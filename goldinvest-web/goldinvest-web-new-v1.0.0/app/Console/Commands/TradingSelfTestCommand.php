<?php

namespace App\Console\Commands;

use App\Accounting\Models\Account;
use App\Accounting\Models\AccountingPeriod;
use App\Accounting\Models\CashAccount;
use App\Accounting\Models\Journal;
use App\Accounting\Security\AccountingPermission;
use App\Accounting\Services\CashAccountService;
use App\Accounting\Services\CashService;
use App\Accounting\Services\ExpenseWorkflow;
use App\Accounting\Services\GoldTradingPoster;
use App\Accounting\Services\InventoryValuation;
use App\Accounting\Services\RealizedResultRecorder;
use App\Accounting\Services\RealizedTradingResult;
use App\Accounting\Services\TrialBalance;
use App\GoldTrading\Models\GoldLot;
use App\GoldTrading\Models\GoldProcessing;
use App\GoldTrading\Models\GoldSale;
use App\GoldTrading\Models\LotResult;
use App\GoldTrading\Services\LotCostBasis;
use App\Investor\Support\Money;
use App\Models\Admin\Admin;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * The Phase 3E acceptance run.
 *
 * Builds complete deals — bought, refined, costed, sold — entirely through the
 * posting services, then checks that the realized result read back out of the
 * general ledger is the one arithmetic says it should be. Everything happens
 * inside a transaction that is always rolled back.
 *
 * The historical deals are read but never written. That they still refuse to be
 * recorded, and that their figures have not moved, is one of the things tested.
 */
class TradingSelfTestCommand extends Command
{
    protected $signature = 'trading:selftest {--skip-regression : trading result tests only}';

    protected $description = 'Verify the company realized trading result against the general ledger';

    private array $results = [];
    private string $date;
    private const EPS = 0.00000001;

    private ExpenseWorkflow $expenses;
    private GoldTradingPoster $gold;
    private ?CashAccount $bank = null;
    private ?Admin $super = null;

    public function handle(
        RealizedTradingResult $realized,
        RealizedResultRecorder $recorder,
        ExpenseWorkflow $expenses,
        GoldTradingPoster $gold,
        InventoryValuation $inventory,
        LotCostBasis $costBasis,
        CashAccountService $accounts,
        CashService $cash,
        TrialBalance $trialBalance,
    ): int {
        // Artisan resolves a command once per process, so a suite invoked twice in
        // the same run would otherwise report the first run's results alongside
        // the second's and count them all.
        $this->results = [];

        $this->line('Realized trading result self-test (Phase 3E)');
        $this->line(str_repeat('-', 60));

        $period = AccountingPeriod::where('status', AccountingPeriod::OPEN)->orderByDesc('starts_on')->first();

        if (! $period) {
            $this->error('No open accounting period. Run accounting:install first.');

            return self::FAILURE;
        }

        $this->date = $period->starts_on->copy()->addDays(3)->toDateString();
        $this->expenses = $expenses;
        $this->gold = $gold;

        $investorsBefore = $this->investorSnapshot();
        $suspenseBefore = $this->accountBalance('1090');
        $historicalBefore = $this->historicalSnapshot();

        DB::beginTransaction();

        try {
            $this->super = $this->superAdmin();
            $this->bank = $accounts->create([
                'name' => 'Trading Result Bank', 'type' => CashAccount::TYPE_BANK, 'code' => 'TR-BANK',
            ]);
            $cash->receipt($this->bank, 40000.00, '3000', ['date' => $this->date, 'memo' => 'Self-test funding']);

            $caseA = $this->caseA($realized);
            $caseB = $this->caseB($realized);
            $caseC = $this->caseC($realized, $inventory, $costBasis);
            $caseD = $this->multipleExpenses($realized);

            $this->company($realized, $period, [$caseA, $caseB, $caseC, $caseD]);
            $this->inventoryReconciles($realized, $inventory, [$caseA, $caseB, $caseC, $caseD]);
            $this->periodScope($realized, $caseA, $period);
            $this->pendingExpense($realized, $recorder);
            $this->recording($realized, $recorder, $caseA);
            $this->recordingRefusals($realized, $recorder, $caseC, $period);
            $this->permissions($recorder, $caseB);
            $this->immutability($recorder, $caseA);
            $this->historicalRefused($realized, $recorder);
            $this->trialBalance($trialBalance);
        } catch (\Throwable $e) {
            $this->check('Unexpected failure', false,
                $e->getMessage() . ' @ ' . basename($e->getFile()) . ':' . $e->getLine());
        } finally {
            DB::rollBack();
        }

        $this->untouched($investorsBefore, $suspenseBefore, $historicalBefore);

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
        $this->line('  Everything was rolled back; no lot, journal, result or balance remains.');

        $regressionOk = true;

        if (! $this->option('skip-regression')) {
            foreach ([
                'Phase 1' => ['ledger:check', ['user' => 'bhavin']],
                'Phase 2' => ['investor:selftest', ['user' => 'bhavin']],
                'Phase 3A' => ['accounting:selftest', ['--skip-regression' => true]],
                'Phase 3B' => ['cash:selftest', ['--skip-regression' => true]],
                'Phase 3C and D4' => ['gold:accounting-selftest', ['--skip-regression' => true]],
                'Phase 3D' => ['expense:selftest', ['--skip-regression' => true]],
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

    /**
     * 1-3. Case A: a clean trade.
     *
     * 2,000.00 of gold, 23 g after refining, a 100.00 processing charge
     * capitalised into it, all of it sold at 130.00.
     */
    private function caseA(RealizedTradingResult $realized): GoldLot
    {
        $lot = $this->tradedLot([
            'cost' => 2000.00, 'grams' => 25.0, 'waste' => 2.0,
            'capitalised' => 100.00, 'sell_grams' => 23.0, 'price' => 130.00,
        ]);

        $r = $realized->forLot($lot->fresh());

        $this->check(
            'Revenue is what the ledger was credited on 4000',
            abs($r['revenue_usd'] - 2990.00) < self::EPS
            && abs($r['revenue_usd'] - $this->accountBalance('4000', $lot)) < self::EPS,
            Money::format($r['revenue_usd']) . ' for 23 g at 130.00, read from account 4000'
        );

        $this->check(
            'Cost of sales is what the ledger was debited on 5000',
            abs($r['cost_of_goods_sold_usd'] - 2100.00) < self::EPS
            && abs($r['cost_of_goods_sold_usd'] - $this->accountBalance('5000', $lot)) < self::EPS
            && abs($r['capitalised_cost_usd'] - 100.00) < self::EPS,
            Money::format($r['cost_of_goods_sold_usd']) . ' = 2,000.00 purchase + 100.00 capitalised'
        );

        $this->check(
            'Gross trading profit is revenue less cost of sales',
            abs($r['gross_profit_usd'] - 890.00) < self::EPS
            && abs($r['ordinary_expenses_usd']) < self::EPS
            && abs($r['net_realized_usd'] - 890.00) < self::EPS,
            '2,990.00 - 2,100.00 = ' . Money::format($r['gross_profit_usd'])
            . ', no ordinary expenses, realized ' . Money::format($r['net_realized_usd'])
        );

        return $lot;
    }

    /** 4-6. Case B: the same trade with a transport cost on top. */
    private function caseB(RealizedTradingResult $realized): GoldLot
    {
        $lot = $this->tradedLot([
            'cost' => 2000.00, 'grams' => 25.0, 'waste' => 2.0,
            'capitalised' => 100.00, 'sell_grams' => 23.0, 'price' => 130.00,
            'expenses' => [['transport', 120.00]],
        ]);

        $r = $realized->forLot($lot->fresh());

        $this->check(
            'An ordinary deal cost comes off the gross profit',
            abs($r['revenue_usd'] - 2990.00) < self::EPS
            && abs($r['cost_of_goods_sold_usd'] - 2100.00) < self::EPS
            && abs($r['gross_profit_usd'] - 890.00) < self::EPS
            && abs($r['ordinary_expenses_usd'] - 120.00) < self::EPS
            && abs($r['net_realized_usd'] - 770.00) < self::EPS,
            '890.00 - 120.00 = ' . Money::format($r['net_realized_usd'])
        );

        $expenseCodes = array_column($r['expenses_by_account'], 'code');

        $this->check(
            'The capitalised charge is not among the expenses',
            $expenseCodes === ['6000']
            && abs($r['expenses_by_account'][0]['amount_usd'] - 120.00) < self::EPS
            && abs($this->accountBalance('6010', $lot)) < self::EPS,
            'only 6000 Transport 120.00; account 6010 Refining carries nothing for this deal'
        );

        // Everything the company spent, against everything it recognised.
        $spent = 2000.00 + 100.00 + 120.00;
        $recognised = round($r['cost_of_goods_sold_usd'] + $r['ordinary_expenses_usd'], 8);

        $this->check(
            'Nothing is counted twice: revenue less all spending is the realized result',
            abs($recognised - $spent) < self::EPS
            && abs(round($r['revenue_usd'] - $spent, 8) - $r['net_realized_usd']) < self::EPS,
            'spent ' . Money::format($spent) . ' = cost of sales ' . Money::format($r['cost_of_goods_sold_usd'])
            . ' + expenses ' . Money::format($r['ordinary_expenses_usd'])
        );

        return $lot;
    }

    /** 7-8. Case C: only the grams that left. */
    private function caseC(RealizedTradingResult $realized, InventoryValuation $inventory, LotCostBasis $basis): GoldLot
    {
        $lot = $this->tradedLot([
            'cost' => 2000.00, 'grams' => 25.0, 'waste' => 2.0,
            'capitalised' => 100.00, 'sell_grams' => 10.0, 'price' => 130.00,
        ]);

        $r = $realized->forLot($lot->fresh());
        $expectedCogs = round(round(2100.00 / 23.0, 8) * 10.0, 8);
        $expectedHeld = round(2100.00 - $expectedCogs, 8);

        $this->check(
            'A partial sale charges only the grams that left',
            abs($r['cost_of_goods_sold_usd'] - $expectedCogs) < self::EPS
            && abs($r['revenue_usd'] - 1300.00) < self::EPS
            && abs($r['net_realized_usd'] - round(1300.00 - $expectedCogs, 8)) < self::EPS,
            'COGS ' . Money::exact($r['cost_of_goods_sold_usd']) . ' for 10 g, realized '
            . Money::exact($r['net_realized_usd'])
        );

        $glHeld = $inventory->glInventory($lot->fresh());

        $this->check(
            'The gold still held keeps its cost, in the ledger and in the basis alike',
            abs($r['remaining_value_at_cost_usd'] - $expectedHeld) < self::EPS
            && abs($glHeld - $expectedHeld) < self::EPS
            && abs(round($r['cost_of_goods_sold_usd'] + $glHeld, 8) - 2100.00) < self::EPS
            && abs($r['remaining_grams'] - 13.0) < 0.0001,
            '13 g left at ' . Money::exact($glHeld) . '; sold plus held = '
            . Money::exact(round($r['cost_of_goods_sold_usd'] + $glHeld, 8))
        );

        $this->check(
            'An unsold deal is not called complete',
            $r['trading_complete'] === false
            && $r['is_final'] === false
            && $r['stage'] === 'trading in progress',
            $r['stage'] . ': ' . $r['qualification']
        );

        return $lot;
    }

    /** 9-10. A different deal shape, with two costs on it. */
    private function multipleExpenses(RealizedTradingResult $realized): GoldLot
    {
        $lot = $this->tradedLot([
            'cost' => 1500.00, 'grams' => 20.0, 'waste' => 1.0,
            'capitalised' => 0.0, 'sell_grams' => 19.0, 'price' => 100.00,
            'expenses' => [['security', 60.00], ['travel', 40.00]],
        ]);

        $r = $realized->forLot($lot->fresh());

        $this->check(
            'Several costs on one deal are each read from their own account',
            count($r['expenses_by_account']) === 2
            && abs($r['ordinary_expenses_usd'] - 100.00) < self::EPS
            && abs($this->accountBalance('6030', $lot) - 60.00) < self::EPS
            && abs($this->accountBalance('6040', $lot) - 40.00) < self::EPS,
            '6030 Security 60.00 + 6040 Travel 40.00 = ' . Money::format($r['ordinary_expenses_usd'])
        );

        $this->check(
            'A deal with a different shape comes out right too',
            abs($r['revenue_usd'] - 1900.00) < self::EPS
            && abs($r['cost_of_goods_sold_usd'] - 1500.00) < self::EPS
            && abs($r['net_realized_usd'] - 300.00) < self::EPS,
            '1,900.00 - 1,500.00 - 100.00 = ' . Money::format($r['net_realized_usd'])
        );

        return $lot;
    }

    /** 11-13. The company, and that adding the deals up loses nothing. */
    private function company(RealizedTradingResult $realized, AccountingPeriod $period, array $lots): void
    {
        // A cost that belongs to no deal: the office, not the gold.
        $rent = $this->expenses->draft([
            'expense_date' => $this->date, 'category' => 'operating',
            'description'  => 'Office rent, self-test', 'amount_local' => 200.00,
        ]);
        $this->expenses->submit($rent);
        $this->expenses->approve($rent, $this->super);
        $this->expenses->post($rent, $this->bank, ['date' => $this->date]);

        $company = $realized->forCompany(['period_id' => $period->id]);

        $sum = 0.0;
        foreach ($lots as $lot) {
            $sum = round($sum + $realized->forLot($lot->fresh(), ['period_id' => $period->id])['net_realized_usd'], 8);
        }

        $this->check(
            'The company result is the sum of its deals, with nothing counted twice',
            abs($company['trading_result_usd'] - $sum) < self::EPS
            && count($company['lots']) === count($lots),
            Money::format($company['trading_result_usd']) . ' across ' . count($company['lots'])
            . ' deals equals the sum of them individually'
        );

        $this->check(
            'Every posting on revenue and cost of sales is accounted for by a deal',
            $company['reconciles']
            && abs($company['revenue_unattributed_usd']) < self::EPS
            && abs($company['ledger_cogs_usd'] - $company['trading_cogs_usd']) < self::EPS,
            'ledger revenue ' . Money::format($company['ledger_revenue_usd'])
            . ', all of it attributed to deals'
        );

        $this->check(
            'A cost belonging to no deal is kept out of every deal and shown separately',
            abs($company['unattributed_expenses_usd'] - 200.00) < self::EPS
            && abs($company['operating_result_usd'] - round($company['trading_result_usd'] - 200.00, 8)) < self::EPS,
            'office rent 200.00 sits below the trading result, not inside any deal'
        );
    }

    /** 14. Inventory still held reconciles to the ledger, deal by deal. */
    private function inventoryReconciles(RealizedTradingResult $realized, InventoryValuation $inventory, array $lots): void
    {
        $agree = true;
        $held = 0.0;

        foreach ($lots as $lot) {
            $fresh = $lot->fresh();
            $r = $realized->forLot($fresh);
            $gl = $inventory->glInventory($fresh);
            $agree = $agree && abs($r['remaining_value_at_cost_usd'] - $gl) < self::EPS;
            $held = round($held + $gl, 8);
        }

        $this->check(
            'Gold still held reconciles to the ledger on every deal',
            $agree,
            'inventory carried across all self-test deals: ' . Money::exact($held)
        );
    }

    /** 15-16. Asking for one period, and for a window that excludes the trade. */
    private function periodScope(RealizedTradingResult $realized, GoldLot $lot, AccountingPeriod $period): void
    {
        $inPeriod = $realized->forLot($lot->fresh(), ['period_id' => $period->id]);
        $allTime = $realized->forLot($lot->fresh());

        $this->check(
            'A result can be asked for one accounting period',
            abs($inPeriod['net_realized_usd'] - $allTime['net_realized_usd']) < self::EPS
            && abs($inPeriod['net_realized_usd'] - 890.00) < self::EPS,
            'period ' . $period->code . ' holds the whole of this deal: '
            . Money::format($inPeriod['net_realized_usd'])
        );

        $before = $realized->forLot($lot->fresh(), [
            'to' => $period->starts_on->copy()->subDay()->toDateString(),
        ]);

        $this->check(
            'A window that excludes the trade reports none of it',
            abs($before['revenue_usd']) < self::EPS
            && abs($before['cost_of_goods_sold_usd']) < self::EPS
            && abs($before['net_realized_usd']) < self::EPS,
            'nothing up to ' . $period->starts_on->copy()->subDay()->toDateString()
            . '; the trade is not in that window'
        );
    }

    /** 17-19. A sold deal whose costs are still arriving. */
    private function pendingExpense(RealizedTradingResult $realized, RealizedResultRecorder $recorder): void
    {
        $lot = $this->tradedLot([
            'cost' => 800.00, 'grams' => 10.0, 'waste' => 0.0,
            'capitalised' => 0.0, 'sell_grams' => 10.0, 'price' => 120.00,
        ]);

        // Approved, agreed, real — and not yet in the ledger.
        $pending = $this->expenses->draft([
            'expense_date' => $this->date, 'category' => 'transport',
            'description'  => 'Invoice received, not yet posted, self-test',
            'amount_local' => 75.00, 'gold_lot_id' => $lot->id,
        ]);
        $this->expenses->submit($pending);
        $this->expenses->approve($pending, $this->super);

        $r = $realized->forLot($lot->fresh());

        $this->check(
            'A sold deal with a cost still outstanding is not called final',
            $r['trading_complete'] === true
            && $r['is_final'] === false
            && abs($r['unposted_costs_usd'] - 75.00) < self::EPS
            && $r['stage'] === 'trading complete, expenses pending',
            $r['qualification']
        );

        $this->check(
            'Declaring the costs complete is refused while one is outside the ledger',
            $this->refused(fn () => $recorder->finaliseExpenses($lot))
            && $this->refused(fn () => $recorder->record($lot)),
            'the 75.00 has to be posted or rejected first'
        );

        $this->expenses->post($pending, $this->bank, ['date' => $this->date]);
        $recorder->finaliseExpenses($lot);
        $after = $realized->forLot($lot->fresh());

        $this->check(
            'Once the cost is posted and the costs declared complete, the result settles',
            abs($after['unposted_costs_usd']) < self::EPS
            && abs($after['ordinary_expenses_usd'] - 75.00) < self::EPS
            && abs($after['net_realized_usd'] - 325.00) < self::EPS
            && $after['expenses_finalised'] === true
            && $after['stage'] === 'ready to record',
            '1,200.00 - 800.00 - 75.00 = ' . Money::format($after['net_realized_usd'])
            . '; interim result was ' . Money::format($r['net_realized_usd'])
        );

        $this->pendingLot = $lot;
    }

    private ?GoldLot $pendingLot = null;

    /** 20-22. Recording, and recording again. */
    private function recording(RealizedTradingResult $realized, RealizedResultRecorder $recorder, GoldLot $lot): void
    {
        $recorder->finaliseExpenses($lot);
        $before = LotResult::count();

        $record = $recorder->record($lot, $this->super);
        $r = $realized->forLot($lot->fresh());

        $this->check(
            'Recording a result writes the ledger\'s figures and nothing else',
            abs((float) $record->revenue_usd - 2990.00) < self::EPS
            && abs((float) $record->cost_of_goods_sold_usd - 2100.00) < self::EPS
            && abs((float) $record->net_profit_usd - 890.00) < self::EPS
            && abs((float) $record->capitalised_cost_usd - 100.00) < self::EPS
            && (float) $record->investor_profit_usd === 0.0
            && $record->realized_at !== null
            && (int) $record->realized_by === (int) $this->super->id,
            'realized ' . Money::format((float) $record->net_profit_usd) . ' by ' . $this->super->username
            . ', investor share untouched at 0.00'
        );

        $this->check(
            'Only now is the result final',
            $r['is_final'] === true
            && $r['stage'] === 'realized'
            && $r['result_recorded'] === true
            && $r['qualification'] === null,
            $lot->lot_code . ' is ' . $r['stage'] . '; nothing further can move it'
        );

        $again = $recorder->record($lot, $this->super);

        $this->check(
            'Recording the same result twice records it once',
            (int) $again->id === (int) $record->id
            && LotResult::count() === $before + 1
            && $again->realized_at->equalTo($record->realized_at),
            'handed back the result recorded at ' . $record->realized_at->format('H:i:s')
            . '; no second row was written'
        );
    }

    /** 23-25. What may not be recorded. */
    private function recordingRefusals(
        RealizedTradingResult $realized,
        RealizedResultRecorder $recorder,
        GoldLot $partial,
        AccountingPeriod $period,
    ): void {
        $this->check(
            'A deal still holding gold cannot have its result recorded',
            $this->refused(fn () => $recorder->record($partial, $this->super)),
            $partial->lot_code . ' still holds 13 g; what it realized is not known yet'
        );

        $unposted = GoldLot::create([
            'lot_code' => 'TR-UNPOSTED-' . substr(md5(uniqid('', true)), 0, 8),
            'purchase_date' => $this->date, 'project_name' => 'Never posted', 'gross_grams' => 5,
            'purchase_currency' => 'USD', 'price_per_gram_local' => 80, 'fx_rate_to_usd' => 1,
            'price_per_gram_usd' => 80, 'total_cost_local' => 400, 'total_cost_usd' => 400,
            'status' => 'purchased',
        ]);

        $this->check(
            'A deal that never reached the ledger has no result to record',
            $this->refused(fn () => $recorder->record($unposted, $this->super)),
            'nothing is posted for ' . $unposted->lot_code . ', so the ledger holds nothing for it'
        );

        // A deal whose period has since been closed.
        $lot = $this->pendingLot;
        $was = $period->status;
        $period->update(['status' => AccountingPeriod::CLOSED]);

        $refused = $this->refused(fn () => $recorder->record($lot, $this->super));
        $closedView = $realized->forLot($lot->fresh());

        $period->update(['status' => $was]);

        $this->check(
            'A result belonging to a closed period cannot be recorded into it',
            $refused && $closedView['period_closed'] === true,
            'period ' . $period->code . ' had already been stood behind'
        );
    }

    /** 26. Who may record. */
    private function permissions(RealizedResultRecorder $recorder, GoldLot $lot): void
    {
        $stranger = new Admin();
        $stranger->id = -1;
        $stranger->username = 'selftest-no-permissions';

        $this->check(
            'Admin without permission cannot record a result or declare costs complete',
            $this->refused(fn () => $recorder->record($lot, $stranger))
            && $this->refused(fn () => $recorder->finaliseExpenses($lot, $stranger))
            && ! AccountingPermission::allows($stranger, AccountingPermission::RESULT_RECORD),
            'both refused for an admin holding no grants'
        );
    }

    /** 27-28. A recorded result stops moving; so does the ledger under it. */
    private function immutability(RealizedResultRecorder $recorder, GoldLot $lot): void
    {
        $record = LotResult::where('gold_lot_id', $lot->id)->first();

        $this->check(
            'A recorded result cannot be edited',
            $this->refused(fn () => $record->update(['net_profit_usd' => 1.00]))
            && $this->refused(fn () => $record->update(['revenue_usd' => 1.00]))
            && abs((float) $record->fresh()->net_profit_usd - 890.00) < self::EPS,
            'the figures behind ' . $lot->lot_code . ' are fixed at ' . Money::format(890.00)
        );

        $journal = Journal::find($lot->fresh()->purchase_journal_id);

        $this->check(
            'The journals the result was read from are still immutable, and costs cannot be reopened',
            ($journal === null || $this->refused(fn () => $journal->update(['memo' => 'changed'])))
            && $this->refused(fn () => $recorder->reopenExpenses($lot, 'Changed my mind', $this->super)),
            'a late cost is posted to the ledger, not hidden by reopening a recorded deal'
        );
    }

    /** 29-30. The historical deals are read, never written. */
    private function historicalRefused(RealizedTradingResult $realized, RealizedResultRecorder $recorder): void
    {
        $lots = GoldLot::whereIn('id',
            LotResult::where('status', LotResult::STATUS_DISTRIBUTED)->pluck('gold_lot_id')
        )->get();

        if ($lots->isEmpty()) {
            $this->check('Historical deals refuse to be recorded', true, 'no historical deal present to test');

            return;
        }

        $refused = true;
        $detail = [];

        foreach ($lots as $lot) {
            $refused = $refused && $this->refused(fn () => $recorder->record($lot, $this->super));
            $r = $realized->forLot($lot);
            $detail[] = $lot->lot_code . ' ' . $r['stage'];
        }

        $this->check(
            'Historical deals refuse to be recorded and are marked as what they are',
            $refused,
            implode('; ', $detail) . ' — their funding has not been established'
        );
    }

    /** 31-33. Nothing outside this phase moved. */
    private function untouched(array $investorsBefore, float $suspenseBefore, array $historicalBefore): void
    {
        $after = $this->investorSnapshot();

        $this->check(
            'No investor balance or ledger entry moved',
            $investorsBefore === $after,
            'wallets ' . Money::exact($after['balance']) . ' / ' . Money::exact($after['profit'])
            . ', ' . $after['entries'] . ' ledger entries, ' . $after['distributions'] . ' distributions — unchanged'
        );

        $this->check(
            'Suspense is untouched and D2/D3 remain unresolved',
            abs($this->accountBalance('1090') - $suspenseBefore) < self::EPS
            && abs($this->accountBalance('1090')) < self::EPS,
            'account 1090 holds ' . Money::exact($this->accountBalance('1090'))
            . '; nothing was plugged into it to make anything balance'
        );

        $nowHistorical = $this->historicalSnapshot();

        $this->check(
            'The historical deals\' own records are exactly as they were',
            $historicalBefore === $nowHistorical,
            $nowHistorical === []
                ? 'no historical deal present'
                : implode('; ', array_map(
                    fn ($row, $code) => $code . ' net ' . Money::exact((float) $row['net']) . ' ' . $row['status'],
                    $nowHistorical, array_keys($nowHistorical)
                ))
        );
    }

    /** 34. The books still balance. */
    private function trialBalance(TrialBalance $trialBalance): void
    {
        $tb = $trialBalance->build();

        $this->check(
            'Trial balance still balances',
            $tb['balanced'],
            'debits ' . Money::format($tb['total_debits']) . ' credits ' . Money::format($tb['total_credits'])
            . ' difference ' . Money::exact($tb['difference'])
        );
    }

    /**
     * A complete deal, built only through the posting services.
     *
     * Bought, refined, charged, sold — each step through the same path a real
     * deal takes, so the ledger the result is read from is a real one.
     */
    private function tradedLot(array $spec): GoldLot
    {
        $grams = $spec['grams'];
        $cost = $spec['cost'];
        $perGram = round($cost / $grams, 8);

        $lot = GoldLot::create([
            'lot_code'             => 'TR-SELFTEST-' . substr(md5(uniqid('', true)), 0, 8),
            'purchase_date'        => $this->date,
            'project_name'         => 'Trading result self-test',
            'location'             => 'Test',
            'gross_grams'          => $grams,
            'purchase_currency'    => 'USD',
            'price_per_gram_local' => $perGram,
            'fx_rate_to_usd'       => 1,
            'price_per_gram_usd'   => $perGram,
            'total_cost_local'     => $cost,
            'total_cost_usd'       => $cost,
            'status'               => 'purchased',
        ]);

        $this->gold->purchase($lot, $this->bank, ['date' => $this->date]);

        $waste = $spec['waste'];
        $processing = GoldProcessing::create([
            'gold_lot_id'      => $lot->id,
            'processed_at'     => $this->date,
            'method'           => 'Self-test refining',
            'input_grams'      => $grams,
            'waste_grams'      => $waste,
            'waste_percent'    => $grams > 0 ? round(($waste / $grams) * 100, 4) : 0,
            'output_grams'     => round($grams - $waste, 4),
            'output_purity'    => '24K',
            'cost_usd'         => 0.0,
            'cost_capitalised' => true,
        ]);
        $this->gold->refine($processing, null, ['date' => $this->date]);

        // The processing charge arrives as an expense claim and is capitalised,
        // which is the Phase 3D path into the Phase 3C cost basis.
        if (($spec['capitalised'] ?? 0) > 0) {
            $charge = $this->expenses->draft([
                'expense_date' => $this->date, 'category' => 'refining',
                'description'  => 'Refinery charge, self-test',
                'amount_local' => $spec['capitalised'],
                'capitalised'  => true, 'gold_lot_id' => $lot->id,
            ]);
            $this->expenses->submit($charge);
            $this->expenses->approve($charge, $this->super);
            $this->expenses->post($charge, $this->bank, ['date' => $this->date]);
        }

        $sale = GoldSale::create([
            'sale_code'           => $lot->lot_code . '-S1',
            'gold_lot_id'         => $lot->id,
            'sale_date'           => $this->date,
            'buyer_name'          => 'Self-test buyer',
            'grams_sold'          => $spec['sell_grams'],
            'price_per_gram_usd'  => $spec['price'],
            'gross_proceeds_usd'  => round($spec['sell_grams'] * $spec['price'], 8),
            'settlement_currency' => 'USD',
            'fx_rate_to_usd'      => 1,
            'status'              => GoldSale::STATUS_SETTLED,
        ]);
        $this->gold->sell($sale, $this->bank, ['date' => $this->date]);

        foreach ($spec['expenses'] ?? [] as [$category, $amount]) {
            $expense = $this->expenses->draft([
                'expense_date' => $this->date, 'category' => $category,
                'description'  => ucfirst($category) . ', self-test',
                'amount_local' => $amount, 'gold_lot_id' => $lot->id,
            ]);
            $this->expenses->submit($expense);
            $this->expenses->approve($expense, $this->super);
            $this->expenses->post($expense, $this->bank, ['date' => $this->date]);
        }

        return $lot->fresh();
    }

    /** The signed balance of one account, optionally for one deal only. */
    private function accountBalance(string $code, ?GoldLot $lot = null): float
    {
        $account = Account::where('code', $code)->first();

        if (! $account) {
            return 0.0;
        }

        $sums = DB::table('journal_lines')
            ->where('account_id', $account->id)
            ->when($lot, fn ($q) => $q->where('gold_lot_id', $lot->id))
            ->selectRaw('SUM(debit) as d, SUM(credit) as c')
            ->first();

        $net = (float) ($sums->d ?? 0) - (float) ($sums->c ?? 0);

        return round($account->normal_balance === 'credit' ? -$net : $net, 8);
    }

    private function investorSnapshot(): array
    {
        return [
            'balance'       => round((float) DB::table('user_wallets')->sum('balance'), 8),
            'profit'        => round((float) DB::table('user_wallets')->sum('profit_balance'), 8),
            'entries'       => (int) DB::table('investor_ledger_entries')->count(),
            'distributions' => (int) DB::table('gold_profit_distributions')->count(),
        ];
    }

    /** The historical deals' recorded figures, exactly as stored. */
    private function historicalSnapshot(): array
    {
        $rows = [];

        foreach (LotResult::where('status', LotResult::STATUS_DISTRIBUTED)->with('lot')->get() as $result) {
            $rows[$result->lot?->lot_code ?? 'lot#' . $result->gold_lot_id] = [
                'net'         => (string) $result->net_profit_usd,
                'status'      => $result->status,
                'realized_at' => (string) $result->realized_at,
            ];
        }

        ksort($rows);

        return $rows;
    }

    private function superAdmin(): Admin
    {
        $admin = Admin::all()->first(fn (Admin $a) => $a->isSuperAdmin());

        if (! $admin) {
            throw new \RuntimeException('No Super Admin exists to run the recording tests with.');
        }

        return $admin;
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
