<?php

namespace App\Accounting\Services;

use App\Accounting\Exceptions\AccountingException;
use App\Accounting\Models\BankReconciliation;
use App\Accounting\Models\BankStatementLine;
use App\Accounting\Models\CashAccount;
use App\Accounting\Models\JournalLine;
use App\Accounting\Security\AccountingPermission;
use App\Investor\Services\SequenceAllocator;
use App\Investor\Support\Money;
use App\Models\Admin\Admin;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Holding the company's books against the bank's.
 *
 * The one rule that shapes everything here: reconciling never changes
 * accounting. Matching a statement line to a journal line records that the two
 * refer to the same event; it does not adjust, create or correct a journal. If
 * the bank and the ledger disagree, that disagreement is the output, not
 * something to be smoothed away.
 *
 * Fixing a genuine error found this way is a separate, deliberate act: post a
 * correcting journal, then reconcile again.
 */
class BankReconciliationService
{
    private const EPSILON = 0.00000001;

    public function __construct(private readonly SequenceAllocator $sequences)
    {
    }

    /**
     * Records statement lines. Importing the same statement twice adds nothing,
     * because each line is fingerprinted.
     *
     * @return array{imported: int, duplicates: int, lines: array<int, BankStatementLine>}
     */
    public function import(CashAccount $account, array $rows, ?string $statementRef = null, ?Admin $actor = null): array
    {
        AccountingPermission::assert($actor, AccountingPermission::BANK_IMPORT);

        $imported = [];
        $duplicates = 0;

        foreach ($rows as $row) {
            $date = Carbon::parse($row['date'])->toDateString();
            $amount = round((float) $row['amount'], 8);
            $description = trim((string) ($row['description'] ?? ''));

            if ($description === '') {
                throw new AccountingException('Every statement line needs a description.');
            }

            if (abs($amount) <= self::EPSILON) {
                throw new AccountingException('A statement line of zero on ' . $date . ' means nothing happened.');
            }

            $fingerprint = BankStatementLine::fingerprintFor(
                $account->id, $date, $amount, $description, $row['external_ref'] ?? null
            );

            if (BankStatementLine::where('cash_account_id', $account->id)->where('fingerprint', $fingerprint)->exists()) {
                $duplicates++;
                continue;
            }

            $imported[] = BankStatementLine::create([
                'cash_account_id' => $account->id,
                'statement_ref'   => $statementRef,
                'value_date'      => $date,
                'description'     => $description,
                'amount'          => $amount,
                'external_ref'    => $row['external_ref'] ?? null,
                'fingerprint'     => $fingerprint,
                'status'          => BankStatementLine::UNMATCHED,
                'imported_by'     => $actor?->id,
            ]);
        }

        return ['imported' => count($imported), 'duplicates' => $duplicates, 'lines' => $imported];
    }

    /**
     * Matches a statement line to the journal line that records the same event.
     *
     * Refuses if they disagree about the amount or the account, because a match
     * that papers over a difference is worse than no match: it hides exactly the
     * thing a reconciliation exists to surface.
     */
    public function match(BankStatementLine $line, JournalLine $journalLine, ?Admin $actor = null): BankStatementLine
    {
        AccountingPermission::assert($actor, AccountingPermission::BANK_MATCH);

        if ($line->status === BankStatementLine::MATCHED) {
            throw new AccountingException('Statement line #' . $line->id . ' is already matched.');
        }

        $account = $line->cashAccount;

        if ($journalLine->account_id !== $account->gl_account_id) {
            throw new AccountingException(
                'That journal line is against ' . ($journalLine->account->code ?? '?')
                . ', not ' . $account->label() . '.'
            );
        }

        if (BankStatementLine::where('matched_journal_line_id', $journalLine->id)->exists()) {
            throw new AccountingException('That journal line is already matched to another statement line.');
        }

        // The bank's sign convention and ours: money arriving is a debit here and
        // a positive amount there.
        $ledgerAmount = $journalLine->signedAmount();

        if (abs($ledgerAmount - $line->amount) > self::EPSILON) {
            throw new AccountingException(
                'Amounts do not agree: the bank says ' . Money::exact($line->amount)
                . ' and the ledger says ' . Money::exact($ledgerAmount)
                . '. Post a correction rather than matching these together.'
            );
        }

        $line->update([
            'status'                  => BankStatementLine::MATCHED,
            'matched_journal_line_id' => $journalLine->id,
            'matched_by'              => $actor?->id,
            'matched_at'              => Carbon::now(),
        ]);

        return $line->refresh();
    }

    public function unmatch(BankStatementLine $line, ?Admin $actor = null): BankStatementLine
    {
        AccountingPermission::assert($actor, AccountingPermission::BANK_MATCH);

        $line->update([
            'status'                  => BankStatementLine::UNMATCHED,
            'matched_journal_line_id' => null,
            'matched_by'              => null,
            'matched_at'              => null,
        ]);

        return $line->refresh();
    }

    /** Sets a line aside with a stated reason. Never silently. */
    public function ignore(BankStatementLine $line, string $reason, ?Admin $actor = null): BankStatementLine
    {
        AccountingPermission::assert($actor, AccountingPermission::BANK_MATCH);

        if (trim($reason) === '') {
            throw new AccountingException('Ignoring a statement line needs a reason.');
        }

        $line->update([
            'status'        => BankStatementLine::IGNORED,
            'ignore_reason' => $reason,
            'matched_by'    => $actor?->id,
            'matched_at'    => Carbon::now(),
        ]);

        return $line->refresh();
    }

    /**
     * Suggests matches where a statement line and an unmatched journal line agree
     * on amount and are within a few days of each other. Suggests only: nothing
     * is matched without someone deciding.
     */
    public function suggest(CashAccount $account, int $dayTolerance = 3): array
    {
        $suggestions = [];
        $matchedIds = BankStatementLine::whereNotNull('matched_journal_line_id')->pluck('matched_journal_line_id')->all();

        $candidates = JournalLine::where('account_id', $account->gl_account_id)
            ->whereNotIn('id', $matchedIds ?: [0])
            ->with('journal')
            ->get();

        foreach (BankStatementLine::where('cash_account_id', $account->id)
                     ->where('status', BankStatementLine::UNMATCHED)->get() as $line) {
            foreach ($candidates as $candidate) {
                if (abs($candidate->signedAmount() - $line->amount) > self::EPSILON) {
                    continue;
                }

                if (abs($candidate->journal->journal_date->diffInDays($line->value_date)) > $dayTolerance) {
                    continue;
                }

                $suggestions[] = ['statement_line' => $line, 'journal_line' => $candidate];
                break;
            }
        }

        return $suggestions;
    }

    /** The state of play for an account at a date, without changing anything. */
    public function summarise(CashAccount $account, string $asAt, float $statementClosingBalance): array
    {
        $lines = BankStatementLine::where('cash_account_id', $account->id)
            ->whereDate('value_date', '<=', $asAt)
            ->get();

        $ledgerBalance = $account->balance($asAt);
        $difference = round($statementClosingBalance - $ledgerBalance, 8);

        return [
            'account'          => $account,
            'as_at'            => $asAt,
            'statement_closing' => round($statementClosingBalance, 8),
            'ledger_balance'   => $ledgerBalance,
            'difference'       => $difference,
            'reconciles'       => abs($difference) <= self::EPSILON,
            'lines_total'      => $lines->count(),
            'lines_matched'    => $lines->where('status', BankStatementLine::MATCHED)->count(),
            'lines_unmatched'  => $lines->where('status', BankStatementLine::UNMATCHED)->count(),
            'lines_ignored'    => $lines->where('status', BankStatementLine::IGNORED)->count(),
            'unmatched'        => $lines->where('status', BankStatementLine::UNMATCHED)->values(),
        ];
    }

    /**
     * Completes a reconciliation, which is an assertion that on this date the
     * company's books and the bank agreed.
     *
     * It will not complete over an unexplained difference or an unresolved line,
     * and the person completing it may not be the person who imported the
     * statement — a reconciliation signed off by the only pair of eyes that has
     * seen the evidence is not a control.
     */
    public function complete(
        CashAccount $account,
        string $asAt,
        float $statementClosingBalance,
        ?Admin $actor = null,
        ?string $notes = null,
        ?string $sodExceptionReason = null,
    ): BankReconciliation {
        AccountingPermission::assert($actor, AccountingPermission::BANK_RECONCILE);

        $summary = $this->summarise($account, $asAt, $statementClosingBalance);

        if ($summary['lines_unmatched'] > 0) {
            throw new AccountingException(
                $summary['lines_unmatched'] . ' statement line(s) are still unmatched. '
                . 'Match them, or set them aside with a reason, before completing.'
            );
        }

        if (! $summary['reconciles']) {
            throw new AccountingException(
                'The bank says ' . Money::format($summary['statement_closing'])
                . ' and the ledger says ' . Money::format($summary['ledger_balance'])
                . ', a difference of ' . Money::exact($summary['difference'])
                . '. Find it and post a correction; do not complete over it.'
            );
        }

        $exception = $this->resolveSegregation($account, $asAt, $actor, $sodExceptionReason);

        return DB::transaction(fn () => BankReconciliation::create([
            'reference'                 => $this->sequences->next('BRC', Carbon::parse($asAt)),
            'cash_account_id'           => $account->id,
            'as_at'                     => $asAt,
            'statement_closing_balance' => $summary['statement_closing'],
            'ledger_balance'            => $summary['ledger_balance'],
            'difference'                => $summary['difference'],
            'lines_total'               => $summary['lines_total'],
            'lines_matched'             => $summary['lines_matched'],
            'lines_unmatched'           => $summary['lines_unmatched'],
            'lines_ignored'             => $summary['lines_ignored'],
            'status'                    => BankReconciliation::COMPLETED,
            'performed_by'              => $actor?->id,
            'completed_by'              => $actor?->id,
            'completed_at'              => Carbon::now(),
            'notes'                     => $notes,
            'sod_exception'             => $exception !== null,
            'sod_exception_reason'      => $exception,
            'snapshot'                  => [
                'statement_closing' => $summary['statement_closing'],
                'ledger_balance'    => $summary['ledger_balance'],
                'matched'           => $summary['lines_matched'],
                'ignored'           => $summary['lines_ignored'],
            ],
        ]));
    }

    /**
     * The person completing must not be the only person who has seen the
     * evidence.
     *
     * A single-admin environment cannot satisfy that, so the rule can be
     * relaxed by configuration. Relaxing it is not the same as removing it:
     * only a Super Admin may take the exception, a reason is required, and the
     * reason is returned so it can be recorded against the reconciliation. The
     * control is waived visibly rather than bypassed.
     *
     * @return string|null the exception reason when one was taken
     */
    private function resolveSegregation(CashAccount $account, string $asAt, ?Admin $actor, ?string $reason): ?string
    {
        // No actor is the console, where reaching a shell is already a greater
        // privilege than any of these grants.
        if ($actor === null) {
            return null;
        }

        $importers = BankStatementLine::where('cash_account_id', $account->id)
            ->whereDate('value_date', '<=', $asAt)
            ->whereNotNull('imported_by')
            ->pluck('imported_by')
            ->unique();

        $selfSignoff = $importers->count() === 1 && (int) $importers->first() === (int) $actor->id;

        if ($importers->isEmpty() || ! $selfSignoff) {
            return null;
        }

        if (! config('accounting.reconciliation.allow_self_signoff', false)) {
            throw new AccountingException(
                'The statement was imported by the same person completing the reconciliation. '
                . 'Somebody else must sign it off, or enable ACCOUNTING_ALLOW_SELF_SIGNOFF for a '
                . 'single-admin environment.'
            );
        }

        // Relaxing the rule does not relax who may take the exception.
        if (! $actor->isSuperAdmin()) {
            throw new AccountingException(
                'Only a Super Admin may sign off a reconciliation they imported the statement for.'
            );
        }

        if (trim((string) $reason) === '') {
            throw new AccountingException(
                'Signing off your own reconciliation needs a reason, which is recorded against it.'
            );
        }

        return trim($reason);
    }
}
