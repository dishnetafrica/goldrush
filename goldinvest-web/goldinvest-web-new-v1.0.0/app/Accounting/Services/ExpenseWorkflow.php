<?php

namespace App\Accounting\Services;

use App\Accounting\Exceptions\AccountingException;
use App\Accounting\Exceptions\PostingRefused;
use App\Accounting\Models\CashAccount;
use App\Accounting\Models\Journal;
use App\Accounting\Security\AccountingPermission;
use App\GoldTrading\Models\GoldLot;
use App\GoldTrading\Models\GoldSale;
use App\GoldTrading\Models\TradingExpense;
use App\Investor\Services\SequenceAllocator;
use App\Investor\Support\Money;
use App\Models\Admin\Admin;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * The life of a company expense, from a claim to money that has left.
 *
 * Draft, submitted, approved or rejected, posted, paid. Each step records who
 * took it and when, because an expense that nobody is named against is not
 * controlled, it is merely recorded. A rejection records why, because a refusal
 * with no reason tells the claimant nothing and teaches nobody anything.
 *
 * Two boundaries matter more than the rest.
 *
 * Nothing here writes to the general ledger. Posting hands the expense to
 * GoldTradingPoster, which hands its journal to JournalPoster, which is the
 * only thing in the application that writes a journal at all. An expense
 * therefore cannot get into the books by a route that skips the rules the books
 * are kept by.
 *
 * Nothing here decides what a cost is worth twice. A capitalised cost is
 * debited to inventory and read back by LotCostBasis; an ordinary cost is
 * debited to its expense account and read back by the deal result. A cost is
 * one or the other and never both, which is decision D4.
 */
class ExpenseWorkflow
{
    private const EPSILON = 0.00000001;

    public function __construct(
        private readonly SequenceAllocator $sequences,
        private readonly GoldTradingPoster $poster,
        private readonly CashService $cash,
        private readonly JournalPoster $journals,
    ) {
    }

    /**
     * Raise a claim.
     *
     * Nothing has been agreed at this point and nothing has reached the books.
     * A draft can be edited freely and thrown away, which is what makes it
     * possible to be strict about everything after it.
     */
    public function draft(array $attributes, ?Admin $actor = null): TradingExpense
    {
        AccountingPermission::assert($actor, AccountingPermission::EXPENSE_CREATE);

        $category = strtolower(trim((string) ($attributes['category'] ?? '')));

        if (! in_array($category, TradingExpense::CATEGORIES, true)) {
            throw new PostingRefused(
                'Category must be one of: ' . implode(', ', TradingExpense::CATEGORIES) . '.'
            );
        }

        $description = trim((string) ($attributes['description'] ?? ''));

        if ($description === '') {
            throw new PostingRefused('Say what the cost was for.');
        }

        $date = Carbon::parse($attributes['expense_date'] ?? Carbon::now());
        $amountLocal = round((float) ($attributes['amount_local'] ?? 0), 8);
        $fx = (float) ($attributes['fx_rate_to_usd'] ?? 1);

        if ($amountLocal <= self::EPSILON) {
            throw new PostingRefused('An expense must be for more than nothing.');
        }

        if ($fx <= self::EPSILON) {
            throw new PostingRefused('The exchange rate must be greater than zero.');
        }

        $lot = $this->resolveLot($attributes['gold_lot_id'] ?? null);
        $capitalised = (bool) ($attributes['capitalised'] ?? false);

        if ($capitalised) {
            $this->assertCapitalisable($category, $lot);
        }

        $payee = $this->trimmedOrNull($attributes['payee_name'] ?? null);
        $externalRef = $this->trimmedOrNull($attributes['external_ref'] ?? null);

        $this->assertNotAlreadyClaimed($payee, $externalRef);

        return DB::transaction(function () use (
            $attributes, $category, $description, $date, $amountLocal, $fx,
            $lot, $capitalised, $payee, $externalRef, $actor
        ) {
            return TradingExpense::create([
                'reference'      => $this->sequences->next('EXP', $date),
                'status'         => TradingExpense::STATUS_DRAFT,
                'expense_date'   => $date->toDateString(),
                'category'       => $category,
                'description'    => $description,
                'payee_name'     => $payee,
                'external_ref'   => $externalRef,
                'currency_code'  => strtoupper((string) ($attributes['currency_code'] ?? 'USD')),
                'amount_local'   => $amountLocal,
                'fx_rate_to_usd' => $fx,

                // The rate is units of the local currency per one US dollar, so
                // the reporting amount is a division, not a multiplication.
                'amount_usd'     => round($amountLocal / $fx, 8),

                'capitalised'    => $capitalised,
                'gold_lot_id'    => $lot?->id,
                'gold_sale_id'   => $attributes['gold_sale_id'] ?? null,
                'recorded_by'    => $actor?->id,
                'payment_status' => TradingExpense::PAYMENT_UNPAID,
                'notes'          => $this->trimmedOrNull($attributes['notes'] ?? null),
            ]);
        });
    }

    /** Change a draft. Anything further along is corrected, not edited. */
    public function amend(TradingExpense $expense, array $changes, ?Admin $actor = null): TradingExpense
    {
        AccountingPermission::assert($actor, AccountingPermission::EXPENSE_CREATE);
        $this->assertStatus($expense, [TradingExpense::STATUS_DRAFT], 'edited');

        $allowed = [
            'expense_date', 'category', 'description', 'payee_name', 'external_ref',
            'currency_code', 'amount_local', 'fx_rate_to_usd', 'capitalised',
            'gold_lot_id', 'gold_sale_id', 'notes',
        ];

        $merged = array_merge([
            'expense_date'   => $expense->expense_date->toDateString(),
            'category'       => $expense->category,
            'description'    => $expense->description,
            'payee_name'     => $expense->payee_name,
            'external_ref'   => $expense->external_ref,
            'currency_code'  => $expense->currency_code,
            'amount_local'   => $expense->amount_local,
            'fx_rate_to_usd' => $expense->fx_rate_to_usd,
            'capitalised'    => $expense->capitalised,
            'gold_lot_id'    => $expense->gold_lot_id,
            'gold_sale_id'   => $expense->gold_sale_id,
            'notes'          => $expense->notes,
        ], array_intersect_key($changes, array_flip($allowed)));

        $category = strtolower(trim((string) $merged['category']));

        if (! in_array($category, TradingExpense::CATEGORIES, true)) {
            throw new PostingRefused(
                'Category must be one of: ' . implode(', ', TradingExpense::CATEGORIES) . '.'
            );
        }

        $amountLocal = round((float) $merged['amount_local'], 8);
        $fx = (float) $merged['fx_rate_to_usd'];

        if ($amountLocal <= self::EPSILON) {
            throw new PostingRefused('An expense must be for more than nothing.');
        }

        if ($fx <= self::EPSILON) {
            throw new PostingRefused('The exchange rate must be greater than zero.');
        }

        $lot = $this->resolveLot($merged['gold_lot_id']);

        if ((bool) $merged['capitalised']) {
            $this->assertCapitalisable($category, $lot);
        }

        $payee = $this->trimmedOrNull($merged['payee_name']);
        $externalRef = $this->trimmedOrNull($merged['external_ref']);

        $this->assertNotAlreadyClaimed($payee, $externalRef, $expense->id);

        $expense->fill([
            'expense_date'   => Carbon::parse($merged['expense_date'])->toDateString(),
            'category'       => $category,
            'description'    => trim((string) $merged['description']),
            'payee_name'     => $payee,
            'external_ref'   => $externalRef,
            'currency_code'  => strtoupper((string) $merged['currency_code']),
            'amount_local'   => $amountLocal,
            'fx_rate_to_usd' => $fx,
            'amount_usd'     => round($amountLocal / $fx, 8),
            'capitalised'    => (bool) $merged['capitalised'],
            'gold_lot_id'    => $lot?->id,
            'gold_sale_id'   => $merged['gold_sale_id'],
            'notes'          => $this->trimmedOrNull($merged['notes']),
        ])->save();

        return $expense;
    }

    /** Hand the claim over to be checked. It stops being editable here. */
    public function submit(TradingExpense $expense, ?Admin $actor = null): TradingExpense
    {
        AccountingPermission::assert($actor, AccountingPermission::EXPENSE_SUBMIT);
        $this->assertStatus($expense, [TradingExpense::STATUS_DRAFT], 'submitted');

        return $this->persist($expense, [
            'status'       => TradingExpense::STATUS_SUBMITTED,
            'submitted_by' => $actor?->id,
            'submitted_at' => Carbon::now(),
        ]);
    }

    /**
     * Agree the claim.
     *
     * Not by the person who made it. An expense checked only by its claimant is
     * not a control, it is a formality — the same reasoning as bank
     * reconciliation sign-off, and relaxed on the same terms: deliberately, by
     * a Super Admin, with a reason recorded on the expense itself.
     */
    public function approve(TradingExpense $expense, ?Admin $actor = null, ?string $sodReason = null): TradingExpense
    {
        AccountingPermission::assert($actor, AccountingPermission::EXPENSE_APPROVE);
        $this->assertStatus($expense, [TradingExpense::STATUS_SUBMITTED], 'approved');

        $exception = $this->resolveSegregation($expense, $actor, $sodReason);

        return $this->persist($expense, [
            'status'               => TradingExpense::STATUS_APPROVED,
            'approved_by'          => $actor?->id,
            'approved_at'          => Carbon::now(),
            'sod_exception'        => $exception !== null,
            'sod_exception_reason' => $exception,
        ]);
    }

    /**
     * Refuse the claim, saying why. A refusal with no reason teaches nobody anything.
     *
     * An approved claim may be refused too, so long as it has not reached the
     * ledger. Approval is a decision about a claim; posting is what makes it a
     * cost. Between the two a duplicate or a mistake can still come to light,
     * and a claim that could be neither withdrawn nor posted would be stuck.
     */
    public function reject(TradingExpense $expense, string $reason, ?Admin $actor = null): TradingExpense
    {
        AccountingPermission::assert($actor, AccountingPermission::EXPENSE_APPROVE);
        $this->assertStatus($expense, [TradingExpense::STATUS_SUBMITTED, TradingExpense::STATUS_APPROVED], 'rejected');

        if ($expense->journal_id !== null) {
            throw new PostingRefused('Expense ' . $expense->label() . ' is in the ledger; reverse it rather than rejecting it.');
        }

        if (trim($reason) === '') {
            throw new PostingRefused('A rejection needs a reason; the claimant has to know what to fix.');
        }

        return $this->persist($expense, [
            'status'           => TradingExpense::STATUS_REJECTED,
            'rejected_by'      => $actor?->id,
            'rejected_at'      => Carbon::now(),
            'rejection_reason' => trim($reason),
        ]);
    }

    /**
     * Into the general ledger.
     *
     * Posting the same expense twice hands back the journal it already has
     * rather than raising a second one. A retried click, a repeated API call
     * and a re-run command all describe the same single cost, and the books
     * should record it once.
     */
    public function post(
        TradingExpense $expense,
        ?CashAccount $from = null,
        array $context = [],
        ?Admin $actor = null,
    ): Journal {
        AccountingPermission::assert($actor, AccountingPermission::EXPENSE_POST);

        if ($expense->journal_id !== null) {
            return $expense->journal()->with('lines')->first();
        }

        return $this->poster->expense($expense, $from, $context, $actor);
    }

    /**
     * The money leaves.
     *
     * Only for a cost the books already carry as owed. Paying something that
     * was never recognised would put the payment in the ledger with nothing to
     * relieve, which is how cash and expenses stop agreeing.
     */
    public function pay(
        TradingExpense $expense,
        CashAccount $from,
        array $context = [],
        ?Admin $actor = null,
    ): Journal {
        AccountingPermission::assert($actor, AccountingPermission::EXPENSE_PAY);

        // As with posting: the amount that leaves the bank comes from the record,
        // never from an instance a refused edit may have left dirty.
        $expense->refresh();

        if ($expense->payment_journal_id !== null) {
            return $expense->paymentJournal()->with('lines')->first();
        }

        if ($expense->journal_id === null) {
            throw new PostingRefused(
                'Expense ' . $expense->label() . ' has not been posted, so there is nothing recorded as owed. '
                . 'Post it first.'
            );
        }

        if ($expense->status === TradingExpense::STATUS_REVERSED) {
            throw new PostingRefused('Expense ' . $expense->label() . ' was reversed; there is nothing to pay.');
        }

        if ($expense->payment_status === TradingExpense::PAYMENT_PAID) {
            throw new PostingRefused('Expense ' . $expense->label() . ' has already been paid.');
        }

        $amount = round((float) $expense->amount_usd, 8);
        $date = $context['date'] ?? Carbon::now()->toDateString();

        return DB::transaction(function () use ($expense, $from, $amount, $date, $context, $actor) {
            // Settling what the company owes: the payable goes down, the cash
            // goes down with it. No expense account is touched, because the cost
            // was recognised when it was posted.
            $journal = $this->cash->payment($from, $amount, GoldTradingPoster::PAYABLE, [
                'date'        => $date,
                'memo'        => $context['memo'] ?? ('Payment of ' . $expense->label()
                    . ($expense->payee_name ? ' to ' . $expense->payee_name : '')),
                'source_type' => 'gold_expense_payment',
                'source_id'   => $expense->id,
            ], $actor);

            $this->persist($expense, [
                'payment_status'            => TradingExpense::PAYMENT_PAID,
                'payment_journal_id'        => $journal->id,
                'paid_by'                   => $actor?->id,
                'paid_at'                   => Carbon::now(),
                'paid_from_cash_account_id' => $from->id,
            ]);

            return $journal;
        });
    }

    /**
     * Undo a cost the company turns out not to have.
     *
     * Only while it is unpaid. Reversing an accrual says the company does not
     * owe this after all, which is a thing that can be true. Reversing a payment
     * would say the money came back, which is not — the cash left the bank and
     * the bank statement will go on saying so. A cost that was paid but booked
     * wrongly is a classification mistake, and reclassify() is what moves it.
     */
    public function reverse(TradingExpense $expense, string $reason, ?Admin $actor = null): Journal
    {
        AccountingPermission::assert($actor, AccountingPermission::EXPENSE_POST);
        AccountingPermission::assert($actor, AccountingPermission::JOURNAL_REVERSE);

        if ($expense->journal_id === null) {
            throw new PostingRefused('Expense ' . $expense->label() . ' has not been posted, so there is nothing to reverse.');
        }

        if ($expense->status === TradingExpense::STATUS_REVERSED) {
            throw new PostingRefused('Expense ' . $expense->label() . ' has already been reversed.');
        }

        if ($expense->payment_status === TradingExpense::PAYMENT_PAID) {
            throw new PostingRefused(
                'Expense ' . $expense->label() . ' has been paid. The money has left the account and the books '
                . 'must not pretend otherwise. If it was booked to the wrong place, reclassify it; if the '
                . 'supplier is refunding it, record the refund as a receipt.'
            );
        }

        if (trim($reason) === '') {
            throw new PostingRefused('A reversal needs a reason.');
        }

        return DB::transaction(function () use ($expense, $reason, $actor) {
            $reversal = $this->journals->reverse($expense->journal, $reason, $actor);

            $this->persist($expense, [
                'status'          => TradingExpense::STATUS_REVERSED,
                'reversal_reason' => trim($reason),
            ]);

            return $reversal;
        });
    }

    /**
     * Move a posted cost to where it should have gone, without touching cash.
     *
     * What was wrong was the classification, not the payment, so the correction
     * is a journal between the two accounts and nothing else. This is also how
     * a cost that should have been capitalised gets into inventory: the flag
     * moves and LotCostBasis picks it up, so the ledger and the deal result
     * change together rather than one of them drifting.
     *
     * Not once the gold has been sold. By then the cost has already flowed
     * through cost of sales, and moving it would rewrite a result that has been
     * reported.
     */
    public function reclassify(
        TradingExpense $expense,
        array $to,
        string $reason,
        ?Admin $actor = null,
    ): Journal {
        AccountingPermission::assert($actor, AccountingPermission::EXPENSE_POST);

        if ($expense->journal_id === null || $expense->status === TradingExpense::STATUS_REVERSED) {
            throw new PostingRefused(
                'Expense ' . $expense->label() . ' is not posted, so there is nothing to reclassify. Edit it instead.'
            );
        }

        if (trim($reason) === '') {
            throw new PostingRefused('A reclassification needs a reason.');
        }

        $category = strtolower(trim((string) ($to['category'] ?? $expense->category)));
        $capitalised = (bool) ($to['capitalised'] ?? $expense->capitalised);

        if (! in_array($category, TradingExpense::CATEGORIES, true)) {
            throw new PostingRefused('Category must be one of: ' . implode(', ', TradingExpense::CATEGORIES) . '.');
        }

        if ($category === $expense->category && $capitalised === (bool) $expense->capitalised) {
            throw new PostingRefused('That is where the cost already is.');
        }

        $lot = $expense->lot;

        if ($capitalised !== (bool) $expense->capitalised) {
            if (! $lot) {
                throw new PostingRefused('Only a cost attached to a deal can be capitalised or de-capitalised.');
            }

            $sold = $lot->sales()->where('status', GoldSale::STATUS_SETTLED)->exists();

            if ($sold) {
                throw new PostingRefused(
                    'Gold from ' . $lot->lot_code . ' has already been sold, so this cost has already gone '
                    . 'through cost of sales. Moving it now would rewrite a result that has been reported.'
                );
            }
        }

        $amount = round((float) $expense->amount_usd, 8);
        $fromAccount = $expense->capitalised
            ? ($expense->capitalised_into ?? InventoryValuation::REFINED)
            : GoldTradingPoster::accountFor($expense->category);

        return DB::transaction(function () use ($expense, $to, $category, $capitalised, $lot, $amount, $fromAccount, $reason, $actor) {
            // Work out the destination against the books as they stand, which for
            // a capitalisation means the inventory account the lot's cost is
            // actually sitting in.
            $staged = clone $expense;
            $staged->category = $category;
            $staged->capitalised = $capitalised;

            $toAccount = $capitalised
                ? $this->poster->capitalisationAccountFor($staged)
                : GoldTradingPoster::accountFor($category);

            if ($toAccount === $fromAccount) {
                throw new PostingRefused('That is where the cost already is.');
            }

            $journal = $this->journals->post([
                [
                    'account'     => $toAccount,
                    'debit'       => $amount,
                    'gold_lot_id' => $lot?->id,
                    'memo'        => 'reclassified: ' . $reason,
                ],
                [
                    'account'     => $fromAccount,
                    'credit'      => $amount,
                    'gold_lot_id' => $lot?->id,
                    'memo'        => 'reclassified out of ' . $fromAccount,
                ],
            ], [
                'date'        => $to['date'] ?? Carbon::now()->toDateString(),
                'memo'        => 'Reclassify ' . $expense->label() . ': ' . $reason,
                'source_type' => 'gold_expense_reclassification',
                'source_id'   => $expense->id,
            ], $actor);

            $this->persist($expense, [
                'category'         => $category,
                'capitalised'      => $capitalised,
                'capitalised_into' => $capitalised ? $toAccount : null,
                'notes'            => trim((string) $expense->notes . "\n" . 'Reclassified ' . $journal->reference
                    . ' (' . $fromAccount . ' to ' . $toAccount . '): ' . $reason),
            ]);

            return $journal;
        });
    }

    /**
     * Take one step in the claim's life.
     *
     * The model is reloaded first, deliberately. An edit the guard refused
     * leaves its changes sitting on the in-memory instance, and they would
     * otherwise ride along with the next legitimate step and be refused with it
     * — a rejected attempt poisoning the record it failed to change.
     */
    private function persist(TradingExpense $expense, array $attributes): TradingExpense
    {
        $expense->refresh();
        $expense->forceFill($attributes)->save();

        return $expense;
    }

    /**
     * The claimant may not be the only person who has seen the claim.
     *
     * @return string|null the reason a waived control was allowed, or null when
     *                     no exception was needed
     */
    private function resolveSegregation(TradingExpense $expense, ?Admin $actor, ?string $reason): ?string
    {
        // No actor is the console, where reaching a shell is already a greater
        // privilege than any of these grants.
        if ($actor === null || $expense->submitted_by === null) {
            return null;
        }

        if ((int) $expense->submitted_by !== (int) $actor->id) {
            return null;
        }

        if (! config('accounting.expenses.allow_self_approval', false)) {
            throw new AccountingException(
                'Expense ' . $expense->label() . ' was submitted by the same person approving it. '
                . 'Somebody else must approve it, or enable ACCOUNTING_ALLOW_SELF_APPROVAL for a '
                . 'single-admin environment.'
            );
        }

        if (! $actor->isSuperAdmin()) {
            throw new AccountingException(
                'Only a Super Admin may approve their own expense, and only with a reason recorded against it.'
            );
        }

        if (trim((string) $reason) === '') {
            throw new AccountingException(
                'Approving your own expense waives a control, which needs a reason, and the reason is '
                . 'recorded against the expense.'
            );
        }

        return trim($reason);
    }

    /**
     * The same supplier invoice, claimed twice.
     *
     * The database refuses it outright; catching it here means the person doing
     * it is told which claim already covers that invoice rather than being shown
     * a constraint violation.
     */
    private function assertNotAlreadyClaimed(?string $payee, ?string $externalRef, ?int $ignoreId = null): void
    {
        if ($payee === null || $externalRef === null) {
            return;
        }

        $existing = TradingExpense::where('payee_name', $payee)
            ->where('external_ref', $externalRef)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->first();

        if ($existing) {
            throw new PostingRefused(
                'Invoice ' . $externalRef . ' from ' . $payee . ' is already claimed as '
                . $existing->label() . ' for ' . Money::format((float) $existing->amount_usd)
                . ' (' . $existing->status . ').'
            );
        }
    }

    private function assertCapitalisable(string $category, ?GoldLot $lot): void
    {
        if (! $lot) {
            throw new PostingRefused(
                'A capitalised cost has to name the deal whose gold it prepares. Without a lot there is '
                . 'nothing for it to become part of.'
            );
        }

        if ($category === 'operating') {
            throw new PostingRefused(
                'An operating expense is by definition not attributable to a deal, so it cannot be '
                . 'capitalised into one.'
            );
        }
    }

    private function assertStatus(TradingExpense $expense, array $allowed, string $verb): void
    {
        $status = $expense->status ?? TradingExpense::STATUS_DRAFT;

        if (! in_array($status, $allowed, true)) {
            throw new PostingRefused(
                'Expense ' . $expense->label() . ' is ' . $status . ' and cannot be ' . $verb
                . '. Expected: ' . implode(' or ', $allowed) . '.'
            );
        }
    }

    private function resolveLot(mixed $lot): ?GoldLot
    {
        if ($lot === null || $lot === '') {
            return null;
        }

        if ($lot instanceof GoldLot) {
            return $lot;
        }

        $found = is_numeric($lot)
            ? GoldLot::find((int) $lot)
            : GoldLot::where('lot_code', $lot)->first();

        if (! $found) {
            throw new PostingRefused('No deal found matching "' . $lot . '".');
        }

        return $found;
    }

    private function trimmedOrNull(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value === '' ? null : $value;
    }
}
