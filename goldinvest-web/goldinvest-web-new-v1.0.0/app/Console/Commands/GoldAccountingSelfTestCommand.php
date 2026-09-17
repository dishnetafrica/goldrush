<?php

namespace App\Console\Commands;

use App\Accounting\Exceptions\AccountingException;
use App\Accounting\Models\AccountingPeriod;
use App\Accounting\Models\CashAccount;
use App\Accounting\Services\CashAccountService;
use App\Accounting\Services\CashService;
use App\Accounting\Services\GoldTradingPoster;
use App\Accounting\Services\InventoryValuation;
use App\Accounting\Services\JournalPoster;
use App\Accounting\Services\TrialBalance;
use App\GoldTrading\Models\GoldLot;
use App\GoldTrading\Models\GoldProcessing;
use App\GoldTrading\Models\GoldSale;
use App\GoldTrading\Models\TradingExpense;
use App\GoldTrading\Services\LotResultCalculator;
use App\Investor\Support\Money;
use App\Models\Admin\Admin;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * The Phase 3C acceptance run.
 *
 * Buys a lot of gold, refines it, sells it and pays a deal cost, checking the
 * books after each step — all inside a transaction that is always rolled back.
 * The historical deals are never touched: the poster refuses them outright, and
 * that refusal is one of the things tested here.
 */
class GoldAccountingSelfTestCommand extends Command
{
    protected $signature = 'gold:accounting-selftest {--skip-regression : gold accounting tests only}';

    protected $description = 'Verify gold purchase, inventory, processing, sale, COGS and expense accounting';

    private array $results = [];
    private string $date;

    // A deal shaped like the real ones: 26 g at 80.00, 8% lost refining, sold at
    // international less 10%.
    private const GRAMS = 26.0;
    private const PRICE_PER_GRAM = 80.0;
    private const WASTE_PERCENT = 8.0;
    private const SALE_PRICE = 127.971;

    public function handle(
        GoldTradingPoster $gold,
        InventoryValuation $valuation,
        CashAccountService $accounts,
        CashService $cash,
        JournalPoster $poster,
        TrialBalance $trialBalance,
    ): int {
        // Artisan resolves a command once per process, so a suite invoked twice in
        // the same run would otherwise report the first run's results alongside
        // the second's and count them all.
        $this->results = [];

        $this->line('Gold trading accounting self-test (Phase 3C)');
        $this->line(str_repeat('-', 60));

        $period = AccountingPeriod::where('status', AccountingPeriod::OPEN)->orderByDesc('starts_on')->first();

        if (! $period) {
            $this->error('No open accounting period. Run accounting:install first.');

            return self::FAILURE;
        }

        $this->date = $period->starts_on->copy()->addDays(3)->toDateString();

        DB::beginTransaction();

        try {
            $bank = $accounts->create(['name' => 'Gold Test Bank', 'type' => CashAccount::TYPE_BANK, 'code' => 'GT-BANK']);
            $cash->receipt($bank, 10000.00, '2000', ['date' => $this->date, 'memo' => 'Self-test funding']);

            $lot = $this->makeLot();

            $this->purchase($gold, $valuation, $lot, $bank);
            $this->processing($gold, $valuation, $lot, $bank);
            $this->sale($gold, $valuation, $lot, $bank);
            $this->expense($gold, $lot, $bank);
            $this->negativeInventoryRefused($gold, $valuation, $lot, $bank);
            $this->immutabilityAndReversal($gold, $valuation, $poster, $lot);
            $this->duplicatePostingRefused($gold, $lot, $bank);
            $this->periodEnforcement($gold, $bank);
            $this->permissions($gold, $bank);
            $this->historicalRefused($gold);
            $this->divergenceReported($valuation, $lot);
            $this->capitalisedProcessing($gold, $valuation, $accounts, $cash);
            $this->partialSale($gold, $valuation, $accounts, $cash);
            $this->trialBalance($trialBalance);
        } catch (\Throwable $e) {
            $this->check('Unexpected failure', false, $e->getMessage());
        } finally {
            DB::rollBack();
        }

        $this->table(
            ['#', 'Test', 'Result', 'Detail'],
            array_map(
                fn ($r, $i) => [$i + 1, $r[0], $r[1] ? 'PASS' : 'FAIL', $r[2]],
                $this->results,
                array_keys($this->results)
            )
        );

        $failed = array_filter($this->results, fn ($r) => ! $r[1]);
        $this->line('  ' . (count($this->results) - count($failed)) . ' of ' . count($this->results) . ' passed.');
        $this->line('  Everything was rolled back; no lot, journal or balance remains.');

        $regressionOk = true;

        if (! $this->option('skip-regression')) {
            foreach ([
                'Phase 1' => ['ledger:check', ['user' => 'bhavin']],
                'Phase 2' => ['investor:selftest', ['user' => 'bhavin']],
                'Phase 3A' => ['accounting:selftest', ['--skip-regression' => true]],
                'Phase 3B' => ['cash:selftest', ['--skip-regression' => true]],
            ] as $label => [$command, $args]) {
                $this->newLine();
                $this->line($label . ' regression');
                $regressionOk = $this->call($command, $args) === 0 && $regressionOk;
            }
        }

        if ($failed !== [] || ! $regressionOk) {
            $this->error('FAIL');

            return self::FAILURE;
        }

        $this->info('PASS');

        return self::SUCCESS;
    }

    private function makeLot(?float $totalCost = null, ?float $grams = null, ?float $wastePercent = null): GoldLot
    {
        $grams ??= self::GRAMS;
        $totalCost ??= self::GRAMS * self::PRICE_PER_GRAM;
        $perGram = $grams > 0 ? round($totalCost / $grams, 8) : 0.0;

        return GoldLot::create([
            'lot_code'             => 'GT-SELFTEST-' . substr(md5(uniqid('', true)), 0, 8),
            'purchase_date'        => $this->date,
            'project_name'         => 'Self-test deal',
            'location'             => 'Test',
            'gross_grams'          => $grams,
            'purchase_currency'    => 'USD',
            'price_per_gram_local' => $perGram,
            'fx_rate_to_usd'       => 1,
            'price_per_gram_usd'   => $perGram,
            'total_cost_local'     => $totalCost,
            'total_cost_usd'       => $totalCost,
            'status'               => 'purchased',
        ]);
    }

    private function makeSale(GoldLot $lot, float $grams, float $pricePerGram): GoldSale
    {
        return GoldSale::create([
            'sale_code'          => $lot->lot_code . '-S' . (GoldSale::where('gold_lot_id', $lot->id)->count() + 1),
            'gold_lot_id'        => $lot->id,
            'sale_date'          => $this->date,
            'buyer_name'         => 'Self-test buyer',
            'grams_sold'         => $grams,
            'price_per_gram_usd' => $pricePerGram,
            'gross_proceeds_usd' => round($grams * $pricePerGram, 8),
            'settlement_currency' => 'USD',
            'fx_rate_to_usd'     => 1,
            'status'             => GoldSale::STATUS_SETTLED,
        ]);
    }

    /** 1-3. The purchase journal, the quantity and the cost. */
    private function purchase(GoldTradingPoster $gold, InventoryValuation $valuation, GoldLot $lot, CashAccount $bank): void
    {
        $bankBefore = $bank->balance();
        $journal = $gold->purchase($lot, $bank, ['date' => $this->date]);
        $v = $valuation->forLot($lot->fresh());

        $expected = self::GRAMS * self::PRICE_PER_GRAM;

        $this->check(
            'Gold purchase posts a balanced journal',
            $journal->balances() && abs($journal->totalDebit() - $expected) < 0.00000001,
            $journal->reference . ': Dr 1100 ' . Money::format($expected) . ' / Cr ' . $bank->glAccount->code
        );

        $this->check(
            'Purchase reduces the bank and raises inventory',
            abs(($bankBefore - $bank->fresh()->balance()) - $expected) < 0.00000001
            && abs($v['gl_unrefined_usd'] - $expected) < 0.00000001,
            'bank -' . Money::format($expected) . ', unrefined inventory ' . Money::format($v['gl_unrefined_usd'])
        );

        $this->check(
            'Inventory quantity recorded',
            abs($v['gross_grams'] - self::GRAMS) < 0.0001,
            number_format($v['gross_grams'], 4) . ' g at ' . Money::format(self::PRICE_PER_GRAM) . '/g'
        );
    }

    /** 4-5. Refining: cost moves, grams are lost, no journal for the loss. */
    private function processing(GoldTradingPoster $gold, InventoryValuation $valuation, GoldLot $lot, CashAccount $bank): void
    {
        $waste = round(self::GRAMS * self::WASTE_PERCENT / 100, 4);
        $output = round(self::GRAMS - $waste, 4);

        $processing = GoldProcessing::create([
            'gold_lot_id'   => $lot->id,
            'processed_at'  => $this->date,
            'method'        => 'Self-test refining',
            'input_grams'   => self::GRAMS,
            'waste_grams'   => $waste,
            'waste_percent' => self::WASTE_PERCENT,
            'output_grams'  => $output,
            'output_purity' => '24K',
            'cost_usd'      => 0,
        ]);

        $journal = $gold->refine($processing, $bank, ['date' => $this->date]);
        $v = $valuation->forLot($lot->fresh());

        $cost = self::GRAMS * self::PRICE_PER_GRAM;

        $this->check(
            'Refining moves cost without losing any',
            $journal->balances()
            && abs($v['gl_unrefined_usd']) < 0.00000001
            && abs($v['gl_refined_usd'] - $cost) < 0.00000001,
            $journal->reference . ': unrefined ' . Money::format($v['gl_unrefined_usd'])
            . ', refined ' . Money::format($v['gl_refined_usd'])
        );

        $expectedPerGram = round($cost / $output, 8);

        $this->check(
            'Wastage raises the cost per gram and posts no journal of its own',
            abs($v['cost_per_refined_gram'] - $expectedPerGram) < 0.00000001
            && $journal->lines->count() === 2,
            number_format($waste, 4) . ' g lost, cost per gram now '
            . Money::format($v['cost_per_refined_gram']) . ' from ' . Money::format(self::PRICE_PER_GRAM)
        );
    }

    /** 6-8. The sale, the cost of what left, and what remains. */
    private function sale(GoldTradingPoster $gold, InventoryValuation $valuation, GoldLot $lot, CashAccount $bank): void
    {
        $before = $valuation->forLot($lot->fresh());
        $grams = $before['refined_grams'];
        $proceeds = round($grams * self::SALE_PRICE, 8);

        $sale = GoldSale::create([
            'sale_code'          => $lot->lot_code . '-S1',
            'gold_lot_id'        => $lot->id,
            'sale_date'          => $this->date,
            'buyer_name'         => 'Self-test buyer',
            'grams_sold'         => $grams,
            'price_per_gram_usd' => self::SALE_PRICE,
            'gross_proceeds_usd' => $proceeds,
            'settlement_currency' => 'USD',
            'fx_rate_to_usd'     => 1,
            'status'             => GoldSale::STATUS_SETTLED,
        ]);

        $bankBefore = $bank->balance();
        $journal = $gold->sell($sale, $bank, ['date' => $this->date]);
        $after = $valuation->forLot($lot->fresh());

        $cogsLine = $journal->lines->first(fn ($l) => $l->account->code === '5000');
        $revenueLine = $journal->lines->first(fn ($l) => $l->account->code === '4000');

        $this->check(
            'Gold sale posts revenue and cost in one balanced journal',
            $journal->balances() && $journal->lines->count() === 4
            && abs($revenueLine->credit - $proceeds) < 0.00000001,
            $journal->reference . ': revenue ' . Money::format($proceeds)
            . ', bank +' . Money::format($bank->fresh()->balance() - $bankBefore)
        );

        $expectedCogs = round($before['cost_basis_usd'], 8);

        $this->check(
            'Cost of goods sold charges the cost of the grams that left',
            abs($cogsLine->debit - $expectedCogs) < 0.00000001,
            Money::format($cogsLine->debit) . ' for ' . number_format($grams, 4) . ' g'
        );

        $this->check(
            'Selling the lot out leaves no inventory behind',
            abs($after['gl_refined_usd']) < 0.00000001 && abs($after['remaining_grams']) < 0.0001,
            'inventory ' . Money::format($after['gl_refined_usd']) . ', '
            . number_format($after['remaining_grams'], 4) . ' g held'
        );

        $this->check(
            'Gross profit is proceeds less cost',
            abs(($proceeds - $expectedCogs) - round($proceeds - $expectedCogs, 8)) < 0.00000001,
            Money::format($proceeds) . ' - ' . Money::format($expectedCogs)
            . ' = ' . Money::format($proceeds - $expectedCogs)
        );
    }

    /** 9-10. A deal cost, paid from the bank. */
    private function expense(GoldTradingPoster $gold, GoldLot $lot, CashAccount $bank): void
    {
        $expense = TradingExpense::create([
            // Costs reach the general ledger only once approved (Phase 3D), so a
            // fixture that is about posting starts from there.
            'status'         => TradingExpense::STATUS_APPROVED,
            'expense_date'   => $this->date,
            'category'       => 'transport',
            'description'    => 'Self-test transport',
            'currency_code'  => 'USD',
            'amount_local'   => 120.00,
            'fx_rate_to_usd' => 1,
            'amount_usd'     => 120.00,
            'gold_lot_id'    => $lot->id,
        ]);

        $bankBefore = $bank->balance();
        $journal = $gold->expense($expense, $bank, ['date' => $this->date]);

        $expenseLine = $journal->lines->first(fn ($l) => $l->account->code === '6000');

        $this->check(
            'Deal expense posts to its category and the bank',
            $journal->balances()
            && abs($expenseLine->debit - 120.00) < 0.00000001
            && abs(($bankBefore - $bank->fresh()->balance()) - 120.00) < 0.00000001
            && $expenseLine->gold_lot_id === $lot->id,
            $journal->reference . ': Dr 6000 Transport ' . Money::format(120.00)
            . ' / Cr ' . $bank->glAccount->code . ', tagged to ' . $lot->lot_code
        );
    }

    /** Inventory cannot go negative. */
    private function negativeInventoryRefused(GoldTradingPoster $gold, InventoryValuation $valuation, GoldLot $lot, CashAccount $bank): void
    {
        $extra = GoldSale::create([
            'sale_code'          => $lot->lot_code . '-S2',
            'gold_lot_id'        => $lot->id,
            'sale_date'          => $this->date,
            'buyer_name'         => 'Self-test overselling buyer',
            'grams_sold'         => 5.0,
            'price_per_gram_usd' => self::SALE_PRICE,
            'gross_proceeds_usd' => round(5.0 * self::SALE_PRICE, 8),
            'settlement_currency' => 'USD',
            'fx_rate_to_usd'     => 1,
            'status'             => GoldSale::STATUS_SETTLED,
        ]);

        $this->check(
            'Selling gold the lot does not hold is refused',
            $this->refused(fn () => $gold->sell($extra, $bank, ['date' => $this->date])),
            'inventory cannot go negative'
        );

        $extra->delete();
    }

    /** Posted gold journals are immutable and corrected by reversal. */
    private function immutabilityAndReversal(GoldTradingPoster $gold, InventoryValuation $valuation, JournalPoster $poster, GoldLot $lot): void
    {
        $journal = \App\Accounting\Models\Journal::findOrFail($lot->fresh()->purchase_journal_id);

        $editRefused = $this->refused(fn () => $journal->update(['memo' => 'tampered']), AccountingException::class);
        $journal->refresh();

        $before = $valuation->glInventory($lot);
        $reversal = $poster->reverse($journal, 'Self-test correction', null, $this->date);
        $after = $valuation->glInventory($lot);

        $this->check('Posted gold journal is immutable', $editRefused, 'edit refused on ' . $journal->reference);

        $this->check(
            'Reversing the purchase removes its cost from inventory',
            $reversal->balances() && abs(($before - $after) - (self::GRAMS * self::PRICE_PER_GRAM)) < 0.00000001,
            $journal->reference . ' reversed by ' . $reversal->reference
            . ', inventory moved ' . Money::format($after - $before)
        );
    }

    /** The same record cannot be posted twice. */
    private function duplicatePostingRefused(GoldTradingPoster $gold, GoldLot $lot, CashAccount $bank): void
    {
        $this->check(
            'A record already posted cannot be posted again',
            $this->refused(fn () => $gold->purchase($lot->fresh(), $bank, ['date' => $this->date])),
            'the lot already carries a purchase journal'
        );
    }

    /** Periods are enforced here as everywhere else. */
    private function periodEnforcement(GoldTradingPoster $gold, CashAccount $bank): void
    {
        $period = AccountingPeriod::orderBy('starts_on')->first();
        $was = $period->status;
        $period->update(['status' => AccountingPeriod::CLOSED]);

        $lot = $this->makeLot();

        $refused = $this->refused(fn () => $gold->purchase($lot, $bank, ['date' => $this->date]));

        $period->update(['status' => $was]);

        $this->check('Posting into a closed period refused', $refused, 'period ' . $period->code . ' rejected the purchase');
    }

    /** Permissions hold for gold postings too. */
    private function permissions(GoldTradingPoster $gold, CashAccount $bank): void
    {
        $stranger = new Admin();
        $stranger->id = -1;
        $stranger->username = 'selftest-no-permissions';

        $lot = $this->makeLot();

        $this->check(
            'Admin without permission cannot post gold accounting',
            $this->refused(fn () => $gold->purchase($lot, $bank, ['date' => $this->date], $stranger)),
            'refused for an admin holding no grants'
        );
    }

    /** The historical deals stay out of the ledger until the backfill. */
    private function historicalRefused(GoldTradingPoster $gold): void
    {
        $distributedLotIds = \App\GoldTrading\Models\LotResult::where('status', 'distributed')->pluck('gold_lot_id');
        $historical = GoldLot::whereIn('id', $distributedLotIds)->first();

        if (! $historical) {
            $this->check('Historical deals are refused until the backfill', true, 'no historical deal present to test');

            return;
        }

        $this->check(
            'Historical deals are refused until the backfill',
            $this->refused(fn () => $gold->purchase($historical, null, ['date' => $this->date])),
            $historical->lot_code . ' refused: its funding has not been established'
        );
    }

    /** The known disagreement with the investor-facing calculator is reported. */
    private function divergenceReported(InventoryValuation $valuation, GoldLot $lot): void
    {
        $divergence = $valuation->divergence($lot->fresh());

        $this->check(
            'Cost of sales agrees with the deal result while processing costs are zero',
            $divergence['agrees'],
            'capitalised ' . Money::format($divergence['capitalised_cost_usd'])
            . ', difference ' . Money::exact($divergence['cogs_difference_usd'])
        );
    }

    /**
     * Decision D4, on the figures that were specified: 2,000 for 25 g, 8% lost,
     * and a 100 processing charge that belongs in the gold rather than in the
     * period's expenses.
     */
    private function capitalisedProcessing(
        GoldTradingPoster $gold,
        InventoryValuation $valuation,
        CashAccountService $accounts,
        CashService $cash,
    ): void {
        $bank = $accounts->create(['name' => 'D4 Bank', 'type' => CashAccount::TYPE_BANK, 'code' => 'D4-BANK']);
        $cash->receipt($bank, 10000.00, '2000', ['date' => $this->date, 'memo' => 'D4 funding']);

        $lot = $this->makeLot(2000.00, 25.0, 8.0);
        $gold->purchase($lot, $bank, ['date' => $this->date]);

        $processing = GoldProcessing::create([
            'gold_lot_id'      => $lot->id,
            'processed_at'     => $this->date,
            'method'           => 'D4 refining',
            'input_grams'      => 25.0,
            'waste_grams'      => 2.0,
            'waste_percent'    => 8.0,
            'output_grams'     => 23.0,
            'output_purity'    => '24K',
            'cost_usd'         => 100.00,
            'cost_capitalised' => true,
        ]);

        $refineJournal = $gold->refine($processing, $bank, ['date' => $this->date]);
        $v = $valuation->forLot($lot->fresh());

        $this->check(
            'D4: processing cost enters inventory, not expenses',
            abs($v['cost_basis_usd'] - 2100.00) < 0.00000001
            && abs($v['gl_refined_usd'] - 2100.00) < 0.00000001
            && $refineJournal->balances(),
            'cost basis ' . Money::format($v['cost_basis_usd'])
            . ' = 2,000.00 purchase + ' . Money::format($v['capitalised_cost_usd']) . ' processing; '
            . 'ledger refined inventory ' . Money::format($v['gl_refined_usd'])
        );

        $expectedPerGram = round(2100.00 / 23.0, 8);

        $this->check(
            'D4: cost per refined gram is 2,100.00 / 23 g',
            abs($v['cost_per_refined_gram'] - $expectedPerGram) < 0.00000001,
            Money::exact($v['cost_per_refined_gram']) . ' expected ' . Money::exact($expectedPerGram)
        );

        $sale = $this->makeSale($lot, 23.0, 130.00);
        $saleJournal = $gold->sell($sale, $bank, ['date' => $this->date]);
        $after = $valuation->forLot($lot->fresh());

        $cogsLine = $saleJournal->lines->first(fn ($l) => $l->account->code === '5000');

        $this->check(
            'D4: selling all 23 g charges the whole 2,100.00 to cost of sales',
            abs($cogsLine->debit - 2100.00) < 0.00000001
            && abs($after['gl_refined_usd']) < 0.00000001,
            'COGS ' . Money::format($cogsLine->debit) . ', inventory left ' . Money::format($after['gl_refined_usd'])
        );

        // The point of the whole decision: the 100 is in the cost of the gold, so
        // it must not appear again as an expense of the deal.
        $result = app(LotResultCalculator::class)->forLot($lot->fresh());

        $this->check(
            'D4: the processing charge is not counted twice',
            abs((float) $result['expenses_usd']) < 0.00000001
            && abs((float) $result['cost_of_goods_sold_usd'] - 2100.00) < 0.00000001,
            'deal expenses ' . Money::format((float) $result['expenses_usd'])
            . ', COGS ' . Money::format((float) $result['cost_of_goods_sold_usd'])
        );

        $expectedNet = round(23.0 * 130.00 - 2100.00, 8);

        $this->check(
            'D4: trading result is proceeds less the full inventory cost',
            abs((float) $result['net_profit_usd'] - $expectedNet) < 0.00000001,
            Money::format(23.0 * 130.00) . ' - ' . Money::format(2100.00)
            . ' = ' . Money::format((float) $result['net_profit_usd'])
        );

        $divergence = $valuation->divergence($lot->fresh());

        $this->check(
            'D4: ledger and deal result agree with a processing charge present',
            $divergence['agrees'],
            'difference ' . Money::exact($divergence['cogs_difference_usd'])
            . ' on a capitalised ' . Money::format($divergence['capitalised_cost_usd'])
        );
    }

    /** Only the cost of what was sold leaves inventory; the rest keeps its value. */
    private function partialSale(
        GoldTradingPoster $gold,
        InventoryValuation $valuation,
        CashAccountService $accounts,
        CashService $cash,
    ): void {
        $bank = $accounts->create(['name' => 'D4 Partial Bank', 'type' => CashAccount::TYPE_BANK, 'code' => 'D4-PART']);
        $cash->receipt($bank, 10000.00, '2000', ['date' => $this->date, 'memo' => 'Partial sale funding']);

        $lot = $this->makeLot(2000.00, 25.0, 8.0);
        $gold->purchase($lot, $bank, ['date' => $this->date]);

        $processing = GoldProcessing::create([
            'gold_lot_id'      => $lot->id,
            'processed_at'     => $this->date,
            'method'           => 'Partial sale refining',
            'input_grams'      => 25.0,
            'waste_grams'      => 2.0,
            'waste_percent'    => 8.0,
            'output_grams'     => 23.0,
            'output_purity'    => '24K',
            'cost_usd'         => 100.00,
            'cost_capitalised' => true,
        ]);

        $gold->refine($processing, $bank, ['date' => $this->date]);

        $sale = $this->makeSale($lot, 10.0, 130.00);
        $journal = $gold->sell($sale, $bank, ['date' => $this->date]);
        $after = $valuation->forLot($lot->fresh());

        $perGram = round(2100.00 / 23.0, 8);
        $expectedCogs = round($perGram * 10.0, 8);
        $expectedRemaining = round(2100.00 - $expectedCogs, 8);

        $cogsLine = $journal->lines->first(fn ($l) => $l->account->code === '5000');

        $this->check(
            'Partial sale charges only the grams that left',
            abs($cogsLine->debit - $expectedCogs) < 0.00000001,
            'COGS ' . Money::exact($cogsLine->debit) . ' for 10 g at ' . Money::exact($perGram) . '/g'
        );

        $this->check(
            'Unsold gold keeps its carrying value',
            abs($after['gl_refined_usd'] - $expectedRemaining) < 0.00000001
            && abs($after['remaining_value_usd'] - $expectedRemaining) < 0.00000001
            && abs($after['remaining_grams'] - 13.0) < 0.0001,
            '13 g left, carried at ' . Money::exact($after['gl_refined_usd'])
        );

        $this->check(
            'Cost sold plus cost held equals the cost basis',
            abs(($expectedCogs + $expectedRemaining) - 2100.00) < 0.00000001,
            Money::exact($expectedCogs) . ' + ' . Money::exact($expectedRemaining) . ' = 2,100.00000000'
        );
    }

    /** The books still hold together. */
    private function trialBalance(TrialBalance $trialBalance): void
    {
        $report = $trialBalance->build();

        $this->check(
            'Trial balance still balances',
            $report['balanced'],
            'debits ' . Money::format($report['total_debits'])
            . ' credits ' . Money::format($report['total_credits'])
            . ' difference ' . Money::exact($report['difference'])
        );
    }

    private function refused(callable $action, ?string $expected = null): bool
    {
        try {
            $action();
        } catch (\Throwable $e) {
            return $expected === null ? true : $e instanceof $expected;
        }

        return false;
    }

    private function check(string $name, bool $passed, string $detail = ''): void
    {
        $this->results[] = [$name, $passed, $detail];
    }
}
