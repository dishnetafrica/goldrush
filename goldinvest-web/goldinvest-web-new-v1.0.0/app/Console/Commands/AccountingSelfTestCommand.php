<?php

namespace App\Console\Commands;

use App\Accounting\Exceptions\AccountingException;
use App\Accounting\Exceptions\PostingRefused;
use App\Accounting\Models\Account;
use App\Accounting\Models\AccountingPeriod;
use App\Accounting\Models\Journal;
use App\Accounting\Security\AccountingPermission;
use App\Accounting\Services\JournalPoster;
use App\Accounting\Services\TrialBalance;
use App\Investor\Support\Money;
use App\Models\Admin\Admin;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * The Phase 3A acceptance run.
 *
 * Everything that writes happens inside a transaction that is always rolled
 * back, so the ledger is exactly as it was when this finishes. The tests are
 * mostly about refusals: the value of a general ledger is in what it declines
 * to record.
 */
class AccountingSelfTestCommand extends Command
{
    protected $signature = 'accounting:selftest {--skip-regression : accounting tests only}';

    protected $description = 'Verify journal posting, immutability, period enforcement and the trial balance';

    private array $results = [];

    public function handle(JournalPoster $poster, TrialBalance $trialBalance): int
    {
        // Artisan resolves a command once per process, so a suite invoked twice in
        // the same run would otherwise report the first run's results alongside
        // the second's and count them all.
        $this->results = [];

        $this->line('Accounting self-test (Phase 3A)');
        $this->line(str_repeat('-', 60));

        if (Account::count() === 0) {
            $this->error('No chart of accounts. Run accounting:install first.');

            return self::FAILURE;
        }

        DB::beginTransaction();

        try {
            $this->balancedJournal($poster);
            $this->unbalancedRefused($poster);
            $this->singleLineRefused($poster);
            $this->immutability($poster);
            $this->reversal($poster);
            $this->duplicateReferenceRefused($poster);
            $this->closedPeriodRefused($poster);
            $this->lockedPeriodRefused($poster);
            $this->precision($poster);
            $this->trialBalanceBalances($poster, $trialBalance);
            $this->permissions($poster);
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
        $this->line('  Everything was rolled back; no journal was left behind.');

        $regressionOk = true;

        if (! $this->option('skip-regression')) {
            $this->newLine();
            $this->line('Phase 1 regression');
            $regressionOk = $this->call('ledger:check', ['user' => 'bhavin']) === 0;

            $this->newLine();
            $this->line('Phase 2 regression');
            $regressionOk = $this->call('investor:selftest', ['user' => 'bhavin']) === 0 && $regressionOk;
        }

        if ($failed !== [] || ! $regressionOk) {
            $this->error('FAIL');

            return self::FAILURE;
        }

        $this->info('PASS');

        return self::SUCCESS;
    }

    private function openPeriodDate(): string
    {
        $period = AccountingPeriod::where('status', AccountingPeriod::OPEN)->orderByDesc('starts_on')->first();

        return $period ? $period->starts_on->copy()->addDays(1)->toDateString() : Carbon::now()->toDateString();
    }

    /** 1. A balanced journal posts. */
    private function balancedJournal(JournalPoster $poster): void
    {
        $journal = $poster->post([
            ['account' => '1000', 'debit' => 100.00, 'memo' => 'self-test'],
            ['account' => '2000', 'credit' => 100.00, 'memo' => 'self-test'],
        ], ['date' => $this->openPeriodDate(), 'memo' => 'Self-test balanced journal']);

        $this->check(
            'Balanced journal posts',
            $journal->exists && $journal->balances() && $journal->lines->count() === 2,
            $journal->reference . ', debits ' . Money::format($journal->totalDebit())
            . ' = credits ' . Money::format($journal->totalCredit())
        );
    }

    /** 2. An unbalanced journal is refused. */
    private function unbalancedRefused(JournalPoster $poster): void
    {
        $this->check(
            'Unbalanced journal refused',
            $this->refused(fn () => $poster->post([
                ['account' => '1000', 'debit' => 100.00],
                ['account' => '2000', 'credit' => 90.00],
            ], ['date' => $this->openPeriodDate(), 'memo' => 'Self-test unbalanced'])),
            'debits 100.00 vs credits 90.00 rejected'
        );
    }

    /** 3. A journal with fewer than two lines is refused. */
    private function singleLineRefused(JournalPoster $poster): void
    {
        $this->check(
            'Single-line journal refused',
            $this->refused(fn () => $poster->post([
                ['account' => '1000', 'debit' => 100.00],
            ], ['date' => $this->openPeriodDate(), 'memo' => 'Self-test one line'])),
            'one-sided entry rejected'
        );
    }

    /** 4. A posted journal cannot be altered. */
    private function immutability(JournalPoster $poster): void
    {
        $journal = $poster->post([
            ['account' => '1000', 'debit' => 10.00],
            ['account' => '2000', 'credit' => 10.00],
        ], ['date' => $this->openPeriodDate(), 'memo' => 'Self-test immutability']);

        $headerRefused = $this->refused(fn () => $journal->update(['memo' => 'tampered']), AccountingException::class);
        $line = $journal->lines->first();
        $lineRefused = $this->refused(fn () => $line->update(['debit' => 999.00]), AccountingException::class);
        $deleteRefused = $this->refused(fn () => $line->delete(), AccountingException::class);

        $this->check(
            'Posted journal is immutable',
            $headerRefused && $lineRefused && $deleteRefused,
            'header edit, line edit and line delete all refused'
        );
    }

    /** 5. Reversal posts the mirror image and leaves the original intact. */
    private function reversal(JournalPoster $poster): void
    {
        $original = $poster->post([
            ['account' => '6000', 'debit' => 55.00],
            ['account' => '1000', 'credit' => 55.00],
        ], ['date' => $this->openPeriodDate(), 'memo' => 'Self-test to be reversed']);

        $reversal = $poster->reverse($original, 'Self-test reversal', null, $this->openPeriodDate());
        $original->refresh();

        $mirrored = abs($reversal->lines->firstWhere('account_id', $original->lines->first()->account_id)->credit - 55.00) < 0.00000001;

        $this->check(
            'Reversal mirrors and marks the original',
            $reversal->isReversal()
            && $reversal->balances()
            && $mirrored
            && $original->status === Journal::REVERSED
            && $original->reversed_by_journal_id === $reversal->id,
            $original->reference . ' reversed by ' . $reversal->reference
        );

        $this->check(
            'Double reversal refused',
            $this->refused(fn () => $poster->reverse($original, 'again')),
            'already-reversed journal cannot be reversed twice'
        );
    }

    /** 6. A duplicate reference is refused by the unique key. */
    private function duplicateReferenceRefused(JournalPoster $poster): void
    {
        $date = $this->openPeriodDate();

        $first = $poster->post([
            ['account' => '1000', 'debit' => 5.00],
            ['account' => '2000', 'credit' => 5.00],
        ], ['date' => $date, 'memo' => 'Self-test reference']);

        $duplicated = false;

        try {
            $poster->post([
                ['account' => '1000', 'debit' => 5.00],
                ['account' => '2000', 'credit' => 5.00],
            ], ['date' => $date, 'memo' => 'Self-test duplicate', 'reference' => $first->reference]);
        } catch (\Throwable) {
            $duplicated = true;
        }

        $this->check('Duplicate reference refused', $duplicated, 'unique key rejected ' . $first->reference);
    }

    /** 7 & 8. Closed and locked periods refuse postings. */
    private function closedPeriodRefused(JournalPoster $poster): void
    {
        $this->periodStatusRefuses($poster, AccountingPeriod::CLOSED, 'Posting into a closed period refused');
    }

    private function lockedPeriodRefused(JournalPoster $poster): void
    {
        $this->periodStatusRefuses($poster, AccountingPeriod::LOCKED, 'Posting into a locked period refused');
    }

    private function periodStatusRefuses(JournalPoster $poster, string $status, string $name): void
    {
        $period = AccountingPeriod::orderBy('starts_on')->first();

        if (! $period) {
            $this->check($name, false, 'no period exists');

            return;
        }

        $was = $period->status;
        $period->update(['status' => $status]);

        $refused = $this->refused(fn () => $poster->post([
            ['account' => '1000', 'debit' => 1.00],
            ['account' => '2000', 'credit' => 1.00],
        ], ['date' => $period->starts_on->toDateString(), 'memo' => 'Self-test ' . $status]));

        $period->update(['status' => $was]);

        $this->check($name, $refused, 'period ' . $period->code . ' as ' . $status . ' rejected the posting');
    }

    /** 9. Eight decimal places survive a round trip. */
    private function precision(JournalPoster $poster): void
    {
        $amount = 861.06632000;

        $journal = $poster->post([
            ['account' => '7000', 'debit' => $amount],
            ['account' => '2010', 'credit' => $amount],
        ], ['date' => $this->openPeriodDate(), 'memo' => 'Self-test precision']);

        $stored = (float) $journal->lines->firstWhere('debit', '>', 0)->debit;

        $this->check(
            'Eight-decimal precision preserved',
            abs($stored - $amount) < 0.00000001 && $journal->balances(),
            'stored ' . Money::exact($stored) . ' for ' . Money::exact($amount)
        );
    }

    /** 10. The trial balance balances. */
    private function trialBalanceBalances(JournalPoster $poster, TrialBalance $trialBalance): void
    {
        $report = $trialBalance->build();

        $this->check(
            'Trial balance: debits equal credits',
            $report['balanced'],
            'debits ' . Money::format($report['total_debits'])
            . ' credits ' . Money::format($report['total_credits'])
            . ' difference ' . Money::exact($report['difference'])
        );
    }

    /** 11. Permissions are enforced. */
    private function permissions(JournalPoster $poster): void
    {
        // An admin with no roles holds nothing. Not persisted: this only exercises
        // the grant lookup, which is what we are testing.
        $stranger = new Admin();
        $stranger->id = -1;
        $stranger->username = 'selftest-no-permissions';

        $strangerRefused = $this->refused(fn () => $poster->post([
            ['account' => '1000', 'debit' => 1.00],
            ['account' => '2000', 'credit' => 1.00],
        ], ['date' => $this->openPeriodDate(), 'memo' => 'Self-test permission'], $stranger));

        $this->check(
            'Admin without permission cannot post',
            $strangerRefused && ! AccountingPermission::allows($stranger, AccountingPermission::JOURNAL_POST),
            'posting refused for an admin holding no grants'
        );

        $superAdmin = Admin::all()->first(fn (Admin $a) => $a->isSuperAdmin());

        if ($superAdmin) {
            $journal = $poster->post([
                ['account' => '1000', 'debit' => 2.00],
                ['account' => '2000', 'credit' => 2.00],
            ], ['date' => $this->openPeriodDate(), 'memo' => 'Self-test super admin'], $superAdmin);

            $this->check(
                'Super Admin may post, and is recorded as the actor',
                $journal->exists && $journal->posted_by === $superAdmin->id,
                $journal->reference . ' posted by ' . $superAdmin->username
            );
        } else {
            $this->check('Super Admin may post', false, 'no Super Admin found to test with');
        }
    }

    private function refused(callable $action, string $expected = PostingRefused::class): bool
    {
        try {
            $action();
        } catch (\Throwable $e) {
            return $e instanceof $expected;
        }

        return false;
    }

    private function check(string $name, bool $passed, string $detail = ''): void
    {
        $this->results[] = [$name, $passed, $detail];
    }
}
