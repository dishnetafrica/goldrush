<?php

namespace App\Console\Commands;

use App\Accounting\Models\Account;
use App\Accounting\Models\AccountingPeriod;
use App\Accounting\Models\CashAccount;
use App\Accounting\Models\Journal;
use App\Accounting\Models\PeriodClosePack;
use App\Accounting\Models\ReportView;
use App\Accounting\Reports\ClosePackBuilder;
use App\Accounting\Reports\Control;
use App\Accounting\Reports\ControlReports;
use App\Accounting\Reports\CsvExport;
use App\Accounting\Reports\FinancialStatements;
use App\Accounting\Reports\InvestorLiabilityReport;
use App\Accounting\Reports\ReportScope;
use App\Accounting\Reports\TradingReports;
use App\Accounting\Security\AccountingPermission;
use App\Accounting\Services\CashAccountService;
use App\Accounting\Services\CashService;
use App\Accounting\Services\ExpenseWorkflow;
use App\Accounting\Services\GoldTradingPoster;
use App\Accounting\Services\InvestorAllocation;
use App\Accounting\Services\InvestorDistributor;
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
use App\Investor\Models\LedgerEntry;
use App\Investor\Support\Branding;
use App\Investor\Support\Money;
use App\Models\Admin\Admin;
use App\Models\Admin\Currency;
use App\Models\User;
use App\Models\UserWallet;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The Phase 3G reporting acceptance run.
 *
 * Builds months of its own with temporary investors, trades, closes and
 * distributes them through the frozen 3C-3F services, and then renders every
 * report against that ledger and checks each figure against the account it
 * must have come from. Reports are read-only, so the run also proves that
 * rendering all of them, and exporting them, changes nothing. Everything rolls
 * back; the real ledger, the real September and the real investor are read to
 * prove they did not move, and never written.
 */
class ReportSelfTestCommand extends Command
{
    protected $signature = 'report:selftest {--skip-regression : reporting tests only}';

    protected $description = 'Verify the Phase 3G reports derive every figure from the posted ledger and change nothing';

    private array $results = [];
    private const EPS = 0.00000001;

    private ?AccountingPeriod $traded = null;
    private ?AccountingPeriod $declared = null;
    private ?AccountingPeriod $later = null;
    private string $date;
    private ExpenseWorkflow $expenses;
    private GoldTradingPoster $gold;
    private RealizedResultRecorder $recorder;
    private ?CashAccount $bank = null;
    private ?Admin $super = null;
    private ?User $investorA = null;
    private ?User $investorB = null;
    private ?Currency $currency = null;
    private ?GoldLot $lotA = null;
    private ?GoldLot $lotB = null;
    private float $pool = 0.0;
    private array $byInvestor = [];

    public function handle(
        FinancialStatements $statements, TradingReports $trading, InvestorLiabilityReport $investors,
        ControlReports $controls, ClosePackBuilder $packs, CsvExport $csv,
        InvestorDistributor $distributor, InvestorAllocation $allocation, RealizedTradingResult $realized,
        RealizedResultRecorder $recorder, PeriodCloseService $close, ExpenseWorkflow $expenses,
        GoldTradingPoster $gold, JournalPoster $poster, CashAccountService $accounts, CashService $cash,
        TrialBalance $trialBalance,
    ): int {
        $this->results = [];

        $this->line('Reporting self-test (Phase 3G)');
        $this->line(str_repeat('-', 60));

        $this->expenses = $expenses;
        $this->gold = $gold;
        $this->recorder = $recorder;

        $before = $this->outsideSnapshot();
        $real = $this->realLedgerTotals();

        DB::beginTransaction();

        try {
            $this->super = $this->superAdmin();
            $this->currency = Currency::where('default', true)->firstOrFail();
            $this->investorA = $this->tempInvestor('st-rep-a');
            $this->investorB = $this->tempInvestor('st-rep-b');

            $this->traded = AccountingPeriod::create(['code' => 'ST-2025-05', 'starts_on' => '2025-05-01', 'ends_on' => '2025-05-31', 'status' => AccountingPeriod::OPEN]);
            $this->declared = AccountingPeriod::create(['code' => 'ST-2025-06', 'starts_on' => '2025-06-01', 'ends_on' => '2025-06-30', 'status' => AccountingPeriod::OPEN]);
            $this->later = AccountingPeriod::create(['code' => 'ST-2025-07', 'starts_on' => '2025-07-01', 'ends_on' => '2025-07-31', 'status' => AccountingPeriod::OPEN]);
            $this->date = '2025-05-10';

            $this->bank = $accounts->create(['name' => 'Reporting Bank', 'type' => CashAccount::TYPE_BANK, 'code' => 'RP-BANK']);
            $cash->receipt($this->bank, 30000.00, '3000', ['date' => $this->date, 'memo' => 'Self-test funding']);

            // Two finalized deals: A revenue 2,990 / basis 2,100 (2,000 + 100 capitalised) -> 890 at 100% to A;
            // B revenue 1,200 / basis 800 -> 400 at 70%, split A/B under their recorded shares.
            $this->lotA = $this->finalizedDeal(['cost' => 2000, 'grams' => 25, 'waste' => 2, 'capitalised' => 100,
                'sell_grams' => 23, 'price' => 130, 'share' => 100, 'capital' => [[$this->investorA, 2000.00]]]);
            $this->lotB = $this->finalizedDeal(['cost' => 800, 'grams' => 10, 'waste' => 0, 'capitalised' => 0,
                'sell_grams' => 10, 'price' => 120, 'share' => 70,
                'capital' => [[$this->investorA, 480.00], [$this->investorB, 320.00, 50.0]]]);

            $close->close($this->traded, $this->super, 'May 2025 month end, reporting self-test');

            $tradedBefore = $statements->profitAndLoss(ReportScope::forPeriod($this->traded));
            $declaredBefore = $statements->profitAndLoss(ReportScope::forPeriod($this->declared));

            $preview = $allocation->forPeriod($this->traded);
            $this->pool = $preview['investor_pool_usd'];
            $this->byInvestor = $preview['by_investor'];

            $distributor->distribute($this->traded, $this->super, ['date' => '2025-06-05', 'note' => 'reporting self-test']);

            $this->trialBalanceTests($statements);
            $this->profitAndLossTests($statements, $realized, $tradedBefore, $declaredBefore);
            $this->balanceSheetTests($statements);
            $this->investorLiabilityTests($investors, $real);
            $this->goldTradingTests($trading, $statements);
            $this->cashFlowTests($statements, $cash, $accounts, $poster);
            $this->permissionTests($packs);
            $this->auditTests();
            $this->closePackTests($packs);
            $this->emptyLedgerTests($statements, $trading);
            $this->legacyTests($statements, $investors, $trading);
            $this->readOnlyTests($statements, $trading, $investors, $controls, $packs, $csv);
            $this->trialBalance($trialBalance);
        } catch (\Throwable $e) {
            $this->check('Unexpected failure', false, $e->getMessage() . ' @ ' . basename($e->getFile()) . ':' . $e->getLine());
        } finally {
            DB::rollBack();
        }

        $this->untouched($before);

        $this->table(['#', 'Test', 'Result', 'Detail'],
            array_map(fn ($r, $i) => [$i + 1, $r[0], $r[1] ? 'PASS' : 'FAIL', $r[2]], $this->results, array_keys($this->results)));

        $failed = array_filter($this->results, fn ($r) => ! $r[1]);
        $this->line('  ' . (count($this->results) - count($failed)) . ' of ' . count($this->results) . ' passed.');
        $this->line('  Everything was rolled back; no journal, distribution, credit, wallet change, audit row or close pack remains.');

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
                'Phase 3F allocation' => ['allocation:selftest', ['--skip-regression' => true]],
                'Phase 3F distribution' => ['distribution:selftest', ['--skip-regression' => true]],
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

    // ------------------------------------------------------------ 1. trial balance

    private function trialBalanceTests(FinancialStatements $statements): void
    {
        $tb = $statements->trialBalance(ReportScope::forPeriod($this->traded));
        $consistent = collect($tb['rows'])->every(fn ($r) => $r['consistent']);

        $id = $this->damage($this->traded, '6100', 0.01);
        $damaged = $statements->trialBalance(ReportScope::forPeriod($this->traded));
        DB::table('journal_lines')->where('id', $id)->delete();

        $this->check(
            'Trial balance: opening + movements = closing per account, balanced; an injected 0.01 raw line reports UNBALANCED with the difference',
            $tb['balanced'] && $consistent && $tb['scope']['status'] === 'FINAL - period closed as ' . $this->traded->fresh()->close_reference
            && ! $damaged['balanced'] && abs($damaged['totals']['difference'] - 0.01) < self::EPS
            && $damaged['controls'][0]['status'] === Control::EXCEPTION
            && $statements->trialBalance(ReportScope::forPeriod($this->traded))['balanced'],
            'debits ' . Money::exact($tb['totals']['debits']) . ' = credits ' . Money::exact($tb['totals']['credits'])
            . '; damaged: difference ' . Money::exact($damaged['totals']['difference']) . ' ' . $damaged['controls'][0]['status']
        );
    }

    // ---------------------------------------------------------- 2-4, 27. P&L

    private function profitAndLossTests(FinancialStatements $statements, RealizedTradingResult $realized, array $tradedBefore, array $declaredBefore): void
    {
        $traded = $statements->profitAndLoss(ReportScope::forPeriod($this->traded));
        $company = $realized->forCompany(['period_id' => $this->traded->id]);

        $this->check(
            'P&L trading result equals RealizedTradingResult::forCompany() exactly',
            abs($traded['trading']['realized_result_usd'] - $company['operating_result_usd']) < self::EPS
            && abs($traded['trading']['revenue_usd'] - 4190.00) < self::EPS
            && abs($traded['trading']['cogs_usd'] - 2900.00) < self::EPS
            && abs($traded['trading']['expenses_usd']) < self::EPS
            && abs($traded['trading']['realized_result_usd'] - 1290.00) < self::EPS
            && $traded['controls'][3]['passed'],
            'revenue 4,190.00 - COGS 2,900.00 - expenses 0.00 = ' . Money::format($traded['trading']['realized_result_usd']) . ' = service ' . Money::format($company['operating_result_usd'])
        );

        $declared = $statements->profitAndLoss(ReportScope::forPeriod($this->declared));
        $dr7000 = $this->accountBalance('7000');

        $this->check(
            'Dr 7000 posted: the trading result of both periods is unchanged and the appropriation section shows it',
            abs($declared['trading']['realized_result_usd'] - $declaredBefore['trading']['realized_result_usd']) < self::EPS
            && abs($traded['trading']['realized_result_usd'] - $tradedBefore['trading']['realized_result_usd']) < self::EPS
            && abs($declared['appropriation']['amount_usd'] - $this->pool) < self::EPS
            && abs($dr7000 - $this->pool) < self::EPS
            && abs($declared['result_after_appropriation_usd'] - ($declared['trading']['realized_result_usd'] - $this->pool)) < self::EPS
            && $declared['controls'][4]['passed'],
            'ST-2025-06 trading ' . Money::format($declared['trading']['realized_result_usd']) . ' before and after; 7000 '
            . Money::exact($declared['appropriation']['amount_usd']) . '; result after appropriation ' . Money::format($declared['result_after_appropriation_usd'])
        );

        $listed = $declared['appropriation']['distributions'];
        $elsewhere = $traded['appropriation']['appropriated_elsewhere'];

        $this->check(
            'The appropriation appears in the period declared (June) citing May, and May shows only a footnote with 7000 at 0.00',
            count($listed) === 1 && $listed[0]['source_period'] === 'ST-2025-05' && $listed[0]['declared_in'] === 'ST-2025-06'
            && $listed[0]['journal_date'] === '2025-06-05' && str_contains($listed[0]['caption'], 'ST-2025-05')
            && abs($traded['appropriation']['amount_usd']) < self::EPS && $traded['appropriation']['distributions'] === []
            && count($elsewhere) === 1 && $elsewhere[0]['declared_in'] === 'ST-2025-06',
            $listed[0]['caption'] . ' dated ' . $listed[0]['journal_date'] . '; May footnote: appropriated in ' . $elsewhere[0]['declared_in']
        );

        $bySource = $statements->profitAndLoss(new ReportScope($this->declared, null, null, null, 'ST-2025-05'));
        $byOther = $statements->profitAndLoss(new ReportScope($this->declared, null, null, null, 'ST-2025-06'));

        $this->check(
            'Source-period filter: June filtered to source ST-2025-05 lists the distribution, filtered to ST-2025-06 lists none, and the trading result is identical either way',
            count($bySource['appropriation']['distributions']) === 1 && $byOther['appropriation']['distributions'] === []
            && abs($bySource['trading']['realized_result_usd'] - $declared['trading']['realized_result_usd']) < self::EPS
            && abs($byOther['trading']['realized_result_usd'] - $declared['trading']['realized_result_usd']) < self::EPS
            && abs($byOther['appropriation']['amount_usd'] - $this->pool) < self::EPS,
            'the filter narrows the listing only; 7000 and the trading result are the GL\'s whatever the filter'
        );
    }

    // ---------------------------------------------------------- 5-7. balance sheet

    private function balanceSheetTests(FinancialStatements $statements): void
    {
        $scope = ReportScope::fromInput(['as_at' => '2025-06-30']);
        $bs = $statements->balanceSheet($scope);

        $id = $this->damage($this->traded, '6100', 0.01);
        $damaged = $statements->balanceSheet($scope);
        DB::table('journal_lines')->where('id', $id)->delete();

        $this->check(
            'Balance sheet: assets = liabilities + equity + current result; a forced imbalance is reported as a control exception',
            $bs['balanced'] && abs($bs['totals']['difference_usd']) < self::EPS
            && ! $damaged['balanced'] && $damaged['controls'][0]['status'] === Control::EXCEPTION
            && abs($damaged['totals']['difference_usd']) > 0,
            'assets ' . Money::exact($bs['totals']['assets_usd']) . ' = ' . Money::exact($bs['totals']['right_side_usd'])
            . '; damaged difference ' . Money::exact($damaged['totals']['difference_usd']) . ' ' . $damaged['controls'][0]['status']
        );

        $liab = collect($bs['liabilities'])->keyBy('code');

        $this->check(
            'Balance sheet: 2000 and 2010 are separate lines and never merged',
            $liab->has('2000') && $liab->has('2010')
            && abs($liab['2010']['balance'] - $this->pool) < self::EPS
            && abs($bs['investor_liability']['profit_2010_usd'] - $this->pool) < self::EPS
            && abs($bs['investor_liability']['capital_2000_usd'] - $liab['2000']['balance']) < self::EPS,
            '2000 ' . Money::exact($liab['2000']['balance']) . ' / 2010 ' . Money::exact($liab['2010']['balance'])
        );

        $suspense = collect($bs['assets'])->firstWhere('code', '1090');

        $this->check(
            'Balance sheet: 1090 Suspense is its own line with the D2/D3 caption even at 0.00',
            $suspense !== null && abs($suspense['balance']) < self::EPS
            && str_contains($bs['suspense']['caption'], 'D2/D3') && abs($bs['suspense']['balance']) < self::EPS,
            '1090 ' . Money::exact($suspense['balance']) . ': ' . $bs['suspense']['caption']
        );
    }

    // ------------------------------------------- 14-16, 18, 26, 30. investor liability

    private function investorLiabilityTests(InvestorLiabilityReport $investors, array $real): void
    {
        $il = $investors->company(ReportScope::forPeriod($this->declared));
        $rows = collect($il['rows'])->keyBy(fn ($r) => $r['investor']['username']);
        $a = $rows['st-rep-a'];
        $b = $rows['st-rep-b'];
        $controls = collect($il['controls'])->keyBy('key');

        $this->check(
            'Investor liability: each investor\'s ledger profit equals their allocation; 2010 credited in June equals the profit credited in June; wallet difference 0',
            abs($a['closing']['profit'] - $this->byInvestor[$this->investorA->id]) < self::EPS
            && abs($b['closing']['profit'] - $this->byInvestor[$this->investorB->id]) < self::EPS
            && abs($a['wallet']['difference']) < self::EPS && abs($b['wallet']['difference']) < self::EPS
            && $controls['il_profit_movement']['passed']
            && abs($controls['il_profit_movement']['left'] - $this->pool) < self::EPS,
            'A ' . Money::exact($a['closing']['profit']) . ', B ' . Money::exact($b['closing']['profit']) . '; 2010 movement '
            . Money::exact($controls['il_profit_movement']['left']) . ' = credits ' . Money::exact($controls['il_profit_movement']['right'])
        );

        $this->check(
            'Investor liability: every distribution line has a ledger entry of the same amount',
            $controls['il_distribution_lines']['passed'] && count($il['distribution_lines']) === 2
            && collect($il['distribution_lines'])->every(fn ($l) => $l['matched']),
            implode('; ', array_map(fn ($l) => $l['user'] . ' ' . Money::exact($l['amount_usd']) . ' -> ' . $l['ledger_reference'], $il['distribution_lines']))
        );

        $leak = [];
        array_walk_recursive($il['rows'], function ($v, $k) use (&$leak) {
            if (preg_match('/gram|lot_code|gold_lot|supplier/i', (string) $k)) {
                $leak[] = $k;
            }
        });
        $json = json_encode($il['rows']);

        $this->check(
            'Investor liability: no grams, lots or ownership appear against any investor',
            $leak === [] && ! str_contains($json, 'lot_code') && ! str_contains($json, 'grams'),
            'no gram, lot or supplier field in any investor row'
        );

        // The real ledger (Bhavin) is in the same database, so the to-date view,
        // the one an admin opens today, carries the D2/D3 gap in its balance
        // controls. It must be shown and named, not tuned away.
        $toDate = collect($investors->company(new ReportScope())['controls'])->keyBy('key');
        $profitCtl = $toDate['il_profit_balance'];
        $capitalCtl = $toDate['il_capital_balance'];

        $this->check(
            'Real data pre-backfill G1: the 2010 control REPORTS the historical ledger profit difference with the D2/D3 caption',
            $real['profit'] > self::EPS
                ? ($profitCtl['status'] === Control::PRE_BACKFILL && abs(abs($profitCtl['difference']) - $real['profit']) < self::EPS
                    && $profitCtl['caption'] === Control::PRE_BACKFILL_CAPTION)
                : $profitCtl['passed'],
            'GL 2010 ' . Money::exact($profitCtl['left']) . ' vs ledger profit ' . Money::exact($profitCtl['right']) . ' = '
            . Money::exact($profitCtl['difference']) . ' ' . $profitCtl['status'] . ' (real ledger profit ' . Money::exact($real['profit']) . ')'
        );

        $this->check(
            'Real data pre-backfill G2: the 2000 control REPORTS the historical ledger capital difference as PRE-BACKFILL',
            $real['capital'] > self::EPS
                ? ($capitalCtl['status'] === Control::PRE_BACKFILL && abs(abs($capitalCtl['difference']) - $real['capital']) < self::EPS
                    && $capitalCtl['caption'] === Control::PRE_BACKFILL_CAPTION)
                : $capitalCtl['passed'],
            'GL 2000 ' . Money::exact($capitalCtl['left']) . ' vs ledger capital ' . Money::exact($capitalCtl['right']) . ' = '
            . Money::exact($capitalCtl['difference']) . ' ' . $capitalCtl['status']
        );

        $own = $investors->forInvestor($this->investorA, ReportScope::forPeriod($this->declared));
        $ownJson = json_encode($own);
        $extraKeys = array_diff(array_keys($own), InvestorLiabilityReport::INVESTOR_VIEW_KEYS);

        $this->check(
            'Admin vs investor boundary: the investor\'s own view holds only their row; no other investor, no GL, no company total, no lot',
            $extraKeys === [] && ! str_contains($ownJson, 'st-rep-b') && ! str_contains($ownJson, '"gl"')
            && ! str_contains($ownJson, 'capital_2000') && ! str_contains($ownJson, 'lot_code') && ! str_contains($ownJson, 'grams')
            && ! str_contains($ownJson, 'totals') && ! str_contains($ownJson, 'supplier')
            && count($own['distributions']) === 1 && abs($own['distributions'][0]['amount_usd'] - $this->byInvestor[$this->investorA->id]) < self::EPS
            && abs($own['closing']['profit'] - $this->byInvestor[$this->investorA->id]) < self::EPS,
            'keys ' . implode(',', array_keys($own)) . '; one distribution of ' . Money::exact($own['distributions'][0]['amount_usd'])
        );
    }

    // ---------------------------------------------- 11-13, 17, 29. gold trading

    private function goldTradingTests(TradingReports $trading, FinancialStatements $statements): void
    {
        $g = $trading->goldTrading(new ReportScope(lot: $this->lotA->lot_code));
        $a = $g['lots'][0];

        $this->check(
            'Gold trading: the D4 deal shows basis 2,100.00 (2,000 + 100 capitalised) and expenses 0.00; nothing counted twice',
            abs($a['cost']['basis_usd'] - 2100.00) < self::EPS && abs($a['cost']['capitalised_usd'] - 100.00) < self::EPS
            && abs($a['result']['expenses_usd']) < self::EPS && $a['result']['expenses'] === []
            && abs($a['cost']['basis_usd'] - ($a['cost']['purchase_usd'] + $a['cost']['capitalised_usd'])) < self::EPS
            && abs($a['cost']['gl_inventory_usd']) < self::EPS,
            'basis ' . Money::format($a['cost']['basis_usd']) . ', expenses column ' . Money::format($a['result']['expenses_usd']) . ', 6010 absent'
        );

        $all = $trading->goldTrading(new ReportScope());
        $cogs = collect($all['controls'])->filter(fn ($c) => str_starts_with($c['key'], 'cogs_'));

        $this->check(
            'Gold trading: per-lot GL 5000 equals LotCostBasis COGS and InventoryValuation::divergence() agrees',
            $cogs->count() >= 2 && $cogs->every(fn ($c) => $c['passed'])
            && collect($all['lots'])->every(fn ($l) => $l['divergence']['agrees']),
            $cogs->count() . ' deal(s): ' . $cogs->map(fn ($c) => Money::exact($c['left']))->implode(', ')
        );

        // A deal still holding gold, in a later month.
        [$this->traded, $this->date] = [$this->later, '2025-07-10'];
        $c = $this->tradedLot(['cost' => 800, 'grams' => 10, 'waste' => 0, 'capitalised' => 0, 'sell_grams' => 4, 'price' => 120]);
        [$this->traded, $this->date] = [AccountingPeriod::where('code', 'ST-2025-05')->first(), '2025-05-10'];

        $cReport = $trading->goldTrading(new ReportScope(lot: $c->lot_code))['lots'][0];
        $aReport = $trading->goldTrading(new ReportScope(lot: $this->lotA->lot_code))['lots'][0];

        $this->check(
            'Gold trading: an interim deal carries its qualification; a final deal shows the recorded figures equal to the GL',
            ! $cReport['result']['is_final'] && $cReport['result']['stage'] === 'trading in progress' && $cReport['result']['qualification'] !== null
            && $cReport['recorded'] === null
            && $aReport['recorded'] !== null && abs($aReport['recorded']['net_profit_usd'] - $aReport['result']['net_usd']) < self::EPS
            && abs($aReport['recorded']['net_profit_usd'] - 890.00) < self::EPS,
            $c->lot_code . ': ' . $cReport['result']['qualification'] . '; ' . $this->lotA->lot_code . ' recorded ' . Money::format($aReport['recorded']['net_profit_usd'])
        );

        // The closed period, then the same books damaged by a raw line against
        // the recorded deal: the snapshot control and the recorded-vs-GL control
        // must both raise an exception, and neither may "correct" anything.
        $pc = $trading->periodClose($this->traded);
        $recordedBefore = (float) LotResult::where('gold_lot_id', $this->lotA->id)->value('net_profit_usd');

        $id = $this->damage($this->traded, '6100', 5.00, $this->lotA->id);
        $pcDamaged = $trading->periodClose($this->traded);
        $gDamaged = $trading->goldTrading(new ReportScope(lot: $this->lotA->lot_code))['lots'][0];
        $recordedAfter = (float) LotResult::where('gold_lot_id', $this->lotA->id)->value('net_profit_usd');
        DB::table('journal_lines')->where('id', $id)->delete();

        $snapFailed = collect($pcDamaged['snapshot_checks'])->filter(fn ($c) => ! $c['passed']);
        $recCtl = collect($gDamaged['controls'])->firstWhere('key', 'rec_' . $this->lotA->lot_code);

        $this->check(
            'Period close report: snapshot equals the ledger for a closed period; a raw line posted after the close is reported as a control exception',
            collect($pc['snapshot_checks'])->every(fn ($c) => $c['passed']) && count($pc['snapshot_checks']) === 6
            && $pc['period']['is_closed'] && $pc['period']['close_reference'] !== null
            && $snapFailed->count() >= 2 && $snapFailed->every(fn ($c) => $c['status'] === Control::EXCEPTION),
            count($pc['snapshot_checks']) . ' figures agree; damaged: ' . $snapFailed->count() . ' ' . Control::EXCEPTION
        );

        $this->check(
            'GL-vs-recorded divergence: a recorded deal whose GL figure moved raises CONTROL EXCEPTION; the recorded artifact is not touched',
            $recCtl !== null && $recCtl['status'] === Control::EXCEPTION && abs($recCtl['difference'] - 5.00) < self::EPS
            && abs($recordedAfter - $recordedBefore) < self::EPS
            && collect($trading->goldTrading(new ReportScope(lot: $this->lotA->lot_code))['lots'][0]['controls'])->every(fn ($c) => $c['passed']),
            'recorded ' . Money::exact($recCtl['left']) . ' vs GL ' . Money::exact($recCtl['right']) . ' = ' . Money::exact($recCtl['difference'])
            . ' ' . $recCtl['status'] . '; gold_lot_results unchanged'
        );

        $hist = collect($all['historical']);
        $codes = $hist->pluck('lot_code')->all();
        $totalsExclude = abs($all['totals']['realized_usd'] - collect($all['lots'])->sum(fn ($l) => $l['result']['net_usd'])) < self::EPS;

        $this->check(
            'Historical attribution: the BOR deals sit in their own section, labelled, excluded from every total',
            $hist->count() >= 2 && in_array('BOR-2026-09-08', $codes, true) && in_array('BOR-2026-10-02', $codes, true)
            && $hist->every(fn ($h) => $h['gl'] === null && $h['label'] === RealizedTradingResult::HISTORICAL_ATTRIBUTION)
            && $totalsExclude && ! in_array('BOR-2026-09-08', array_column($all['lots'], 'lot_code'), true),
            implode(', ', $codes) . ' under "' . $all['historical_label'] . '"; totals ' . Money::format($all['totals']['realized_usd']) . ' from current deals only'
        );
    }

    // --------------------------------------------------------- 8-10. cash flow

    private function cashFlowTests(FinancialStatements $statements, CashService $cash, CashAccountService $accounts, JournalPoster $poster): void
    {
        $may = $statements->cashFlow(ReportScope::forPeriod($this->traded));
        $june = $statements->cashFlow(ReportScope::forPeriod($this->declared));
        $range = $statements->cashFlow(ReportScope::fromInput(['from' => '2025-05-01', 'to' => '2025-07-31']));

        $this->check(
            'Cash flow: opening + classified movements = closing, for a period and for a range',
            $may['controls'][0]['passed'] && $june['controls'][0]['passed'] && $range['controls'][0]['passed']
            && abs($may['opening_usd']) < self::EPS && abs($may['closing_usd'] - $june['opening_usd']) < self::EPS
            && abs($may['classes']['owner_capital']['amount'] - 30000.00) < self::EPS
            && abs($may['classes']['gold_purchases']['amount'] + 2900.00) < self::EPS
            && abs($may['classes']['gold_sales']['amount'] - 4190.00) < self::EPS,
            'May: owner capital ' . Money::format($may['classes']['owner_capital']['amount']) . ', gold purchases and capitalised costs '
            . Money::format($may['classes']['gold_purchases']['amount']) . ', gold sales ' . Money::format($may['classes']['gold_sales']['amount'])
            . ', closing ' . Money::format($may['closing_usd']) . '; range to July closes ' . Money::format($range['closing_usd'])
        );

        $cashBox = $accounts->create(['name' => 'Reporting Cash', 'type' => CashAccount::TYPE_CASH, 'code' => 'RP-CASH']);
        $before = $statements->cashFlow(ReportScope::forPeriod($this->later));
        $cash->transfer($this->bank, $cashBox, 1300.00, ['date' => '2025-07-15', 'memo' => 'Cash for the field, self-test']);
        $after = $statements->cashFlow(ReportScope::forPeriod($this->later));

        $this->check(
            'Cash flow: a 1,300.00 transfer between company accounts changes no class and appears once as a memo',
            $after['classes'] === $before['classes'] && abs($after['net_movement_usd'] - $before['net_movement_usd']) < self::EPS
            && count($after['transfers']) === 1 && abs($after['transfers_total_usd'] - 1300.00) < self::EPS
            && $after['controls'][0]['passed'],
            'classes identical; transfers memo ' . Money::format($after['transfers_total_usd']) . ' once; closing ' . Money::format($after['closing_usd']) . ' unchanged'
        );

        $poster->post([['account' => $this->bank->glAccount->code, 'debit' => 500.00], ['account' => '2000', 'credit' => 500.00]],
            ['date' => '2025-07-16', 'memo' => 'Investor capital received, self-test'], $this->super);
        $poster->post([['account' => '2010', 'debit' => 100.00], ['account' => $this->bank->glAccount->code, 'credit' => 100.00]],
            ['date' => '2025-07-17', 'memo' => 'Investor profit paid, self-test'], $this->super);
        $cf = $statements->cashFlow(ReportScope::forPeriod($this->later));

        $this->check(
            'Cash flow: investor capital received (2000) and investor profit paid (2010) are classified separately',
            abs($cf['classes']['investor_capital']['amount'] - $after['classes']['investor_capital']['amount'] - 500.00) < self::EPS
            && abs($cf['classes']['investor_paid']['amount'] - $after['classes']['investor_paid']['amount'] + 100.00) < self::EPS
            && $cf['controls'][0]['passed'] && str_contains((string) $cf['classes']['investor_paid']['caption'], 'G1'),
            'capital +500.00 (financing), profit paid -100.00 (financing, G1 captioned); reconciles'
        );
    }

    // ------------------------------------------------------------- 19. permissions

    private function permissionTests(ClosePackBuilder $packs): void
    {
        $stranger = new Admin();
        $stranger->id = -1;
        $stranger->username = 'selftest-no-permissions';

        $tokens = [AccountingPermission::REPORT_VIEW, AccountingPermission::REPORT_INVESTOR, AccountingPermission::REPORT_EXPORT,
            AccountingPermission::DASHBOARD_VIEW, AccountingPermission::CONTROL_VIEW];

        $this->check(
            'Permissions: a stranger can neither view nor export; view, investor, export, dashboard and control are distinct grants',
            collect($tokens)->every(fn ($t) => ! AccountingPermission::allows($stranger, $t) && AccountingPermission::allows($this->super, $t))
            && count(array_unique($tokens)) === 5 && array_diff($tokens, AccountingPermission::all()) === []
            && $this->refused(fn () => $packs->build($this->traded, $stranger)),
            'refused for an admin holding no grants; the close pack needs ' . AccountingPermission::REPORT_EXPORT
        );
    }

    // ------------------------------------------------------------------ 20. audit

    private function auditTests(): void
    {
        $before = ReportView::count();
        $this->callSilently('report', ['action' => 'trial-balance', '--period' => 'ST-2025-05']);
        $row = ReportView::orderByDesc('id')->first();

        $this->check(
            'Audit: rendering a report writes exactly one append-only audit row with scope and control outcomes',
            ReportView::count() === $before + 1 && $row->report === 'trial-balance' && $row->viewer_type === 'console'
            && ($row->scope['period'] ?? null) === 'ST-2025-05' && $row->interim === false && $row->controls_passed === true
            && count($row->controls) === 3 && $this->refused(fn () => $row->update(['controls_passed' => false]))
            && $this->refused(fn () => $row->delete()),
            'row #' . $row->id . ' ' . $row->report . ' by console, FINAL, ' . count($row->controls) . ' controls; update and delete refused'
        );
    }

    // ---------------------------------------------- 21, 28, 32, 33. close pack

    private function closePackTests(ClosePackBuilder $packs): void
    {
        $pack = $packs->build($this->traded, $this->super);
        $bytes = $pack->contents();

        $this->check(
            'Close pack: one private PDF for the closed period, core fonts, hashed; header carries scope and FINAL status',
            str_starts_with($pack->reference, 'PCK-') && str_starts_with($bytes, '%PDF') && $pack->file_bytes === strlen($bytes)
            && $pack->file_bytes < 400 * 1024 && hash('sha256', $bytes) === $pack->file_hash && $pack->intact()
            && $pack->close_reference === $this->traded->fresh()->close_reference && $pack->generated_by === $this->super->username
            && ! str_contains($bytes, '/FontFile'),
            $pack->reference . ' ' . number_format($pack->file_bytes / 1024, 1) . ' KB, sha256 ' . substr($pack->file_hash, 0, 16) . '..., no embedded font'
        );

        $updateRefused = $this->refused(fn () => $pack->update(['file_hash' => str_repeat('0', 64)]));
        // A refused update leaves the in-memory model dirty (the 3D lesson); the
        // database row is what matters, so read it back before comparing.
        $pack->refresh();

        $this->check(
            'Close pack immutability: the pack cannot be altered or deleted, and an open period has no pack',
            $updateRefused
            && $this->refused(fn () => $pack->delete())
            && $this->refused(fn () => $packs->build($this->declared, $this->super))
            && $pack->file_hash === hash('sha256', $bytes),
            'update and delete refused; stored hash unchanged; ST-2025-06 (open) refused'
        );

        $again = $packs->build($this->traded, $this->super);

        $this->check(
            'PDF hash reproducibility: a second request returns the same pack, same hash, still matching the bytes on disk',
            $again->id === $pack->id && $again->file_hash === $pack->file_hash && $again->intact()
            && PeriodClosePack::where('accounting_period_id', $this->traded->id)->count() === 1,
            'one pack, ' . $again->reference . ', hash unchanged and intact'
        );

        $data = $packs->data($this->traded);
        $html = view('admin.sections.accounting-reports.pdf.pack', $data + [
            'reference' => $pack->reference, 'generated_at' => $pack->generated_at, 'generated_by' => $pack->generated_by,
            'branding' => Branding::get(), 'sections' => ClosePackBuilder::SECTIONS,
        ])->render();

        $titles = ['Trial Balance', 'Profit and Loss', 'Balance Sheet', 'Cash Flow', 'Period Close', 'Reconciliation and Controls', 'Allocation and Distribution'];
        $missing = array_filter($titles, fn ($t) => ! str_contains($html, $t));

        $this->check(
            'Close pack contains every required report: trial balance, P&L, balance sheet, cash flow, period close, controls, distribution',
            $missing === [] && $pack->sections === ClosePackBuilder::SECTIONS
            && str_contains($html, $pack->close_reference) && str_contains($html, 'FINAL')
            && str_contains($html, 'SHA-256') && str_contains($html, 'not an accounting record'),
            count($titles) . ' sections present; ' . $this->traded->fresh()->close_reference . ' and FINAL in the header'
        );
    }

    // ------------------------------------------------------- 22. the real September

    private function emptyLedgerTests(FinancialStatements $statements, TradingReports $trading): void
    {
        $september = AccountingPeriod::where('code', '2026-09')->first() ?? AccountingPeriod::covering(now());

        if (! $september) {
            $this->check('Empty ledger: every report renders for the real open period and says the ledger is empty', false, 'no real period exists');

            return;
        }

        $scope = ReportScope::forPeriod($september);
        $realJournals = Journal::where('accounting_period_id', $september->id)->count();

        $tb = $statements->trialBalance($scope);
        $pl = $statements->profitAndLoss($scope);
        $cf = $statements->cashFlow($scope);
        $pc = $trading->periodClose($september);
        $bs = $statements->balanceSheet(ReportScope::fromInput(['as_at' => '2024-12-31']));

        $this->check(
            'Empty ledger: every report renders for the real ' . $september->code . ' and says the ledger is empty, interim, without a false final figure',
            $realJournals === 0
                ? ($tb['empty'] && $pl['empty'] && $cf['empty'] && $pc['empty'] && $bs['empty'] && $bs['journals'] === 0
                    && str_starts_with($tb['scope']['status'], 'INTERIM') && ! $pc['period']['is_closed']
                    && abs($pl['trading']['realized_result_usd']) < self::EPS && $pl['trading']['deals'] === [] && $tb['balanced'] && $cf['controls'][0]['passed'])
                : (str_starts_with($tb['scope']['status'], 'INTERIM') && $tb['balanced']),
            $september->code . ' ' . $september->status . ': ' . $realJournals . ' real journal(s); ' . $tb['scope']['status']
        );
    }

    // --------------------------------------------------------------- 23. legacy

    private function legacyTests(FinancialStatements $statements, InvestorLiabilityReport $investors, TradingReports $trading): void
    {
        $static = true;
        $files = [];

        foreach (glob(app_path('Accounting/Reports/*.php')) as $file) {
            $source = (string) php_strip_whitespace($file);
            if (str_contains($source, 'investment_plan') || str_contains($source, 'InvestmentPlan') || str_contains($source, 'profit_percentage')
                || str_contains($source, 'InvestProfitLog') || str_contains($source, 'investment_profit_logs')) {
                $static = false;
                $files[] = basename($file);
            }
        }

        $snapshot = fn () => json_encode([
            $statements->profitAndLoss(ReportScope::forPeriod($this->traded)),
            $investors->company(ReportScope::forPeriod($this->declared)),
            $trading->goldTrading(new ReportScope(lot: $this->lotA->lot_code)),
        ]);

        $runtime = true;
        $note = 'no report class reads investment_plans, profit_percentage or profit logs';

        if (Schema::hasTable('investment_plans')) {
            $before = $snapshot();
            $columns = Schema::getColumnListing('investment_plans');
            $row = ['profit_percentage' => 99.0, 'created_at' => now(), 'updated_at' => now()];
            foreach ([
                'name' => 'Self-test plan', 'slug' => 'self-test-plan-' . substr(md5(uniqid('', true)), 0, 8),
                'data' => json_encode(['title' => 'Self-test plan']), 'plan_duration' => 30,
                'profit_return_type' => \App\Constants\GlobalConst::INVEST_PROFIT_ONE_TIME,
                'minimum_investment' => 1, 'maximum_investment' => 1, 'profit' => 99, 'image' => 'self-test.png', 'status' => 1,
            ] as $col => $val) {
                if (in_array($col, $columns, true)) {
                    $row[$col] = $val;
                }
            }
            try {
                $id = DB::table('investment_plans')->insertGetId($row);
                $runtime = $snapshot() === $before;
                $note .= '; a 99% legacy plan (id ' . $id . ') inserted at runtime changed no report figure (byte-identical)';
            } catch (\Throwable $e) {
                $runtime = false;
                $note .= '; LEGACY TABLE PRESENT BUT THE FIXTURE ROW WAS NOT INSERTED: ' . substr($e->getMessage(), 0, 90);
            }
        } else {
            $note .= '; no legacy table in this database, so only the static proof applies here';
        }

        $this->check('Legacy: a 99% investment_plans row changes no report figure (static and runtime)', $static && $runtime,
            $note . ($files ? '; OFFENDING: ' . implode(', ', $files) : ''));
    }

    // ------------------------------------------------------- 24, 31. read-only

    private function readOnlyTests(FinancialStatements $statements, TradingReports $trading, InvestorLiabilityReport $investors,
        ControlReports $controls, ClosePackBuilder $packs, CsvExport $csv): void
    {
        $before = $this->financialSnapshot();

        foreach ([$this->traded, $this->declared, $this->later] as $period) {
            $scope = ReportScope::forPeriod($period);
            $statements->trialBalance($scope, true);
            $statements->profitAndLoss($scope);
            $statements->balanceSheet($scope);
            $statements->cashFlow($scope);
            $trading->periodClose($period);
            $investors->company($scope);
        }
        $trading->goldTrading(new ReportScope());
        $trading->dashboard();
        $controls->all(ReportScope::forPeriod($this->declared));
        $investors->forInvestor($this->investorA, new ReportScope());

        $this->check(
            'Read-only: journal, line, ledger entry, wallet, transaction, distribution, period and result counts are identical after rendering every report',
            $before === $this->financialSnapshot(),
            'journals ' . $before['journals'] . ', lines ' . $before['lines'] . ', ledger entries ' . $before['entries'] . ', wallets '
            . Money::exact($before['wallet_profit']) . ' - unchanged'
        );

        $before = $this->financialSnapshot();
        $packs->build($this->traded, $this->super);
        $csvBytes = 0;

        foreach ([
            $statements->trialBalance(ReportScope::forPeriod($this->traded)), $statements->profitAndLoss(ReportScope::forPeriod($this->declared)),
            $statements->balanceSheet(ReportScope::fromInput(['as_at' => '2025-07-31'])), $statements->cashFlow(ReportScope::forPeriod($this->later)),
            $trading->goldTrading(new ReportScope()), $investors->company(ReportScope::forPeriod($this->declared)),
            $trading->periodClose($this->traded), $controls->all(ReportScope::forPeriod($this->traded)),
        ] as $report) {
            $csvBytes += strlen($csv->string($report));
        }

        $html = view('admin.sections.accounting-reports.pdf.report', [
            'title' => 'Profit and Loss', 'partial' => 'profit-loss', 'data' => $statements->profitAndLoss(ReportScope::forPeriod($this->declared)),
            'scope' => ReportScope::forPeriod($this->declared), 'branding' => Branding::get(), 'generated_by' => 'selftest',
        ])->render();

        $this->check(
            'No mutation from exports: the close pack, every CSV and a PDF render change no financial record',
            $before === $this->financialSnapshot() && $csvBytes > 0 && str_contains($html, 'INTERIM'),
            number_format($csvBytes) . ' bytes of CSV across 8 reports, one PDF render, one pack - nothing financial moved'
        );
    }

    /** 25. The books still balance. */
    private function trialBalance(TrialBalance $trialBalance): void
    {
        $tb = $trialBalance->build();

        $this->check('Trial balance regression: still balances after every fixture and every report', $tb['balanced'],
            'debits ' . Money::format($tb['total_debits']) . ' credits ' . Money::format($tb['total_credits']) . ' difference ' . Money::exact($tb['difference']));
    }

    /** After the rollback: nothing outside this run moved. */
    private function untouched(array $before): void
    {
        $after = $this->outsideSnapshot();

        $this->check('No real investor balance, ledger entry, transaction or distribution moved', $before['investor'] === $after['investor'],
            'wallets ' . Money::exact($after['investor']['balance']) . ' / ' . Money::exact($after['investor']['profit']) . ', '
            . $after['investor']['entries'] . ' ledger entries, ' . $after['investor']['distributions'] . ' distribution(s) - unchanged');

        $this->check('The real accounting periods are exactly as they were; September remains open and undistributed; no audit row or pack remains',
            $before['periods'] === $after['periods'] && $before['journals'] === $after['journals']
            && $before['views'] === $after['views'] && $before['packs'] === $after['packs'],
            implode(', ', array_map(fn ($p) => $p['code'] . ' ' . $p['status'], $after['periods'])) . '; ' . $after['journals'] . ' real journal(s)');

        $this->check('Suspense is untouched and the historical attribution records are byte-identical',
            abs($this->accountBalance('1090')) < self::EPS && $before['historical'] === $after['historical'],
            'account 1090 holds ' . Money::exact($this->accountBalance('1090')) . '; ' . count($after['historical']) . ' historical record(s)');
    }

    // ------------------------------------------------------------------ fixtures

    /** A raw journal line the poster would never write, the way a hand edit or a bad migration would. */
    private function damage(AccountingPeriod $period, string $code, float $debit, ?int $lotId = null): int
    {
        $journal = Journal::where('accounting_period_id', $period->id)->orderBy('id')->first();
        $account = Account::where('code', $code)->first();

        return DB::table('journal_lines')->insertGetId([
            'journal_id' => $journal->id, 'line_no' => 99, 'account_id' => $account->id, 'debit' => $debit, 'credit' => 0,
            'memo' => 'self-test damage', 'gold_lot_id' => $lotId, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function finalizedDeal(array $spec): GoldLot
    {
        $lot = $this->tradedLot($spec);
        foreach ($spec['capital'] as $row) {
            [$user, $amount] = $row;
            CapitalAllocation::create([
                'gold_lot_id' => $lot->id, 'user_id' => $user->id, 'amount_usd' => $amount, 'share_percent' => $row[2] ?? null,
                'allocated_at' => $this->date, 'status' => CapitalAllocation::STATUS_ALLOCATED, 'locked_balance' => false,
                'locked_from_balance_usd' => 0, 'locked_from_profit_usd' => 0, 'notes' => 'self-test attribution; no wallet was debited',
            ]);
        }
        $lot->forceFill(['investor_share_percent' => $spec['share']])->save();
        $this->recorder->finaliseExpenses($lot);
        $this->recorder->record($lot, $this->super);

        return $lot->fresh();
    }

    private function tradedLot(array $spec): GoldLot
    {
        $grams = $spec['grams'];
        $cost = $spec['cost'];
        $perGram = round($cost / $grams, 8);

        $lot = GoldLot::create([
            'lot_code' => 'RP-SELFTEST-' . substr(md5(uniqid('', true)), 0, 8),
            'purchase_date' => $this->date, 'project_name' => 'Reporting self-test', 'location' => 'Test',
            'gross_grams' => $grams, 'purchase_currency' => 'USD', 'price_per_gram_local' => $perGram,
            'fx_rate_to_usd' => 1, 'price_per_gram_usd' => $perGram, 'total_cost_local' => $cost, 'total_cost_usd' => $cost, 'status' => 'purchased',
        ]);
        $this->gold->purchase($lot, $this->bank, ['date' => $this->date]);

        $waste = $spec['waste'];
        $processing = GoldProcessing::create([
            'gold_lot_id' => $lot->id, 'processed_at' => $this->date, 'method' => 'Self-test refining',
            'input_grams' => $grams, 'waste_grams' => $waste, 'waste_percent' => $grams > 0 ? round(($waste / $grams) * 100, 4) : 0,
            'output_grams' => round($grams - $waste, 4), 'output_purity' => '24K', 'cost_usd' => 0.0, 'cost_capitalised' => true,
        ]);
        $this->gold->refine($processing, null, ['date' => $this->date]);

        if (($spec['capitalised'] ?? 0) > 0) {
            $charge = $this->expenses->draft(['expense_date' => $this->date, 'category' => 'refining',
                'description' => 'Refinery charge, self-test', 'amount_local' => $spec['capitalised'], 'capitalised' => true, 'gold_lot_id' => $lot->id]);
            $this->expenses->submit($charge);
            $this->expenses->approve($charge, $this->super);
            $this->expenses->post($charge, $this->bank, ['date' => $this->date]);
        }

        $sale = GoldSale::create([
            'sale_code' => $lot->lot_code . '-S1', 'gold_lot_id' => $lot->id, 'sale_date' => $this->date,
            'buyer_name' => 'Self-test buyer', 'grams_sold' => $spec['sell_grams'], 'price_per_gram_usd' => $spec['price'],
            'gross_proceeds_usd' => round($spec['sell_grams'] * $spec['price'], 8),
            'settlement_currency' => 'USD', 'fx_rate_to_usd' => 1, 'status' => GoldSale::STATUS_SETTLED,
        ]);
        $this->gold->sell($sale, $this->bank, ['date' => $this->date]);

        return $lot->fresh();
    }

    private function tempInvestor(string $username): User
    {
        $user = new User();
        $user->forceFill([
            'firstname' => 'Self-test', 'lastname' => 'Investor', 'username' => $username,
            'email' => $username . '@selftest.invalid', 'password' => bcrypt(bin2hex(random_bytes(8))), 'status' => 1,
        ])->save();

        UserWallet::create(['user_id' => $user->id, 'currency_id' => $this->currency->id, 'balance' => 0, 'profit_balance' => 0, 'status' => 1]);

        return $user;
    }

    private function accountBalance(string $code): float
    {
        $account = Account::where('code', $code)->first();

        if (! $account) {
            return 0.0;
        }

        $sums = DB::table('journal_lines')->where('account_id', $account->id)->selectRaw('SUM(debit) as d, SUM(credit) as c')->first();
        $net = (float) ($sums->d ?? 0) - (float) ($sums->c ?? 0);

        return round($account->normal_balance === 'credit' ? -$net : $net, 8);
    }

    /** What the real investor ledgers hold before this run adds anything: the D2/D3 figures the controls must show. */
    private function realLedgerTotals(): array
    {
        $profit = 0.0;
        $capital = 0.0;

        foreach (LedgerEntry::select('user_id')->distinct()->pluck('user_id') as $userId) {
            $last = LedgerEntry::where('user_id', $userId)->orderByDesc('seq')->first();
            $profit += (float) $last->balance_profit;
            $capital += (float) $last->balance_available + (float) $last->balance_committed;
        }

        return ['profit' => round($profit, 8), 'capital' => round($capital, 8)];
    }

    private function financialSnapshot(): array
    {
        return [
            'journals'      => (int) DB::table('journals')->count(),
            'lines'         => (int) DB::table('journal_lines')->count(),
            'line_sum'      => round((float) DB::table('journal_lines')->sum('debit'), 8),
            'entries'       => (int) DB::table('investor_ledger_entries')->count(),
            'wallet_balance' => round((float) DB::table('user_wallets')->sum('balance'), 8),
            'wallet_profit' => round((float) DB::table('user_wallets')->sum('profit_balance'), 8),
            'transactions'  => (int) DB::table('transactions')->count(),
            'distributions' => (int) DB::table('investor_distributions')->count(),
            'dist_lines'    => (int) DB::table('investor_distribution_lines')->count(),
            'periods'       => AccountingPeriod::orderBy('code')->get(['code', 'status', 'close_reference'])->toArray(),
            'results'       => LotResult::orderBy('id')->get(['gold_lot_id', 'net_profit_usd', 'status', 'realized_at'])->toArray(),
            'lots'          => (int) DB::table('gold_lots')->count(),
            'expenses'      => (int) DB::table('gold_trading_expenses')->count(),
        ];
    }

    private function outsideSnapshot(): array
    {
        return [
            'investor' => [
                'balance'       => round((float) DB::table('user_wallets')->sum('balance'), 8),
                'profit'        => round((float) DB::table('user_wallets')->sum('profit_balance'), 8),
                'entries'       => (int) DB::table('investor_ledger_entries')->count(),
                'distributions' => (int) DB::table('investor_distributions')->count(),
                'users'         => (int) DB::table('users')->count(),
                'transactions'  => (int) DB::table('transactions')->count(),
            ],
            'periods'    => AccountingPeriod::orderBy('code')->get(['code', 'status', 'close_reference'])->toArray(),
            'journals'   => (int) DB::table('journals')->count(),
            'views'      => (int) DB::table('accounting_report_views')->count(),
            'packs'      => (int) DB::table('period_close_packs')->count(),
            'historical' => LotResult::where('status', LotResult::STATUS_DISTRIBUTED)->orderBy('gold_lot_id')
                ->get(['gold_lot_id', 'net_profit_usd', 'investor_profit_usd', 'status'])->toArray(),
        ];
    }

    private function superAdmin(): Admin
    {
        $admin = Admin::all()->first(fn (Admin $a) => $a->isSuperAdmin());

        if (! $admin) {
            throw new \RuntimeException('No Super Admin exists to run the reporting tests with.');
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
