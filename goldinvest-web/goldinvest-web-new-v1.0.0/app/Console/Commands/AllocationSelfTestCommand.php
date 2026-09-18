<?php

namespace App\Console\Commands;

use App\Accounting\Models\Account;
use App\Accounting\Models\AccountingPeriod;
use App\Accounting\Models\CashAccount;
use App\Accounting\Services\CashAccountService;
use App\Accounting\Services\CashService;
use App\Accounting\Services\ExpenseWorkflow;
use App\Accounting\Services\GoldTradingPoster;
use App\Accounting\Services\InvestorAllocation;
use App\Accounting\Services\JournalPoster;
use App\Accounting\Services\PeriodCloseService;
use App\Accounting\Services\RealizedResultRecorder;
use App\Accounting\Services\RealizedTradingResult;
use App\Accounting\Services\TrialBalance;
use App\GoldTrading\Models\CapitalAllocation;
use App\GoldTrading\Models\GoldLot;
use App\GoldTrading\Models\GoldProcessing;
use App\GoldTrading\Models\GoldSale;
use App\GoldTrading\Models\LotResult;
use App\Investor\Support\Money;
use App\Models\Admin\Admin;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The Phase 3F allocation-policy acceptance run.
 *
 * Builds a month of its own with deals under different terms, and checks that
 * the investor allocation is exactly what the approved policy says and nothing
 * the policy forbids. Nothing is distributed. Everything happens inside a
 * transaction that is always rolled back, and the investor ledger is checked
 * before and after to prove it never moved.
 */
class AllocationSelfTestCommand extends Command
{
    protected $signature = 'allocation:selftest {--skip-regression : allocation tests only}';

    protected $description = 'Verify the investor allocation policy: basis, eligibility, terms, loss, reserve, separation';

    private array $results = [];
    private const EPS = 0.00000001;

    private ?AccountingPeriod $period = null;
    private string $date;
    private ExpenseWorkflow $expenses;
    private GoldTradingPoster $gold;
    private RealizedResultRecorder $recorder;
    private ?CashAccount $bank = null;
    private ?Admin $super = null;
    private ?User $investorA = null;
    private ?User $investorB = null;

    public function handle(
        InvestorAllocation $allocation,
        RealizedTradingResult $realized,
        RealizedResultRecorder $recorder,
        PeriodCloseService $close,
        ExpenseWorkflow $expenses,
        GoldTradingPoster $gold,
        JournalPoster $poster,
        CashAccountService $accounts,
        CashService $cash,
        TrialBalance $trialBalance,
    ): int {
        // Artisan resolves a command once per process, so a suite invoked twice in
        // the same run would otherwise report the first run's results alongside
        // the second's and count them all.
        $this->results = [];

        $this->line('Investor allocation policy self-test (Phase 3F, allocation only)');
        $this->line(str_repeat('-', 60));

        $this->expenses = $expenses;
        $this->gold = $gold;
        $this->recorder = $recorder;

        $investorsBefore = $this->investorSnapshot();
        $suspenseBefore = $this->accountBalance('1090');
        $realPeriods = AccountingPeriod::orderBy('code')->get(['code', 'status', 'close_reference'])->toArray();
        $historicalBefore = LotResult::where('status', LotResult::STATUS_DISTRIBUTED)
            ->orderBy('gold_lot_id')->get(['gold_lot_id', 'net_profit_usd', 'investor_profit_usd', 'status'])->toArray();

        DB::beginTransaction();

        try {
            $this->super = $this->superAdmin();
            $this->investorA = User::where('username', 'bhavin')->first() ?? $this->tempInvestor('st-investor-a');
            $this->investorB = $this->tempInvestor('st-investor-b');

            $this->period = AccountingPeriod::create([
                'code' => 'ST-2025-02', 'starts_on' => '2025-02-01', 'ends_on' => '2025-02-28',
                'status' => AccountingPeriod::OPEN,
            ]);
            $this->date = '2025-02-10';

            $this->bank = $accounts->create([
                'name' => 'Allocation Bank', 'type' => CashAccount::TYPE_BANK, 'code' => 'AL-BANK',
            ]);
            $cash->receipt($this->bank, 30000.00, '3000', ['date' => $this->date, 'memo' => 'Self-test funding']);

            $this->oneDeal($allocation);
            $this->severalDealsDifferentTerms($allocation);
            $this->overheadExcluded($allocation);
            $this->unsoldExcluded($allocation);
            $this->interimBlocks($allocation);
            $this->missingTermsBlock($allocation);
            $this->noCapitalBlocks($allocation);
            $this->historicalNotEligible($allocation);
            $this->noCircularityThrough7000($allocation, $realized, $poster);
            $this->legacyPlansCannotInfluence($allocation);
            $this->deterministic($allocation);
            $this->openPeriodNotDistributable($allocation, $close);
            $this->negativePoolRefused($allocation, $accounts, $cash);
            $this->zeroPool($allocation, $accounts, $cash);
            $this->reserveIsPolicy($allocation);
            $this->trialBalance($trialBalance);
        } catch (\Throwable $e) {
            $this->check('Unexpected failure', false,
                $e->getMessage() . ' @ ' . basename($e->getFile()) . ':' . $e->getLine());
        } finally {
            DB::rollBack();
        }

        $this->untouched($investorsBefore, $suspenseBefore, $realPeriods, $historicalBefore);

        $this->table(
            ['#', 'Test', 'Result', 'Detail'],
            array_map(fn ($r, $i) => [$i + 1, $r[0], $r[1] ? 'PASS' : 'FAIL', $r[2]],
                $this->results, array_keys($this->results))
        );

        $failed = array_filter($this->results, fn ($r) => ! $r[1]);
        $this->line('  ' . (count($this->results) - count($failed)) . ' of ' . count($this->results) . ' passed.');
        $this->line('  Everything was rolled back; nothing was distributed and no ledger moved.');

        $regressionOk = true;

        if (! $this->option('skip-regression')) {
            foreach ([
                'Phase 1' => ['ledger:check', ['user' => 'bhavin']],
                'Phase 2' => ['investor:selftest', ['user' => 'bhavin']],
                'Phase 3A' => ['accounting:selftest', ['--skip-regression' => true]],
                'Phase 3B' => ['cash:selftest', ['--skip-regression' => true]],
                'Phase 3C and D4' => ['gold:accounting-selftest', ['--skip-regression' => true]],
                'Phase 3D' => ['expense:selftest', ['--skip-regression' => true]],
                'Phase 3E' => ['trading:selftest', ['--skip-regression' => true]],
                'Phase 3F close' => ['period:selftest', ['--skip-regression' => true]],
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

    /** 1. One finalized deal at 100%: the pool is its whole realized result. */
    private function oneDeal(InvestorAllocation $allocation): void
    {
        // 2,000 + 100 capitalised, 23 g at 130 = 2,990; realized 890.00
        $lot = $this->finalizedDeal(['cost' => 2000, 'grams' => 25, 'waste' => 2, 'capitalised' => 100,
            'sell_grams' => 23, 'price' => 130, 'share' => 100,
            'capital' => [[$this->investorA, 2000.00]]]);

        $a = $allocation->forPeriod($this->period);
        $deal = $this->dealIn($a, $lot);

        $this->check(
            'One finalized deal at 100%: the investor pool is its whole realized result',
            $deal !== null
            && abs($deal['realized_usd'] - 890.00) < self::EPS
            && abs($deal['investor_usd'] - 890.00) < self::EPS
            && abs($deal['company_usd']) < self::EPS
            && abs($a['investor_pool_usd'] - 890.00) < self::EPS,
            $lot->lot_code . ': realized 890.00 x 100% = ' . Money::format($deal['investor_usd'] ?? 0)
            . '; pool ' . Money::format($a['investor_pool_usd'])
        );

        $this->lot100 = $lot;
    }

    private ?GoldLot $lot100 = null;

    /** 2-3. Several deals, each under its own terms. */
    private function severalDealsDifferentTerms(InvestorAllocation $allocation): void
    {
        // 1,500, 19 g at 100 = 1,900, expense 60: realized 340.00, share 40%
        $lot40 = $this->finalizedDeal(['cost' => 1500, 'grams' => 20, 'waste' => 1, 'capitalised' => 0,
            'sell_grams' => 19, 'price' => 100, 'expenses' => [['transport', 60.00]], 'share' => 40,
            'capital' => [[$this->investorA, 1500.00]]]);

        // 800, 10 g at 120 = 1,200: realized 400.00; two investors, 60/40 capital,
        // and investor B on a per-investor 50% override against a lot term of 70%.
        $lotMixed = $this->finalizedDeal(['cost' => 800, 'grams' => 10, 'waste' => 0, 'capitalised' => 0,
            'sell_grams' => 10, 'price' => 120, 'share' => 70,
            'capital' => [[$this->investorA, 480.00], [$this->investorB, 320.00, 50.0]]]);

        $a = $allocation->forPeriod($this->period);
        $d40 = $this->dealIn($a, $lot40);
        $dm = $this->dealIn($a, $lotMixed);

        // 340 x 40% = 136.00
        // A: 400 x 60% capital x 70% = 168.00 ; B: 400 x 40% capital x 50% = 80.00
        $this->check(
            'Each deal is split under its own recorded terms, not a shared percentage',
            abs($d40['investor_usd'] - 136.00) < self::EPS
            && abs($d40['company_usd'] - 204.00) < self::EPS
            && abs($dm['investor_usd'] - 248.00) < self::EPS
            && abs($dm['company_usd'] - 152.00) < self::EPS,
            $lot40->lot_code . ' 340.00 x 40% = 136.00; ' . $lotMixed->lot_code
            . ' 400.00 split 168.00 (60% capital x 70%) + 80.00 (40% capital x 50% override)'
        );

        $expectedPool = round(890.00 + 136.00 + 248.00, 8);

        $this->check(
            'The period pool is the sum of the eligible deals\' allocations',
            count($a['eligible']) === 3
            && abs($a['gross_investor_pool_usd'] - $expectedPool) < self::EPS
            && abs($a['by_investor'][$this->investorA->id] - round(890 + 136 + 168, 8)) < self::EPS
            && abs($a['by_investor'][$this->investorB->id] - 80.00) < self::EPS,
            'pool ' . Money::format($a['gross_investor_pool_usd']) . ' = 890.00 + 136.00 + 248.00; '
            . 'A ' . Money::format($a['by_investor'][$this->investorA->id]) . ', B ' . Money::format($a['by_investor'][$this->investorB->id])
        );

        $this->lot40 = $lot40;
    }

    private ?GoldLot $lot40 = null;

    /** 4. Office rent is the company's, not the pool's. */
    private function overheadExcluded(InvestorAllocation $allocation): void
    {
        $before = $allocation->forPeriod($this->period);

        $rent = $this->expenses->draft(['expense_date' => $this->date, 'category' => 'operating',
            'description' => 'Office rent, self-test', 'amount_local' => 250.00]);
        $this->expenses->submit($rent);
        $this->expenses->approve($rent, $this->super);
        $this->expenses->post($rent, $this->bank, ['date' => $this->date]);

        $after = $allocation->forPeriod($this->period);

        $this->check(
            'Company overheads belonging to no deal are excluded from the pool, and shown',
            abs($after['investor_pool_usd'] - $before['investor_pool_usd']) < self::EPS
            && abs($after['company_overheads_excluded_usd'] - 250.00) < self::EPS
            && abs($after['company_trading_result_usd'] - $before['company_trading_result_usd']) < self::EPS,
            'rent 250.00 posted; pool unchanged at ' . Money::format($after['investor_pool_usd'])
            . ', shown as excluded'
        );
    }

    /** 5. Gold still held has realized nothing. */
    private function unsoldExcluded(InvestorAllocation $allocation): void
    {
        $lot = $this->tradedLot(['cost' => 1000, 'grams' => 10, 'waste' => 0, 'capitalised' => 0,
            'sell_grams' => 4, 'price' => 150]);
        $this->allocateCapital($lot, [[$this->investorA, 1000.00]]);
        $lot->forceFill(['investor_share_percent' => 100])->save();

        $a = $allocation->forPeriod($this->period);
        $entry = collect($a['ineligible'])->firstWhere('lot_code', $lot->lot_code);

        $this->check(
            'A deal still holding gold is not eligible, and is not counted for the part it has sold',
            $entry !== null && str_contains($entry['reason'], 'gold still held')
            && $this->dealIn($a, $lot) === null
            && abs($a['investor_pool_usd'] - round(890 + 136 + 248, 8)) < self::EPS
            && $a['refusals'] === array_values(array_filter($a['refusals'], fn ($r) => ! str_contains($r, $lot->lot_code))),
            $lot->lot_code . ': ' . $entry['reason'] . '; pool unchanged, nothing blocked'
        );

        $this->unsoldLot = $lot;
    }

    private ?GoldLot $unsoldLot = null;

    /** 6. A sold-out deal whose result is still interim holds the allocation up. */
    private function interimBlocks(InvestorAllocation $allocation): void
    {
        $lot = $this->tradedLot(['cost' => 500, 'grams' => 5, 'waste' => 0, 'capitalised' => 0,
            'sell_grams' => 5, 'price' => 130]);
        $this->allocateCapital($lot, [[$this->investorA, 500.00]]);
        $lot->forceFill(['investor_share_percent' => 100])->save();

        $a = $allocation->forPeriod($this->period);
        $entry = collect($a['blocked'])->firstWhere('lot_code', $lot->lot_code);

        $refused = $this->refused(fn () => $allocation->assertDistributable($this->period));

        $this->check(
            'An interim result blocks the allocation rather than being left out',
            $entry !== null && str_contains($entry['reason'], 'interim') && $refused
            && $a['distributable'] === false,
            $lot->lot_code . ': ' . $entry['reason']
        );

        $this->recorder->finaliseExpenses($lot);
        $this->recorder->record($lot, $this->super);

        $after = $allocation->forPeriod($this->period);

        $this->check(
            'Once recorded, the same deal joins the pool under its terms',
            collect($after['blocked'])->firstWhere('lot_code', $lot->lot_code) === null
            && abs($this->dealIn($after, $lot)['investor_usd'] - 150.00) < self::EPS,
            $lot->lot_code . ' realized 150.00 x 100% = 150.00 now in the pool'
        );
    }

    /** 7-8. No terms, no default, no silent exclusion. */
    private function missingTermsBlock(InvestorAllocation $allocation): void
    {
        $lot = $this->tradedLot(['cost' => 400, 'grams' => 4, 'waste' => 0, 'capitalised' => 0,
            'sell_grams' => 4, 'price' => 125]);
        $this->allocateCapital($lot, [[$this->investorA, 400.00]]);
        // Deliberately no investor_share_percent.
        $this->recorder->finaliseExpenses($lot);
        $this->recorder->record($lot, $this->super);

        $a = $allocation->forPeriod($this->period);
        $entry = collect($a['blocked'])->firstWhere('lot_code', $lot->lot_code);

        $this->check(
            'A deal with no recorded investor share blocks the allocation, by name',
            $entry !== null && str_contains($entry['reason'], 'no investor share')
            && $this->refused(fn () => $allocation->assertDistributable($this->period))
            && $this->dealIn($a, $lot) === null,
            $lot->lot_code . ': ' . $entry['reason']
        );

        $this->check(
            'The 100% on other deals is not applied to it as a default',
            $this->dealIn($a, $lot) === null
            && (float) $lot->fresh()->investor_share_percent === 0.0
            && abs($a['gross_investor_pool_usd'] - round(890 + 136 + 248 + 150, 8)) < self::EPS,
            'pool ' . Money::format($a['gross_investor_pool_usd']) . ' contains nothing from '
            . $lot->lot_code . ' though three deals in the period carry 100%'
        );

        // Give it terms, and it joins.
        $lot->forceFill(['investor_share_percent' => 25])->save();
        $after = $allocation->forPeriod($this->period);

        $this->check(
            'Recording its terms lifts the block and applies exactly those terms',
            collect($after['blocked'])->firstWhere('lot_code', $lot->lot_code) === null
            && abs($this->dealIn($after, $lot)['investor_usd'] - 25.00) < self::EPS,
            $lot->lot_code . ' realized 100.00 x 25% = 25.00'
        );
    }

    /** 9. No investor capital recorded: refused, not assumed to be the company's. */
    private function noCapitalBlocks(InvestorAllocation $allocation): void
    {
        $lot = $this->tradedLot(['cost' => 300, 'grams' => 3, 'waste' => 0, 'capitalised' => 0,
            'sell_grams' => 3, 'price' => 120]);
        $lot->forceFill(['investor_share_percent' => 100])->save();
        $this->recorder->finaliseExpenses($lot);
        $this->recorder->record($lot, $this->super);

        $a = $allocation->forPeriod($this->period);
        $entry = collect($a['blocked'])->firstWhere('lot_code', $lot->lot_code);

        $this->allocateCapital($lot, [[$this->investorA, 300.00]]);
        $after = $allocation->forPeriod($this->period);

        $this->check(
            'A deal with no investor capital recorded blocks rather than being treated as the company\'s',
            $entry !== null && str_contains($entry['reason'], 'no investor capital')
            && collect($after['blocked'])->firstWhere('lot_code', $lot->lot_code) === null
            && abs($this->dealIn($after, $lot)['investor_usd'] - 60.00) < self::EPS,
            $lot->lot_code . ': blocked until capital was recorded; then 60.00 x 100%'
        );
    }

    /** 10. The historical deals are attribution, and never enter an accounting pool. */
    private function historicalNotEligible(InvestorAllocation $allocation): void
    {
        $historical = LotResult::where('status', LotResult::STATUS_DISTRIBUTED)->with('lot')->get();

        if ($historical->isEmpty()) {
            $this->check('Historical attribution is not eligible', true, 'no historical deal present to test');

            return;
        }

        // The only period they could appear in is the real one their sales fell
        // in. Ask for that and prove they are listed as attribution, not allocated.
        $realPeriod = AccountingPeriod::covering($historical->first()->closed_at) ?? AccountingPeriod::orderBy('starts_on')->first();
        $a = $allocation->forPeriod($realPeriod);

        $ok = true;
        $detail = [];

        foreach ($historical as $record) {
            $code = $record->lot?->lot_code ?? '?';
            $entry = collect($a['ineligible'])->firstWhere('lot_code', $code);
            $inEligible = collect($a['eligible'])->firstWhere('lot_code', $code) !== null;
            $ok = $ok && ! $inEligible && ($entry === null || str_contains($entry['reason'], 'historical attribution'));
            $detail[] = $code . ' ' . ($entry ? 'listed as historical attribution' : 'not in the period at all');
        }

        $this->check(
            'Historical attribution is never eligible for an accounting allocation',
            $ok && abs($a['gross_investor_pool_usd']) < self::EPS,
            implode('; ', $detail) . '; pool for ' . $realPeriod->code . ' is 0.00'
        );
    }

    /** 11. Account 7000 is an outcome of the allocation, never an input to it. */
    private function noCircularityThrough7000(InvestorAllocation $allocation, RealizedTradingResult $realized, JournalPoster $poster): void
    {
        $before = $allocation->forPeriod($this->period);
        $resultBefore = $realized->forCompany(['period_id' => $this->period->id]);

        // An appropriation posted the way a distribution eventually would post it.
        $poster->post([
            ['account' => '7000', 'debit' => 500.00, 'memo' => 'Investor profit share, self-test'],
            ['account' => '2010', 'credit' => 500.00, 'memo' => 'owed to investors'],
        ], ['date' => $this->date, 'memo' => 'Appropriation, self-test'], $this->super);

        $after = $allocation->forPeriod($this->period);
        $resultAfter = $realized->forCompany(['period_id' => $this->period->id]);

        $this->check(
            'A posting on 7000 changes neither the trading result nor the allocation',
            abs($resultAfter['trading_result_usd'] - $resultBefore['trading_result_usd']) < self::EPS
            && abs($resultAfter['ledger_expenses_usd'] - $resultBefore['ledger_expenses_usd']) < self::EPS
            && abs($after['investor_pool_usd'] - $before['investor_pool_usd']) < self::EPS
            && abs($this->accountBalance('7000') - 500.00) < self::EPS,
            '500.00 on 7000; trading result still ' . Money::format($resultAfter['trading_result_usd'])
            . ', pool still ' . Money::format($after['investor_pool_usd']) . ' - no circle'
        );
    }

    /** 12. The legacy fixed-return engine has no path into this. */
    private function legacyPlansCannotInfluence(InvestorAllocation $allocation): void
    {
        // Code only: the comments in these files are allowed to say what they do
        // not read, which is not the same as reading it.
        $source = (string) php_strip_whitespace((new \ReflectionClass(InvestorAllocation::class))->getFileName());
        $calc = (string) php_strip_whitespace((new \ReflectionClass(\App\GoldTrading\Services\LotResultCalculator::class))->getFileName());

        $static = ! str_contains($source, 'investment_plan') && ! str_contains($source, 'InvestmentPlan')
            && ! str_contains($source, 'profit_percentage') && ! str_contains($source, 'InvestProfitLog')
            && ! str_contains($calc, 'InvestmentPlan') && ! str_contains($calc, 'profit_percentage');

        $runtime = true;
        $note = 'no reference to investment_plans, profit_percentage or profit logs in the allocation code';

        if (Schema::hasTable('investment_plans')) {
            $before = $allocation->forPeriod($this->period);
            $columns = Schema::getColumnListing('investment_plans');
            $row = ['profit_percentage' => 99.0, 'created_at' => now(), 'updated_at' => now()];
            foreach (['name' => 'Self-test plan', 'plan_duration' => 30, 'status' => 1, 'min_amount' => 1, 'max_amount' => 1,
                'return_type' => 1, 'return_period' => 1, 'profit_type' => 1, 'fixed_amount' => 0] as $col => $val) {
                if (in_array($col, $columns, true)) {
                    $row[$col] = $val;
                }
            }
            try {
                DB::table('investment_plans')->insert($row);
                $after = $allocation->forPeriod($this->period);
                $runtime = abs($after['investor_pool_usd'] - $before['investor_pool_usd']) < self::EPS;
                $note .= '; a 99% legacy plan inserted at runtime changed nothing';
            } catch (\Throwable $e) {
                $note .= '; legacy table present but a fixture row could not be inserted (' . substr($e->getMessage(), 0, 60) . ')';
            }
        }

        $this->check('The legacy investment_plans engine cannot influence the allocation', $static && $runtime, $note);
    }

    /** 13. The same books give the same answer. */
    private function deterministic(InvestorAllocation $allocation): void
    {
        $one = $allocation->forPeriod($this->period);
        $two = $allocation->forPeriod($this->period);

        $this->check(
            'Repeated calculation is deterministic',
            $one === $two,
            'two runs produced identical output, ' . count($one['eligible']) . ' eligible deals, pool '
            . Money::exact($one['investor_pool_usd'])
        );
    }

    /** 14. A distribution follows a close. */
    private function openPeriodNotDistributable(InvestorAllocation $allocation, PeriodCloseService $close): void
    {
        $open = $allocation->forPeriod($this->period);
        $openRefusal = count(array_filter($open['refusals'], fn ($r) => str_contains($r, 'is open'))) === 1;

        // The unsold deal does not block a close; the interim one was recorded.
        $close->close($this->period, $this->super, 'February 2025 month end, self-test');
        $closed = $allocation->forPeriod($this->period->fresh());

        $this->check(
            'An open period is not distributable; a closed one with no other refusal is',
            $openRefusal && $open['distributable'] === false
            && $closed['refusals'] === [] && $closed['distributable'] === true
            && abs($closed['investor_pool_usd'] - $open['investor_pool_usd']) < self::EPS,
            'open: refused as not closed; closed: distributable, pool ' . Money::format($closed['investor_pool_usd'])
            . ' - and still nothing was moved'
        );
    }

    /** 15. A loss is refused with the exact words, never a negative credit. */
    private function negativePoolRefused(InvestorAllocation $allocation, CashAccountService $accounts, CashService $cash): void
    {
        $period = AccountingPeriod::create([
            'code' => 'ST-2025-03', 'starts_on' => '2025-03-01', 'ends_on' => '2025-03-31', 'status' => AccountingPeriod::OPEN,
        ]);
        $saved = [$this->period, $this->date];
        [$this->period, $this->date] = [$period, '2025-03-10'];

        // Bought at 100/g, sold at 80/g: realized -200.00.
        $lot = $this->finalizedDeal(['cost' => 1000, 'grams' => 10, 'waste' => 0, 'capitalised' => 0,
            'sell_grams' => 10, 'price' => 80, 'share' => 100, 'capital' => [[$this->investorA, 1000.00]]]);

        $a = $allocation->forPeriod($period);

        $this->check(
            'A negative pool is refused with the loss-policy message, and no negative share exists',
            abs($a['investor_pool_usd'] + 200.00) < self::EPS
            && $a['loss_policy_status'] === InvestorAllocation::NEGATIVE_POOL
            && in_array(InvestorAllocation::NEGATIVE_POOL, $a['refusals'], true)
            && $a['distributable'] === false
            && $this->refused(fn () => $allocation->assertDistributable($period)),
            $lot->lot_code . ' realized -200.00; "' . InvestorAllocation::NEGATIVE_POOL . '"'
        );

        [$this->period, $this->date] = $saved;
    }

    /** 16. Nothing realized, nothing owed, no error. */
    private function zeroPool(InvestorAllocation $allocation, CashAccountService $accounts, CashService $cash): void
    {
        $period = AccountingPeriod::create([
            'code' => 'ST-2025-04', 'starts_on' => '2025-04-01', 'ends_on' => '2025-04-30', 'status' => AccountingPeriod::OPEN,
        ]);
        $saved = [$this->period, $this->date];
        [$this->period, $this->date] = [$period, '2025-04-10'];

        // Bought at 100/g, sold at 100/g: realized exactly 0.00.
        $lot = $this->finalizedDeal(['cost' => 1000, 'grams' => 10, 'waste' => 0, 'capitalised' => 0,
            'sell_grams' => 10, 'price' => 100, 'share' => 100, 'capital' => [[$this->investorA, 1000.00]]]);

        $a = $allocation->forPeriod($period);

        $this->check(
            'A zero result gives a zero pool: nothing to distribute, and no refusal about it',
            abs($a['investor_pool_usd']) < self::EPS
            && $a['loss_policy_status'] === 'not a loss'
            && ! in_array(InvestorAllocation::NEGATIVE_POOL, $a['refusals'], true)
            && $this->dealIn($a, $lot) !== null,
            $lot->lot_code . ' realized 0.00; pool 0.00; loss policy: ' . $a['loss_policy_status']
        );

        [$this->period, $this->date] = $saved;
    }

    /** 17. The reserve is a declared policy, currently zero. */
    private function reserveIsPolicy(InvestorAllocation $allocation): void
    {
        $default = $allocation->forPeriod($this->period->fresh());

        config(['accounting.allocation.reserve_percent' => 10]);
        $withReserve = $allocation->forPeriod($this->period->fresh());
        config(['accounting.allocation.reserve_percent' => 0]);

        $this->check(
            'Reserve is zero by policy, and declared in one place should it ever be agreed',
            (float) $default['reserve_percent'] === 0.0 && abs($default['reserve_usd']) < self::EPS
            && abs($withReserve['reserve_usd'] - round($default['gross_investor_pool_usd'] * 0.10, 8)) < self::EPS
            && abs($withReserve['investor_pool_usd'] - round($default['gross_investor_pool_usd'] * 0.90, 8)) < self::EPS
            && (float) config('accounting.allocation.reserve_percent') === 0.0,
            'default 0%; a 10% policy would retain ' . Money::format($withReserve['reserve_usd'])
            . ' of ' . Money::format($default['gross_investor_pool_usd']) . ' - restored to 0'
        );
    }

    /** 18. The books still balance. */
    private function trialBalance(TrialBalance $trialBalance): void
    {
        $tb = $trialBalance->build();

        $this->check('Trial balance still balances', $tb['balanced'],
            'debits ' . Money::format($tb['total_debits']) . ' credits ' . Money::format($tb['total_credits'])
            . ' difference ' . Money::exact($tb['difference']));
    }

    /** 19-22. Nothing outside this run moved — checked after the rollback. */
    private function untouched(array $investorsBefore, float $suspenseBefore, array $realPeriods, array $historicalBefore): void
    {
        $after = $this->investorSnapshot();

        $this->check(
            'No investor wallet, ledger entry or distribution moved: calculation only',
            $investorsBefore === $after,
            'wallets ' . Money::exact($after['balance']) . ' / ' . Money::exact($after['profit'])
            . ', ' . $after['entries'] . ' ledger entries, ' . $after['distributions'] . ' distributions, '
            . $after['users'] . ' users - unchanged'
        );

        $this->check('Suspense is untouched and D2/D3 remain unresolved',
            abs($this->accountBalance('1090') - $suspenseBefore) < self::EPS && abs($this->accountBalance('1090')) < self::EPS,
            'account 1090 holds ' . Money::exact($this->accountBalance('1090')));

        $now = AccountingPeriod::orderBy('code')->get(['code', 'status', 'close_reference'])->toArray();

        $this->check('The real accounting periods are exactly as they were', $realPeriods === $now,
            implode(', ', array_map(fn ($p) => $p['code'] . ' ' . $p['status'], $now)) . ' - none closed');

        $historicalNow = LotResult::where('status', LotResult::STATUS_DISTRIBUTED)
            ->orderBy('gold_lot_id')->get(['gold_lot_id', 'net_profit_usd', 'investor_profit_usd', 'status'])->toArray();

        $this->check('The historical deals\' attribution records are exactly as they were',
            $historicalBefore === $historicalNow,
            count($historicalNow) . ' historical record(s) byte-identical');
    }

    // ------------------------------------------------------------------ fixtures

    /** A deal bought, refined, sold, costed, recorded, with capital and terms. */
    private function finalizedDeal(array $spec): GoldLot
    {
        $lot = $this->tradedLot($spec);
        $this->allocateCapital($lot, $spec['capital']);

        if (isset($spec['share'])) {
            $lot->forceFill(['investor_share_percent' => $spec['share']])->save();
        }

        $this->recorder->finaliseExpenses($lot);
        $this->recorder->record($lot, $this->super);

        return $lot->fresh();
    }

    /** Capital attribution only: which investor's money funded the deal. No balance moves. */
    private function allocateCapital(GoldLot $lot, array $capital): void
    {
        foreach ($capital as $row) {
            [$user, $amount] = $row;
            CapitalAllocation::create([
                'gold_lot_id' => $lot->id, 'user_id' => $user->id, 'amount_usd' => $amount,
                'share_percent' => $row[2] ?? null, 'allocated_at' => $this->date,
                'status' => CapitalAllocation::STATUS_ALLOCATED, 'locked_balance' => false,
                'locked_from_balance_usd' => 0, 'locked_from_profit_usd' => 0,
                'notes' => 'self-test attribution; no wallet was debited',
            ]);
        }
    }

    private function tradedLot(array $spec): GoldLot
    {
        $grams = $spec['grams'];
        $cost = $spec['cost'];
        $perGram = round($cost / $grams, 8);

        $lot = GoldLot::create([
            'lot_code' => 'AL-SELFTEST-' . substr(md5(uniqid('', true)), 0, 8),
            'purchase_date' => $this->date, 'project_name' => 'Allocation self-test', 'location' => 'Test',
            'gross_grams' => $grams, 'purchase_currency' => 'USD', 'price_per_gram_local' => $perGram,
            'fx_rate_to_usd' => 1, 'price_per_gram_usd' => $perGram,
            'total_cost_local' => $cost, 'total_cost_usd' => $cost, 'status' => 'purchased',
        ]);
        $this->gold->purchase($lot, $this->bank, ['date' => $this->date]);

        $waste = $spec['waste'];
        $processing = GoldProcessing::create([
            'gold_lot_id' => $lot->id, 'processed_at' => $this->date, 'method' => 'Self-test refining',
            'input_grams' => $grams, 'waste_grams' => $waste,
            'waste_percent' => $grams > 0 ? round(($waste / $grams) * 100, 4) : 0,
            'output_grams' => round($grams - $waste, 4), 'output_purity' => '24K',
            'cost_usd' => 0.0, 'cost_capitalised' => true,
        ]);
        $this->gold->refine($processing, null, ['date' => $this->date]);

        if (($spec['capitalised'] ?? 0) > 0) {
            $charge = $this->expenses->draft(['expense_date' => $this->date, 'category' => 'refining',
                'description' => 'Refinery charge, self-test', 'amount_local' => $spec['capitalised'],
                'capitalised' => true, 'gold_lot_id' => $lot->id]);
            $this->expenses->submit($charge);
            $this->expenses->approve($charge, $this->super);
            $this->expenses->post($charge, $this->bank, ['date' => $this->date]);
        }

        $sale = GoldSale::create([
            'sale_code' => $lot->lot_code . '-S1', 'gold_lot_id' => $lot->id, 'sale_date' => $this->date,
            'buyer_name' => 'Self-test buyer', 'grams_sold' => $spec['sell_grams'],
            'price_per_gram_usd' => $spec['price'], 'gross_proceeds_usd' => round($spec['sell_grams'] * $spec['price'], 8),
            'settlement_currency' => 'USD', 'fx_rate_to_usd' => 1, 'status' => GoldSale::STATUS_SETTLED,
        ]);
        $this->gold->sell($sale, $this->bank, ['date' => $this->date]);

        foreach ($spec['expenses'] ?? [] as [$category, $amount]) {
            $expense = $this->expenses->draft(['expense_date' => $this->date, 'category' => $category,
                'description' => ucfirst($category) . ', self-test', 'amount_local' => $amount, 'gold_lot_id' => $lot->id]);
            $this->expenses->submit($expense);
            $this->expenses->approve($expense, $this->super);
            $this->expenses->post($expense, $this->bank, ['date' => $this->date]);
        }

        return $lot->fresh();
    }

    /** A second investor that exists only inside this transaction. */
    private function tempInvestor(string $username): User
    {
        $user = new User();
        $user->forceFill([
            'firstname' => 'Self-test', 'lastname' => 'Investor', 'username' => $username,
            'email' => $username . '@selftest.invalid', 'password' => bcrypt(bin2hex(random_bytes(8))), 'status' => 1,
        ])->save();

        return $user;
    }

    private function dealIn(array $allocation, GoldLot $lot): ?array
    {
        return collect($allocation['eligible'])->firstWhere('lot_code', $lot->lot_code);
    }

    private function accountBalance(string $code): float
    {
        $account = Account::where('code', $code)->first();

        if (! $account) {
            return 0.0;
        }

        $sums = DB::table('journal_lines')->where('account_id', $account->id)
            ->selectRaw('SUM(debit) as d, SUM(credit) as c')->first();
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
            'users'         => (int) DB::table('users')->count(),
        ];
    }

    private function superAdmin(): Admin
    {
        $admin = Admin::all()->first(fn (Admin $a) => $a->isSuperAdmin());

        if (! $admin) {
            throw new \RuntimeException('No Super Admin exists to run the allocation tests with.');
        }

        return $admin;
    }

    private function refused(callable $action): bool
    {
        try {
            $action();
        } catch (\Throwable) {
            return true;
        }

        return false;
    }

    private function check(string $name, bool $passed, string $detail = ''): void
    {
        $this->results[] = [$name, $passed, $detail];
    }
}
