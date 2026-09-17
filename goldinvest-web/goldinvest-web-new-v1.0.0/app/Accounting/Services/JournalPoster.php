<?php

namespace App\Accounting\Services;

use App\Accounting\Exceptions\PostingRefused;
use App\Accounting\Models\Account;
use App\Accounting\Models\AccountingPeriod;
use App\Accounting\Models\Journal;
use App\Accounting\Models\JournalLine;
use App\Accounting\Security\AccountingPermission;
use App\Investor\Services\SequenceAllocator;
use App\Models\Admin\Admin;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * The only thing that writes to the general ledger.
 *
 * Everything arrives through post(): a set of lines, each naming an account and
 * either a debit or a credit. The poster refuses anything that would make the
 * ledger untrue — a journal that does not balance, a single-sided entry, an
 * amount of zero, a posting into a period that has been closed, an actor
 * without the permission — and it refuses before writing, not afterwards.
 *
 * Corrections are reversing journals. Nothing here can edit what was posted.
 */
class JournalPoster
{
    /** Money is stored to 8 decimal places; below that is representation noise. */
    private const EPSILON = 0.00000001;

    public function __construct(private readonly SequenceAllocator $sequences)
    {
    }

    /**
     * Post a journal.
     *
     * @param  array<int, array{account: string|Account, debit?: float, credit?: float,
     *                          memo?: string, gold_lot_id?: int, user_id?: int}>  $lines
     * @param  array<string, mixed>  $context  date, memo, source_type, source_id, meta
     */
    public function post(array $lines, array $context = [], ?Admin $actor = null): Journal
    {
        AccountingPermission::assert($actor, AccountingPermission::JOURNAL_POST);

        $memo = trim((string) ($context['memo'] ?? ''));

        if ($memo === '') {
            throw new PostingRefused('Every journal needs a memo saying what it records.');
        }

        $date = isset($context['date']) ? Carbon::parse($context['date']) : Carbon::now();
        $prepared = $this->prepare($lines);
        $period = $this->periodFor($date);

        return DB::transaction(function () use ($prepared, $context, $memo, $date, $period, $actor) {
            $journal = Journal::create([
                'reference'            => $context['reference'] ?? $this->sequences->next('JV', $date),
                'journal_date'         => $date->toDateString(),
                'accounting_period_id' => $period->id,
                'memo'                 => $memo,
                'source_type'          => $context['source_type'] ?? 'manual',
                'source_id'            => $context['source_id'] ?? null,
                'status'               => Journal::POSTED,
                'posted_by'            => $actor?->id,
                'posted_at'            => Carbon::now(),
                'reverses_journal_id'  => $context['reverses_journal_id'] ?? null,
                'reversal_reason'      => $context['reversal_reason'] ?? null,
                'meta'                 => $context['meta'] ?? null,
            ]);

            foreach ($prepared as $index => $line) {
                JournalLine::create([
                    'journal_id'  => $journal->id,
                    'line_no'     => $index + 1,
                    'account_id'  => $line['account_id'],
                    'debit'       => $line['debit'],
                    'credit'      => $line['credit'],
                    'memo'        => $line['memo'],
                    'gold_lot_id' => $line['gold_lot_id'],
                    'user_id'     => $line['user_id'],
                ]);
            }

            return $journal->load('lines');
        });
    }

    /**
     * Reverse a posted journal by posting its mirror image.
     *
     * The original is left exactly as it was and simply learns that it has been
     * reversed. Both entries stay in the account, which is the point: an
     * auditor can see that something was posted and then undone, and why.
     */
    public function reverse(Journal $journal, string $reason, ?Admin $actor = null, ?string $date = null): Journal
    {
        AccountingPermission::assert($actor, AccountingPermission::JOURNAL_REVERSE);

        if ($journal->status !== Journal::POSTED) {
            throw new PostingRefused('Journal ' . $journal->reference . ' is not in a state that can be reversed.');
        }

        if ($journal->reversed_by_journal_id !== null) {
            throw new PostingRefused(
                'Journal ' . $journal->reference . ' has already been reversed by journal #'
                . $journal->reversed_by_journal_id . '.'
            );
        }

        if (trim($reason) === '') {
            throw new PostingRefused('A reversal needs a reason.');
        }

        $journal->loadMissing('lines');

        $mirrored = $journal->lines->map(fn (JournalLine $line) => [
            'account_id'  => $line->account_id,
            'debit'       => $line->credit,
            'credit'      => $line->debit,
            'memo'        => $line->memo,
            'gold_lot_id' => $line->gold_lot_id,
            'user_id'     => $line->user_id,
        ])->all();

        return DB::transaction(function () use ($journal, $mirrored, $reason, $actor, $date) {
            $reversal = $this->post($mirrored, [
                'date'                => $date ?? Carbon::now()->toDateString(),
                'memo'                => 'Reversal of ' . $journal->reference . ': ' . $reason,
                'source_type'         => $journal->source_type,
                'source_id'           => $journal->source_id,
                'reverses_journal_id' => $journal->id,
                'reversal_reason'     => $reason,
            ], $actor);

            // The one sanctioned change to a posted journal: recording that it has
            // been undone. Everything about what it originally said is untouched.
            $journal->status = Journal::REVERSED;
            $journal->reversed_by_journal_id = $reversal->id;
            $journal->save();

            return $reversal;
        });
    }

    /**
     * Validates the lines and resolves accounts, refusing anything that could
     * not be a real journal.
     */
    private function prepare(array $lines): array
    {
        if (count($lines) < 2) {
            throw new PostingRefused(
                'A journal needs at least two lines: money has to come from somewhere and go somewhere.'
            );
        }

        $prepared = [];
        $totalDebit = 0.0;
        $totalCredit = 0.0;

        foreach ($lines as $position => $line) {
            $account = $this->resolveAccount($line['account'] ?? $line['account_id'] ?? null, $position);

            $debit = round((float) ($line['debit'] ?? 0), 8);
            $credit = round((float) ($line['credit'] ?? 0), 8);

            if ($debit < 0 || $credit < 0) {
                throw new PostingRefused(
                    'Line ' . ($position + 1) . ' has a negative amount. A debit of minus something is a credit; '
                    . 'say which one it is.'
                );
            }

            if ($debit > self::EPSILON && $credit > self::EPSILON) {
                throw new PostingRefused('Line ' . ($position + 1) . ' is both a debit and a credit. It can only be one.');
            }

            if ($debit <= self::EPSILON && $credit <= self::EPSILON) {
                throw new PostingRefused('Line ' . ($position + 1) . ' moves nothing.');
            }

            if (! $account->active) {
                throw new PostingRefused('Account ' . $account->label() . ' is not active.');
            }

            $totalDebit += $debit;
            $totalCredit += $credit;

            $prepared[] = [
                'account_id'  => $account->id,
                'debit'       => $debit,
                'credit'      => $credit,
                'memo'        => $line['memo'] ?? null,
                'gold_lot_id' => $line['gold_lot_id'] ?? null,
                'user_id'     => $line['user_id'] ?? null,
            ];
        }

        $difference = round($totalDebit - $totalCredit, 8);

        if (abs($difference) > self::EPSILON) {
            throw new PostingRefused(
                'Journal does not balance: debits ' . number_format($totalDebit, 8)
                . ' against credits ' . number_format($totalCredit, 8)
                . ', a difference of ' . number_format($difference, 8) . '.'
            );
        }

        return $prepared;
    }

    /**
     * Accepts an Account, an account code, or an account id.
     *
     * Ids matter because a reversal mirrors lines that already carry one; codes
     * matter because everything a human writes uses them.
     */
    private function resolveAccount(Account|string|int|null $account, int $position): Account
    {
        if ($account instanceof Account) {
            return $account;
        }

        if ($account === null || $account === '') {
            throw new PostingRefused('Line ' . ($position + 1) . ' names no account.');
        }

        $found = is_int($account)
            ? Account::find($account)
            : Account::where('code', $account)->first();

        if (! $found) {
            throw new PostingRefused(
                'Line ' . ($position + 1) . ' names account "' . $account . '", which does not exist.'
            );
        }

        return $found;
    }

    private function periodFor(Carbon $date): AccountingPeriod
    {
        $period = AccountingPeriod::covering($date);

        if (! $period) {
            throw new PostingRefused(
                'No accounting period covers ' . $date->toDateString()
                . '. Create it with accounting:install before posting.'
            );
        }

        if (! $period->acceptsPostings()) {
            throw new PostingRefused(
                'Period ' . $period->code . ' is ' . $period->status
                . ' and will not accept postings. Reopen it first if this entry genuinely belongs there.'
            );
        }

        return $period;
    }
}
