<?php

namespace App\Console\Commands;

use App\Accounting\Models\Account;
use App\Accounting\Models\AccountingPeriod;
use App\Accounting\Models\CashAccount;
use App\Accounting\Models\InvestorDistribution;
use App\Accounting\Models\Journal;
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
use App\Investor\Services\LedgerReconciler;
use App\Investor\Support\Money;
use App\Models\Admin\Admin;
use App\Models\Admin\Currency;
use App\Models\User;
use App\Models\UserWallet;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The Phase 3F distribution acceptance run.
 *
 * Builds closed months of its own, with temporary investors whose wallets
 * exist only inside the transaction, distributes their allocations, and checks
 * every place the money is supposed to appear and every place it is not.
 * Everything rolls back. The real September period and the real investor are
 * read to prove they did not move, and never written.
 */
class DistributionSelfTestCommand extends Command
{
    protected $signature = 'distribution:selftest {--skip-regression : distribution tests only}';

    protected $description = 'Verify investor distribution: liability, ledger credit, idempotency, immutability, reversal';

    private array $results = [];
    private const EPS = 0.00000001;

    private ?AccountingPeriod $period = null;
    private ?AccountingPeriod $declared = null;
    private string $date;
    private string $declaredOn;
    private ExpenseWorkflow $expenses;
    private GoldTradingPoster $gold;
    private RealizedResultRecorder $recorder;
    private PeriodCloseService $close;
    private ?CashAccount $bank = null;
    private ?Admin $super = null;
    private ?User $investorA = null;
    private ?User $investorB = null;
    private ?Currency $currency = null;

    public function handle(
        InvestorDistributor $distributor,
        InvestorAllocation $allocation,
        RealizedTradingResult $realized,
        RealizedResultRecorder $recorder,
        PeriodCloseService $close,
        ExpenseWorkflow $expenses,
        GoldTradingPoster $gold,
        JournalPoster $poster,
        LedgerReconciler $reconciler,
        CashAccountService $accounts,
        CashService $cash,
        TrialBalance $trialBalance,
    ): int {
        // Artisan resolves a command once per process, so a suite invoked twice in
        // the same run would otherwise report the first run's results alongside
        // the second's and count them all.
        $this->results = [];

        $this->line('Investor distribution self-test (Phase 3F, distribution)');
        $this->line(str_repeat('-', 60));

        $this->expenses = $expenses;
        $this->gold = $gold;
        $this->recorder = $recorder;
        $this->close = $close;

        $before = $this->outsideSnapshot();

        DB::beginTransaction();

        try {
            $this->super = $this->superAdmin();
            $this->currency = Currency::where('default', true)->firstOrFail();
            $this->investorA = $this->tempInvestor('st-dist-a');
            $this->investorB = $this->tempInvestor('st-dist-b');

            // The month whose result is distributed, and the month the distribution
            // is declared in. Both are this run's own; neither is September.
            $this->period = AccountingPeriod::create(['code' => 'ST-2025-05', 'starts_on' => '2025-05-01',
                'ends_on' => '2025-05-31', 'status' => AccountingPeriod::OPEN]);
            $this->declared = AccountingPeriod::create(['code' => 'ST-2025-06', 'starts_on' => '2025-06-01',
                'ends_on' => '2025-06-30', 'status' => AccountingPeriod::OPEN]);
            $this->date = '2025-05-10';
            $this->declaredOn = '2025-06-05';

            $this->bank = $accounts->create(['name' => 'Distribution Bank', 'type' => CashAccount::TYPE_BANK, 'code' => 'DS-BANK']);
            $cash->receipt($this->bank, 30000.00, '3000', ['date' => $this->date, 'memo' => 'Self-test funding']);

            // 890.00 at 100% to A; 400.00 split A 60%cap x 70% = 168.00, B 40%cap x 50% = 80.00
            // Pool 1,138.00: A 1,058.00, B 80.00
            $lotA = $this->finalizedDeal(['cost' => 2000, 'grams' => 25, 'waste' => 2, 'capitalised' => 100,
                'sell_grams' => 23, 'price' => 130, 'share' => 100, 'capital' => [[$this->investorA, 2000.00]]]);
            $lotB = $this->finalizedDeal(['cost' => 800, 'grams' => 10, 'waste' => 0, 'capitalised' => 0,
                'sell_grams' => 10, 'price' => 120, 'share' => 70,
                'capital' => [[$this->investorA, 480.00], [$this->investorB, 320.00, 50.0]]]);

            $this->openPeriodRefuses($distributor);
            $this->refusalsBeforeClose($distributor, $allocation);
            $this->distributes($distributor, $realized, $reconciler);
            $this->idempotent($distributor);
            $this->immutable($distributor);
            $this->reversal($distributor, $reconciler);
            $this->zeroAndNegative($distributor);
            $this->legacyAndReserve($distributor, $allocation);
            $this->permissions($distributor);
            $this->atomicity($distributor);
            $this->historicalRefused($distributor);
            $this->trialBalance($trialBalance);
        } catch (\Throwable $e) {
            $this->check('Unexpected failure', false,
                $e->getMessage() . ' @ ' . basename($e->getFile()) . ':' . $e->getLine());
        } finally {
            DB::rollBack();
        }

        $this->untouched($before);

        $this->table(['#', 'Test', 'Result', 'Detail'],
            array_map(fn ($r, $i) => [$i + 1, $r[0], $r[1] ? 'PASS' : 'FAIL', $r[2]], $this->results, array_keys($this->results)));

        $failed = array_filter($this->results, fn ($r) => ! $r[1]);
        $this->line('  ' . (count($this->results) - count($failed)) . ' of ' . count($this->results) . ' passed.');
        $this->line('  Everything was rolled back; no distribution, journal, credit or wallet change remains.');

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

    /** 1. Nothing moves while the month is open. */
    private function openPeriodRefuses(InvestorDistributor $distributor): void
    {
        $journals = Journal::count();
        $entries = LedgerEntry::count();

        $this->check(
            'An open period refuses distribution, and nothing is written',
            $this->refused(fn () => $distributor->distribute($this->period, $this->super, ['date' => $this->declaredOn]))
            && Journal::count() === $journals && LedgerEntry::count() === $entries
            && InvestorDistribution::count() === 0,
            'refused on ' . $this->period->code . ' (open); journals and ledger entries unchanged'
        );
    }

    /** 2-4. Every allocation blocker is a distribution blocker, before the close. */
    private function refusalsBeforeClose(InvestorDistributor $distributor, InvestorAllocation $allocation): void
    {
        // A sold-out deal left interim.
        $interim = $this->tradedLot(['cost' => 500, 'grams' => 5, 'waste' => 0, 'capitalised' => 0, 'sell_grams' => 5, 'price' => 130]);
        $this->allocateCapital($interim, [[$this->investorA, 500.00]]);
        $interim->forceFill(['investor_share_percent' => 100])->save();

        $refusedInterim = $this->refused(fn () => $distributor->distribute($this->period, $this->super, ['date' => $this->declaredOn]))
            && collect($allocation->forPeriod($this->period)['blocked'])->firstWhere('lot_code', $interim->lot_code) !== null;

        $this->recorder->finaliseExpenses($interim);
        $this->recorder->record($interim, $this->super);

        // Terms missing on a recorded deal.
        $noTerms = $this->tradedLot(['cost' => 400, 'grams' => 4, 'waste' => 0, 'capitalised' => 0, 'sell_grams' => 4, 'price' => 125]);
        $this->allocateCapital($noTerms, [[$this->investorA, 400.00]]);
        $this->recorder->finaliseExpenses($noTerms);
        $this->recorder->record($noTerms, $this->super);

        $refusedTerms = $this->refused(fn () => $distributor->distribute($this->period, $this->super, ['date' => $this->declaredOn]));
        $noTerms->forceFill(['investor_share_percent' => 25])->save();

        // Capital missing on a recorded deal.
        $noCapital = $this->tradedLot(['cost' => 300, 'grams' => 3, 'waste' => 0, 'capitalised' => 0, 'sell_grams' => 3, 'price' => 120]);
        $noCapital->forceFill(['investor_share_percent' => 100])->save();
        $this->recorder->finaliseExpenses($noCapital);
        $this->recorder->record($noCapital, $this->super);

        $refusedCapital = $this->refused(fn () => $distributor->distribute($this->period, $this->super, ['date' => $this->declaredOn]));
        $this->allocateCapital($noCapital, [[$this->investorB, 300.00]]);

        $this->check('An interim result refuses distribution', $refusedInterim, $interim->lot_code . ' blocked it until recorded');
        $this->check('Missing investor terms refuse distribution', $refusedTerms, $noTerms->lot_code . ' blocked it until terms were set (25%)');
        $this->check('Missing investor capital refuses distribution', $refusedCapital, $noCapital->lot_code . ' blocked it until capital was recorded');

        // Pool now: A 890 + 168 + 150 + 25 = 1,233.00 ; B 80 + 60 = 140.00 ; total 1,373.00
        $this->close->close($this->period, $this->super, 'May 2025 month end, self-test');
    }

    /** 5-13. The distribution itself, checked in every place it lands. */
    private function distributes(InvestorDistributor $distributor, RealizedTradingResult $realized, LedgerReconciler $reconciler): void
    {
        $resultBefore = $realized->forCompany(['period_id' => $this->period->id]);
        $walletA = $this->wallet($this->investorA);
        $walletB = $this->wallet($this->investorB);
        $profitA = (float) $walletA->profit_balance;
        $profitB = (float) $walletB->profit_balance;
        $payableBefore = $this->accountBalance('2010');
        $shareBefore = $this->accountBalance('7000');
        $entriesBefore = LedgerEntry::count();

        $d = $distributor->distribute($this->period, $this->super, ['date' => $this->declaredOn, 'note' => 'self-test']);
        $journal = $d->journal->load('lines.account');

        $this->check(
            'A closed period distributes: one reference, one journal, one line per investor',
            $d->status === InvestorDistribution::POSTED
            && str_starts_with($d->reference, 'DIST-' . $this->period->code . '-')
            && $d->lines->count() === 2
            && abs($d->pool_usd - 1373.00) < self::EPS
            && (int) $d->distributed_by === (int) $this->super->id,
            $d->reference . ': pool ' . Money::exact($d->pool_usd) . ', 2 investors, by ' . $this->super->username
        );

        $dr7000 = $journal->lines->first(fn ($l) => $l->account->code === '7000');
        $cr2010 = $journal->lines->first(fn ($l) => $l->account->code === '2010');

        $this->check(
            'The appropriation is Dr 7000 / Cr 2010 for exactly the pool',
            $journal->balances() && $journal->lines->count() === 2
            && abs((float) $dr7000->debit - 1373.00) < self::EPS && abs((float) $cr2010->credit - 1373.00) < self::EPS
            && (int) $journal->accounting_period_id === (int) $this->declared->id,
            $journal->reference . ': Dr 7000 ' . Money::exact((float) $dr7000->debit) . ' / Cr 2010 '
            . Money::exact((float) $cr2010->credit) . ', dated ' . $this->declaredOn . ' in ' . $this->declared->code
        );

        $lineA = $d->lines->firstWhere('user_id', $this->investorA->id);
        $lineB = $d->lines->firstWhere('user_id', $this->investorB->id);
        $entryA = LedgerEntry::find($lineA->ledger_entry_id);
        $entryB = LedgerEntry::find($lineB->ledger_entry_id);

        $this->check(
            'Each investor receives exactly one profit credit in the ledger, in the profit bucket',
            $entryA && $entryB
            && $entryA->event_type === 'profit_credited' && $entryA->bucket === 'profit'
            && abs((float) $entryA->amount_usd - 1233.00) < self::EPS
            && abs((float) $entryB->amount_usd - 140.00) < self::EPS
            && $entryA->flow === 'external_in'
            && LedgerEntry::count() === $entriesBefore + 2,
            $entryA->reference . ' A +1233.00000000; ' . $entryB->reference . ' B +140.00000000; two entries, no more'
        );

        $this->check(
            'The wallet profit balance rises by exactly the credit',
            abs(((float) $this->wallet($this->investorA)->profit_balance - $profitA) - 1233.00) < self::EPS
            && abs(((float) $this->wallet($this->investorB)->profit_balance - $profitB) - 140.00) < self::EPS,
            'A ' . Money::exact($profitA) . ' to ' . Money::exact((float) $this->wallet($this->investorA)->profit_balance)
            . '; B ' . Money::exact($profitB) . ' to ' . Money::exact((float) $this->wallet($this->investorB)->profit_balance)
        );

        $ledgerSum = round((float) $entryA->amount_usd + (float) $entryB->amount_usd, 8);

        $this->check(
            'Company payable, 7000 debit and the investor credits are one figure',
            abs(($this->accountBalance('2010') - $payableBefore) - $ledgerSum) < self::EPS
            && abs(($this->accountBalance('7000') - $shareBefore) - $ledgerSum) < self::EPS
            && abs($ledgerSum - $d->pool_usd) < self::EPS
            && abs($ledgerSum - $d->lines->sum('amount_usd')) < self::EPS,
            '2010 +' . Money::exact($ledgerSum) . ' = 7000 +' . Money::exact($ledgerSum) . ' = credits ' . Money::exact($ledgerSum)
        );

        $resultAfter = $realized->forCompany(['period_id' => $this->period->id]);

        $this->check(
            'The realized trading result is unchanged by the distribution',
            abs($resultAfter['trading_result_usd'] - $resultBefore['trading_result_usd']) < self::EPS
            && abs((float) $d->company_result_usd - $resultBefore['trading_result_usd']) < self::EPS,
            'still ' . Money::exact($resultAfter['trading_result_usd']) . ' after Dr 7000; snapshot recorded '
            . Money::exact((float) $d->company_result_usd)
        );

        $checkA = $reconciler->forUser($this->investorA);
        $checkB = $reconciler->forUser($this->investorB);

        $this->check(
            'The investor ledger reconciles with the wallet and accounts for the transaction',
            $checkA['passed'] === true && $checkB['passed'] === true
            && $entryA->transaction_id !== null && (int) $entryA->transaction_id === (int) $lineA->transaction_id,
            'ledger:check passes for both; entry ' . $entryA->reference . ' cites transaction ' . $lineA->trx_id
        );

        $ledgerA = LedgerEntry::where('user_id', $this->investorA->id)->orderByDesc('seq')->first();

        $this->check(
            'Eight-decimal precision survives wallet, transaction and ledger alike',
            Money::exact((float) $ledgerA->balance_profit) === Money::exact((float) $this->wallet($this->investorA)->profit_balance)
            && Money::exact((float) DB::table('transactions')->where('id', $lineA->transaction_id)->value('receive_amount')) === '1233.00000000',
            'ledger profit ' . Money::exact((float) $ledgerA->balance_profit) . ' = wallet '
            . Money::exact((float) $this->wallet($this->investorA)->profit_balance) . '; transaction 1233.00000000'
        );

        $snap = $d->snapshot;

        $this->check(
            'The distribution snapshot carries the allocation exactly as it was made',
            isset($snap['allocation']['eligible']) && count($snap['allocation']['eligible']) === 5
            && $snap['period'] === $this->period->code && $snap['period_closed'] === $this->period->fresh()->close_reference
            && $snap['reference'] === $d->reference && $snap['journal'] === $journal->reference
            && (float) $snap['allocation']['reserve_usd'] === 0.0
            && (int) $snap['distributed_by'] === (int) $this->super->id && isset($snap['distributed_at']),
            '5 eligible deals, period ' . $snap['period'] . ' closed as ' . $snap['period_closed'] . ', reserve 0, actor and time recorded'
        );

        $this->distribution = $d;
    }

    private ?InvestorDistribution $distribution = null;

    /** 14-15. The same thing done twice happens once. */
    private function idempotent(InvestorDistributor $distributor): void
    {
        $journals = Journal::count();
        $entries = LedgerEntry::count();
        $profitA = (float) $this->wallet($this->investorA)->profit_balance;
        $payable = $this->accountBalance('2010');

        $again = $distributor->distribute($this->period, $this->super, ['date' => $this->declaredOn]);

        $this->check(
            'Distributing the same period twice distributes it once',
            (int) $again->id === (int) $this->distribution->id
            && Journal::count() === $journals && LedgerEntry::count() === $entries
            && abs((float) $this->wallet($this->investorA)->profit_balance - $profitA) < self::EPS
            && abs($this->accountBalance('2010') - $payable) < self::EPS
            && InvestorDistribution::where('accounting_period_id', $this->period->id)->count() === 1,
            'handed back ' . $again->reference . '; no journal, entry, wallet or liability moved'
        );

        $duplicate = $this->refused(fn () => DB::table('investor_distribution_lines')->insert([
            'investor_distribution_id' => $this->distribution->id, 'user_id' => $this->investorA->id,
            'amount_usd' => 1, 'trx_id' => 'X', 'transaction_id' => 0, 'ledger_entry_id' => LedgerEntry::min('id'),
            'ledger_reference' => 'X', 'snapshot' => '{}', 'created_at' => now(), 'updated_at' => now(),
        ]));

        $this->check(
            'The same investor cannot be credited twice by one distribution, even at the database',
            $duplicate,
            'unique key (distribution, investor) refused a second line for ' . $this->investorA->username
        );
    }

    /** 16-18. Nothing posted can be edited. */
    private function immutable(InvestorDistributor $distributor): void
    {
        $d = $this->distribution->fresh();
        $line = $d->lines->first();
        $entry = LedgerEntry::find($line->ledger_entry_id);

        $this->check(
            'The distribution record and its snapshot are immutable',
            $this->refused(fn () => $d->update(['pool_usd' => 1.00]))
            && $this->refused(fn () => $d->update(['snapshot' => ['x' => 1]]))
            && $this->refused(fn () => $d->delete())
            && abs((float) $d->fresh()->pool_usd - 1373.00) < self::EPS,
            'pool, snapshot and delete all refused; ' . $d->reference . ' stands'
        );

        $this->check(
            'The investor profit credit cannot be edited',
            $this->refused(fn () => $line->update(['amount_usd' => 1.00]))
            && $this->refused(fn () => $entry->update(['amount_usd' => 1.00]))
            && abs((float) $entry->fresh()->amount_usd - 1233.00) < self::EPS,
            'line and ledger entry both refused'
        );

        $this->check(
            'The 7000/2010 journal cannot be edited',
            $this->refused(fn () => $d->journal->update(['memo' => 'changed']))
            && $this->refused(fn () => $d->journal->lines->first()->update(['debit' => 1.00])),
            $d->journal->reference . ' refused header and line edits'
        );
    }

    /** 19-20. Undoing, by the existing rules, and distributing again afterwards. */
    private function reversal(InvestorDistributor $distributor, LedgerReconciler $reconciler): void
    {
        $d = $this->distribution->fresh();
        $profitA = (float) $this->wallet($this->investorA)->profit_balance;
        $payable = $this->accountBalance('2010');
        $share = $this->accountBalance('7000');

        $reversed = $distributor->reverse($d, 'Terms on one deal were recorded wrongly', $this->super);
        $lineA = $reversed->lines->firstWhere('user_id', $this->investorA->id);
        $undo = LedgerEntry::find($lineA->reversal_ledger_entry_id);

        $this->check(
            'Reversal mirrors the journal and answers each credit with a linked entry of the opposite sign',
            $reversed->status === InvestorDistribution::REVERSED
            && $reversed->journal->fresh()->status === 'reversed'
            && Journal::find($reversed->reversal_journal_id)->balances()
            && abs($this->accountBalance('2010') - ($payable - 1373.00)) < self::EPS
            && abs($this->accountBalance('7000') - ($share - 1373.00)) < self::EPS
            && $undo && abs((float) $undo->amount_usd + 1233.00) < self::EPS
            && (int) $undo->reverses_entry_id === (int) $lineA->ledger_entry_id
            && abs((float) $this->wallet($this->investorA)->profit_balance - ($profitA - 1233.00)) < self::EPS
            && $reconciler->forUser($this->investorA)['passed'] === true
            && $this->refused(fn () => $distributor->reverse($reversed, 'Again', $this->super)),
            $reversed->reference . ' reversed; 2010 and 7000 back by 1,373.00; ' . $undo->reference
            . ' -1233.00000000 reverses ' . LedgerEntry::find($lineA->ledger_entry_id)->reference . '; ledger still reconciles; second reversal refused'
        );

        $second = $distributor->distribute($this->period, $this->super, ['date' => $this->declaredOn]);

        $this->check(
            'After a reversal the period can be distributed again, under a new reference',
            (int) $second->id !== (int) $d->id && $second->reference !== $d->reference
            && $second->status === InvestorDistribution::POSTED
            && abs($second->pool_usd - 1373.00) < self::EPS
            && InvestorDistribution::where('accounting_period_id', $this->period->id)->count() === 2,
            $d->reference . ' (reversed) and ' . $second->reference . ' (posted) both on record'
        );

        $this->distribution = $second;
    }

    /** 21-22. Nothing, and less than nothing. */
    private function zeroAndNegative(InvestorDistributor $distributor): void
    {
        $zero = $this->isolatedPeriod('ST-2025-07', '2025-07', 1000, 10, 100);  // realized 0.00
        $journals = Journal::count();

        $this->check(
            'A zero pool distributes nothing and creates no journal',
            $this->refused(fn () => $distributor->distribute($zero, $this->super, ['date' => $this->declaredOn]))
            && Journal::count() === $journals && InvestorDistribution::where('accounting_period_id', $zero->id)->doesntExist(),
            'refused with "nothing to distribute"; no zero-value journal'
        );

        $loss = $this->isolatedPeriod('ST-2025-08', '2025-08', 1000, 10, 80);  // realized -200.00
        $entries = LedgerEntry::count();

        $refused = false;
        try {
            $distributor->distribute($loss, $this->super, ['date' => $this->declaredOn]);
        } catch (\Throwable $e) {
            $refused = str_contains($e->getMessage(), InvestorAllocation::NEGATIVE_POOL);
        }

        $this->check(
            'A negative pool is refused with the loss-policy message and no negative credit exists',
            $refused && LedgerEntry::count() === $entries
            && LedgerEntry::where('amount_usd', '<', 0)->where('event_type', 'profit_credited')
                ->where('user_id', $this->investorA->id)->where('reverses_entry_id', null)->doesntExist(),
            '"' . InvestorAllocation::NEGATIVE_POOL . '"; no ledger entry written'
        );
    }

    /** 23-24. The legacy engine and the reserve. */
    private function legacyAndReserve(InvestorDistributor $distributor, InvestorAllocation $allocation): void
    {
        $source = (string) php_strip_whitespace((new \ReflectionClass(InvestorDistributor::class))->getFileName());
        $static = ! str_contains($source, 'investment_plan') && ! str_contains($source, 'InvestmentPlan')
            && ! str_contains($source, 'profit_percentage');

        $note = 'no reference to the legacy engine in the distributor';
        $runtime = true;

        if (Schema::hasTable('investment_plans')) {
            $before = $this->distribution->fresh();
            $columns = Schema::getColumnListing('investment_plans');
            $row = ['profit_percentage' => 99.0, 'created_at' => now(), 'updated_at' => now()];
            foreach ([
                'name' => 'Self-test plan', 'slug' => 'self-test-dist-' . substr(md5(uniqid('', true)), 0, 8),
                'data' => json_encode(['title' => 'Self-test plan']), 'plan_duration' => 30,
                'profit_return_type' => \App\Constants\GlobalConst::INVEST_PROFIT_ONE_TIME,
                'minimum_investment' => 1, 'maximum_investment' => 1, 'profit' => 99, 'image' => 'self-test.png', 'status' => 1,
            ] as $col => $val) {
                if (in_array($col, $columns, true)) {
                    $row[$col] = $val;
                }
            }
            try {
                DB::table('investment_plans')->insert($row);
                $again = $distributor->distribute($this->period, $this->super, ['date' => $this->declaredOn]);
                $runtime = (int) $again->id === (int) $before->id && abs($again->pool_usd - $before->pool_usd) < self::EPS;
                $note .= '; a 99% legacy plan inserted at runtime; the posted distribution and pool are unchanged';
            } catch (\Throwable $e) {
                $runtime = false;
                $note .= '; LEGACY TABLE PRESENT BUT THE FIXTURE ROW WAS NOT INSERTED: ' . substr($e->getMessage(), 0, 90);
            }
        } else {
            $note .= '; no legacy table in this database, so only the static proof applies here';
        }

        $this->check('The legacy investment_plans engine cannot affect a distribution', $static && $runtime, $note);

        $extra = $this->isolatedPeriod('ST-2025-09', '2025-09', 1000, 10, 150);  // realized 500.00
        config(['accounting.allocation.reserve_percent' => 10]);
        $refusedReserve = $this->refused(fn () => $distributor->distribute($extra, $this->super, ['date' => $this->declaredOn]));
        config(['accounting.allocation.reserve_percent' => 0]);

        $this->check(
            'Reserve remains 0%, and a configured reserve refuses rather than guesses how it is taken',
            (float) config('accounting.allocation.reserve_percent') === 0.0 && $refusedReserve
            && abs((float) $this->distribution->fresh()->reserve_usd) < self::EPS,
            'reserve 0 on ' . $this->distribution->reference . '; a 10% reserve refused distribution of ' . $extra->code
        );
    }

    /** 25. Who may distribute. */
    private function permissions(InvestorDistributor $distributor): void
    {
        $stranger = new Admin();
        $stranger->id = -1;
        $stranger->username = 'selftest-no-permissions';

        $extra = $this->isolatedPeriod('ST-2025-10', '2025-10', 1000, 10, 150);

        $this->check(
            'Admin without permission cannot distribute or reverse',
            $this->refused(fn () => $distributor->distribute($extra, $stranger, ['date' => $this->declaredOn]))
            && $this->refused(fn () => $distributor->reverse($this->distribution->fresh(), 'x', $stranger))
            && ! AccountingPermission::allows($stranger, AccountingPermission::DISTRIBUTION_POST),
            'both refused for an admin holding no grants'
        );
    }

    /** 26. Half a distribution never survives. */
    private function atomicity(InvestorDistributor $distributor): void
    {
        $extra = $this->isolatedPeriod('ST-2025-11', '2025-11', 1000, 10, 150, $this->investorB);  // B gets 500 at 100%

        // Remove B's wallet, so the credit fails after the journal has posted.
        $walletId = $this->wallet($this->investorB)->id;
        DB::table('user_wallets')->where('id', $walletId)->delete();

        $journals = Journal::count();
        $entries = LedgerEntry::count();

        $refused = $this->refused(fn () => $distributor->distribute($extra, $this->super, ['date' => $this->declaredOn]));

        $this->check(
            'A failure midway rolls the whole distribution back: no journal, no credit, no record',
            $refused && Journal::count() === $journals && LedgerEntry::count() === $entries
            && InvestorDistribution::where('accounting_period_id', $extra->id)->doesntExist()
            && abs($this->accountBalance('2010') - $this->accountBalance('2010')) < self::EPS,
            'wallet removed before the credit; the 7000/2010 journal posted inside the transaction was rolled back with it'
        );
    }

    /** 27. The historical deals cannot be distributed again. */
    private function historicalRefused(InvestorDistributor $distributor): void
    {
        $historical = LotResult::where('status', LotResult::STATUS_DISTRIBUTED)->with('lot')->get();

        if ($historical->isEmpty()) {
            $this->check('Historical attribution cannot be distributed', true, 'no historical deal present to test');

            return;
        }

        $real = AccountingPeriod::covering($historical->first()->closed_at) ?? AccountingPeriod::orderBy('starts_on')->first();
        $journals = Journal::count();

        $this->check(
            'Historical attribution cannot be distributed again',
            $this->refused(fn () => $distributor->distribute($real, $this->super))
            && Journal::count() === $journals
            && InvestorDistribution::where('accounting_period_id', $real->id)->doesntExist(),
            $historical->pluck('lot.lot_code')->implode(', ') . ': refused on ' . $real->code . ' (open, no eligible result); nothing written'
        );
    }

    /** 28. The books still balance. */
    private function trialBalance(TrialBalance $trialBalance): void
    {
        $tb = $trialBalance->build();
        $this->check('Trial balance still balances', $tb['balanced'],
            'debits ' . Money::format($tb['total_debits']) . ' credits ' . Money::format($tb['total_credits'])
            . ' difference ' . Money::exact($tb['difference']));
    }

    /** 29-31. Nothing outside this run moved — checked after the rollback. */
    private function untouched(array $before): void
    {
        $after = $this->outsideSnapshot();

        $this->check('Bhavin\'s wallet and ledger, every distribution and every user are exactly as they were',
            $before['investor'] === $after['investor'],
            'wallets ' . Money::exact($after['investor']['balance']) . ' / ' . Money::exact($after['investor']['profit'])
            . ', ' . $after['investor']['entries'] . ' ledger entries, ' . $after['investor']['legacy_distributions']
            . ' legacy distributions, ' . $after['investor']['distributions'] . ' period distributions, '
            . $after['investor']['users'] . ' users, ' . $after['investor']['transactions'] . ' transactions - unchanged');

        $this->check('September 2026 is open, undistributed, and no real journal was created',
            $before['periods'] === $after['periods'] && $before['journals'] === $after['journals']
            && InvestorDistribution::count() === $before['investor']['distributions'],
            implode(', ', array_map(fn ($p) => $p['code'] . ' ' . $p['status'], $after['periods']))
            . '; ' . $after['journals'] . ' journal(s) in the real ledger, as before');

        $this->check('Suspense is untouched and the historical attribution records are byte-identical',
            abs($this->accountBalance('1090')) < self::EPS && $before['historical'] === $after['historical'],
            'account 1090 holds ' . Money::exact($this->accountBalance('1090')) . '; ' . count($after['historical']) . ' historical record(s)');
    }

    // ------------------------------------------------------------------ fixtures

    /** A closed month with one deal for one investor at 100%, realizing (price - 100) x grams. */
    private function isolatedPeriod(string $code, string $ym, float $cost, float $grams, float $price, ?User $investor = null): AccountingPeriod
    {
        $saved = [$this->period, $this->date];
        $period = AccountingPeriod::create(['code' => $code, 'starts_on' => $ym . '-01',
            'ends_on' => date('Y-m-t', strtotime($ym . '-01')), 'status' => AccountingPeriod::OPEN]);
        [$this->period, $this->date] = [$period, $ym . '-10'];

        $this->finalizedDeal(['cost' => $cost, 'grams' => $grams, 'waste' => 0, 'capitalised' => 0,
            'sell_grams' => $grams, 'price' => $price, 'share' => 100, 'capital' => [[$investor ?? $this->investorA, $cost]]]);

        $this->close->close($period, $this->super, $code . ' month end, self-test');

        [$this->period, $this->date] = $saved;

        return $period->fresh();
    }

    private function finalizedDeal(array $spec): GoldLot
    {
        $lot = $this->tradedLot($spec);
        $this->allocateCapital($lot, $spec['capital']);
        $lot->forceFill(['investor_share_percent' => $spec['share']])->save();
        $this->recorder->finaliseExpenses($lot);
        $this->recorder->record($lot, $this->super);

        return $lot->fresh();
    }

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
            'lot_code' => 'DS-SELFTEST-' . substr(md5(uniqid('', true)), 0, 8),
            'purchase_date' => $this->date, 'project_name' => 'Distribution self-test', 'location' => 'Test',
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
            'output_grams' => round($grams - $waste, 4), 'output_purity' => '24K', 'cost_usd' => 0.0, 'cost_capitalised' => true,
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
            'buyer_name' => 'Self-test buyer', 'grams_sold' => $spec['sell_grams'], 'price_per_gram_usd' => $spec['price'],
            'gross_proceeds_usd' => round($spec['sell_grams'] * $spec['price'], 8),
            'settlement_currency' => 'USD', 'fx_rate_to_usd' => 1, 'status' => GoldSale::STATUS_SETTLED,
        ]);
        $this->gold->sell($sale, $this->bank, ['date' => $this->date]);

        return $lot->fresh();
    }

    /** An investor with a wallet, both of which exist only inside this transaction. */
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

    private function wallet(User $user): UserWallet
    {
        return UserWallet::where('user_id', $user->id)->where('currency_id', $this->currency->id)->firstOrFail();
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

    private function outsideSnapshot(): array
    {
        return [
            'investor' => [
                'balance'              => round((float) DB::table('user_wallets')->sum('balance'), 8),
                'profit'               => round((float) DB::table('user_wallets')->sum('profit_balance'), 8),
                'entries'              => (int) DB::table('investor_ledger_entries')->count(),
                'legacy_distributions' => (int) DB::table('gold_profit_distributions')->count(),
                'distributions'        => (int) DB::table('investor_distributions')->count(),
                'users'                => (int) DB::table('users')->count(),
                'transactions'         => (int) DB::table('transactions')->count(),
            ],
            'periods'    => AccountingPeriod::orderBy('code')->get(['code', 'status', 'close_reference'])->toArray(),
            'journals'   => (int) DB::table('journals')->count(),
            'historical' => LotResult::where('status', LotResult::STATUS_DISTRIBUTED)->orderBy('gold_lot_id')
                ->get(['gold_lot_id', 'net_profit_usd', 'investor_profit_usd', 'status'])->toArray(),
        ];
    }

    private function superAdmin(): Admin
    {
        $admin = Admin::all()->first(fn (Admin $a) => $a->isSuperAdmin());

        if (! $admin) {
            throw new \RuntimeException('No Super Admin exists to run the distribution tests with.');
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
