<?php

namespace App\Console\Commands;

use App\Accounting\Exceptions\AccountingException;
use App\Accounting\Models\AccountingPeriod;
use App\Accounting\Models\BankStatementLine;
use App\Accounting\Models\CashAccount;
use App\Accounting\Models\JournalLine;
use App\Accounting\Security\AccountingPermission;
use App\Accounting\Services\BankReconciliationService;
use App\Accounting\Services\CashAccountService;
use App\Accounting\Services\CashService;
use App\Accounting\Services\JournalPoster;
use App\Accounting\Services\TrialBalance;
use App\Investor\Support\Money;
use App\Models\Admin\Admin;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * The Phase 3B acceptance run.
 *
 * Creates real cash and bank accounts, moves money between them, imports a
 * statement and reconciles it — all inside a transaction that is always rolled
 * back, so the company's books are exactly as they were when this finishes.
 */
class CashSelfTestCommand extends Command
{
    protected $signature = 'cash:selftest {--skip-regression : cash and bank tests only}';

    protected $description = 'Verify cash accounts, movements, statement matching and reconciliation';

    private array $results = [];
    private string $date;

    public function handle(
        CashAccountService $accounts,
        CashService $cash,
        BankReconciliationService $bank,
        JournalPoster $poster,
        TrialBalance $trialBalance,
    ): int {
        $this->line('Cash and bank self-test (Phase 3B)');
        $this->line(str_repeat('-', 60));

        $period = AccountingPeriod::where('status', AccountingPeriod::OPEN)->orderByDesc('starts_on')->first();

        if (! $period) {
            $this->error('No open accounting period. Run accounting:install first.');

            return self::FAILURE;
        }

        $this->date = $period->starts_on->copy()->addDays(2)->toDateString();

        DB::beginTransaction();

        try {
            [$cashBox, $bankAccount] = $this->createAccounts($accounts);
            $this->cashMovements($cash, $cashBox, $bankAccount);
            $this->transfer($cash, $bankAccount, $cashBox);
            $this->negativeRefused($cash, $cashBox);
            $this->duplicateReferenceRefused($poster);
            $this->immutabilityAndReversal($cash, $bankAccount, $poster);
            $this->statementMatching($bank, $cash, $bankAccount);
            $this->reconciliation($bank, $bankAccount);
            $this->permissions($accounts, $cash, $cashBox);
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
        $this->line('  Everything was rolled back; no account, journal or statement line remains.');

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

    /** 1 & 2. Cash and bank accounts, each owning exactly one GL account. */
    private function createAccounts(CashAccountService $service): array
    {
        $cashBox = $service->create(['name' => 'Self-test Cash Box', 'type' => CashAccount::TYPE_CASH, 'code' => 'ST-CASH']);

        $this->check(
            'Create cash account',
            $cashBox->exists && $cashBox->glAccount !== null && $cashBox->type === CashAccount::TYPE_CASH,
            $cashBox->label() . ' -> GL ' . $cashBox->glAccount->code
        );

        $bank = $service->create([
            'name' => 'Self-test Bank', 'type' => CashAccount::TYPE_BANK, 'code' => 'ST-BANK',
            'bank_name' => 'Test Bank', 'account_ref' => '0001',
        ]);

        $uniqueGl = $cashBox->gl_account_id !== $bank->gl_account_id;

        $this->check(
            'Create bank account with its own GL account',
            $bank->exists && $uniqueGl,
            $bank->label() . ' -> GL ' . $bank->glAccount->code . ', distinct from the cash box'
        );

        return [$cashBox, $bank];
    }

    /** 3-6. Receipts and payments on both kinds of account post balanced journals. */
    private function cashMovements(CashService $cash, CashAccount $cashBox, CashAccount $bank): void
    {
        $cases = [
            ['Cash receipt posts a balanced journal', fn () => $cash->receipt($cashBox, 500.00, '2000', $this->ctx('Self-test cash receipt')), $cashBox, 500.00],
            ['Bank receipt posts a balanced journal', fn () => $cash->receipt($bank, 2000.00, '2000', $this->ctx('Self-test bank receipt')), $bank, 2000.00],
            ['Cash payment posts a balanced journal', fn () => $cash->payment($cashBox, 120.00, '6000', $this->ctx('Self-test cash payment')), $cashBox, -120.00],
            ['Bank payment posts a balanced journal', fn () => $cash->payment($bank, 300.00, '6100', $this->ctx('Self-test bank payment')), $bank, -300.00],
        ];

        foreach ($cases as [$name, $action, $account, $expectedChange]) {
            $before = $account->balance();
            $journal = $action();
            $after = $account->fresh()->balance();
            $moved = round($after - $before, 8);

            $this->check(
                $name,
                $journal->balances() && abs($moved - $expectedChange) < 0.00000001,
                $journal->reference . ', ' . $account->code . ' moved ' . Money::signed($moved)
                . ' to ' . Money::format($after)
            );
        }
    }

    /** 7. A transfer creates both sides in one journal. */
    private function transfer(CashService $cash, CashAccount $from, CashAccount $to): void
    {
        $fromBefore = $from->balance();
        $toBefore = $to->balance();

        $journal = $cash->transfer($from, $to, 400.00, $this->ctx('Self-test transfer'));

        $fromAfter = $from->fresh()->balance();
        $toAfter = $to->fresh()->balance();

        $this->check(
            'Transfer creates both sides in one journal',
            $journal->lines->count() === 2
            && $journal->balances()
            && abs(($fromBefore - $fromAfter) - 400.00) < 0.00000001
            && abs(($toAfter - $toBefore) - 400.00) < 0.00000001,
            $journal->reference . ': ' . $from->code . ' ' . Money::format($fromAfter)
            . ', ' . $to->code . ' ' . Money::format($toAfter)
        );

        $this->check(
            'Transfer to the same account refused',
            $this->refused(fn () => $cash->transfer($from, $from, 10.00, $this->ctx('Self-test same account'))),
            'a transfer needs two different accounts'
        );
    }

    /** Money the company does not have cannot leave it. */
    private function negativeRefused(CashService $cash, CashAccount $cashBox): void
    {
        $balance = $cashBox->fresh()->balance();

        $this->check(
            'Overdrawing a cash account refused',
            $this->refused(fn () => $cash->payment($cashBox, $balance + 1000.00, '6100', $this->ctx('Self-test overdraw'))),
            'paying ' . Money::format($balance + 1000.00) . ' from a balance of ' . Money::format($balance) . ' rejected'
        );
    }

    /** 8. A duplicate journal reference is refused. */
    private function duplicateReferenceRefused(JournalPoster $poster): void
    {
        $first = $poster->post([
            ['account' => '1090', 'debit' => 7.00],
            ['account' => '2000', 'credit' => 7.00],
        ], ['date' => $this->date, 'memo' => 'Self-test 3B reference']);

        $refused = false;

        try {
            $poster->post([
                ['account' => '1090', 'debit' => 7.00],
                ['account' => '2000', 'credit' => 7.00],
            ], ['date' => $this->date, 'memo' => 'Self-test 3B duplicate', 'reference' => $first->reference]);
        } catch (\Throwable) {
            $refused = true;
        }

        $this->check('Duplicate journal reference refused', $refused, 'unique key rejected ' . $first->reference);
    }

    /** 9 & 10. Cash journals are immutable and corrected by reversal. */
    private function immutabilityAndReversal(CashService $cash, CashAccount $bank, JournalPoster $poster): void
    {
        $journal = $cash->payment($bank, 75.00, '6030', $this->ctx('Self-test to be reversed'));

        $editRefused = $this->refused(fn () => $journal->update(['memo' => 'tampered']), AccountingException::class);
        $journal->refresh();

        $before = $bank->fresh()->balance();
        $reversal = $poster->reverse($journal, 'Self-test correction', null, $this->date);
        $after = $bank->fresh()->balance();

        $this->check('Posted cash journal is immutable', $editRefused, 'edit refused on ' . $journal->reference);

        $this->check(
            'Reversal restores the cash balance',
            $reversal->balances() && abs(($after - $before) - 75.00) < 0.00000001,
            $journal->reference . ' reversed by ' . $reversal->reference
            . ', balance back to ' . Money::format($after)
        );
    }

    /** 11-13. Statement import, matching, unmatched visibility, discrepancy detection. */
    private function statementMatching(BankReconciliationService $bank, CashService $cash, CashAccount $account): void
    {
        $journal = $cash->receipt($account, 1500.00, '2000', $this->ctx('Self-test matched receipt'));
        $journalLine = $journal->lines->firstWhere('account_id', $account->gl_account_id);

        $result = $bank->import($account, [
            ['date' => $this->date, 'description' => 'Deposit from investor', 'amount' => 1500.00, 'external_ref' => 'BNK-1'],
            ['date' => $this->date, 'description' => 'Bank charge not in our books', 'amount' => -25.00, 'external_ref' => 'BNK-2'],
        ], 'ST-STMT-1');

        $this->check(
            'Statement lines imported',
            $result['imported'] === 2 && $result['duplicates'] === 0,
            '2 lines imported'
        );

        $again = $bank->import($account, [
            ['date' => $this->date, 'description' => 'Deposit from investor', 'amount' => 1500.00, 'external_ref' => 'BNK-1'],
        ], 'ST-STMT-1');

        $this->check(
            'Re-importing the same statement adds nothing',
            $again['imported'] === 0 && $again['duplicates'] === 1,
            'fingerprint caught the duplicate'
        );

        $line = $result['lines'][0];
        $matched = $bank->match($line, $journalLine);

        $this->check(
            'Statement line matches a journal line',
            $matched->status === BankStatementLine::MATCHED && $matched->matched_journal_line_id === $journalLine->id,
            'line #' . $matched->id . ' matched to journal line #' . $journalLine->id
        );

        $mismatch = $result['lines'][1];

        $this->check(
            'Matching amounts that disagree is refused',
            $this->refused(fn () => $bank->match($mismatch, $journalLine), AccountingException::class),
            'bank -25.00 against ledger 1,500.00 rejected'
        );

        $unmatched = BankStatementLine::where('cash_account_id', $account->id)
            ->where('status', BankStatementLine::UNMATCHED)->get();

        $this->check(
            'Unmatched statement line stays visible',
            $unmatched->count() === 1 && abs($unmatched->first()->amount + 25.00) < 0.00000001,
            'the 25.00 bank charge is still showing as unmatched'
        );
    }

    /** 13 & 15. Discrepancy detected; reconciliation completes only when it agrees. */
    private function reconciliation(BankReconciliationService $bank, CashAccount $account): void
    {
        $ledger = $account->fresh()->balance();
        $summary = $bank->summarise($account, $this->date, $ledger - 25.00);

        $this->check(
            'Reconciliation detects a discrepancy',
            ! $summary['reconciles'] && abs($summary['difference'] + 25.00) < 0.00000001,
            'difference of ' . Money::exact($summary['difference']) . ' reported, not hidden'
        );

        $this->check(
            'Completing over an unmatched line refused',
            $this->refused(fn () => $bank->complete($account, $this->date, $ledger), AccountingException::class),
            'the unmatched bank charge blocks completion'
        );

        // Set the charge aside with a reason, then post it properly so the books
        // and the bank actually agree.
        $charge = BankStatementLine::where('cash_account_id', $account->id)
            ->where('status', BankStatementLine::UNMATCHED)->first();
        $bank->ignore($charge, 'Bank charge to be posted separately');

        $this->check(
            'Completing over a difference refused',
            $this->refused(fn () => $bank->complete($account, $this->date, $ledger - 25.00), AccountingException::class),
            'a 25.00 difference cannot be signed off'
        );

        $record = $bank->complete($account, $this->date, $ledger, null, 'Self-test reconciliation');

        $this->check(
            'Reconciliation completes when the books agree',
            $record->status === 'completed' && $record->reconciles(),
            $record->reference . ', difference ' . Money::exact($record->difference)
        );

        $this->check(
            'Completed reconciliation is immutable',
            $this->refused(fn () => $record->update(['notes' => 'tampered']), AccountingException::class),
            'edit refused on ' . $record->reference
        );
    }

    /** 14. Permissions are enforced across cash and bank operations. */
    private function permissions(CashAccountService $accounts, CashService $cash, CashAccount $cashBox): void
    {
        $stranger = new Admin();
        $stranger->id = -1;
        $stranger->username = 'selftest-no-permissions';

        $createRefused = $this->refused(fn () => $accounts->create(
            ['name' => 'Should not exist', 'type' => CashAccount::TYPE_CASH, 'code' => 'ST-NOPE'], $stranger
        ));

        $postRefused = $this->refused(fn () => $cash->payment($cashBox, 1.00, '6100', $this->ctx('Self-test permission'), $stranger));

        $this->check(
            'Admin without permission cannot create or post',
            $createRefused && $postRefused
            && ! AccountingPermission::allows($stranger, AccountingPermission::CASH_POST),
            'both refused for an admin holding no grants'
        );
    }

    /** 16. The trial balance still balances after everything above. */
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

    private function ctx(string $memo): array
    {
        return ['date' => $this->date, 'memo' => $memo];
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
