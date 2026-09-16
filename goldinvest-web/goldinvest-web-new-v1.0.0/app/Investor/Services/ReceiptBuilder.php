<?php

namespace App\Investor\Services;

use App\Investor\Exceptions\LedgerException;
use App\Investor\Ledger\Bucket;
use App\Investor\Ledger\Flow;
use App\Investor\Ledger\LedgerEvent;
use App\Investor\Models\LedgerEntry;
use App\Models\Admin\Currency;
use App\Models\User;

/**
 * Assembles a receipt for one movement in the ledger.
 *
 * A receipt states what happened, what it did to each of the investor's
 * buckets, and what the balances were before and after. Like the statement it
 * is built from ledger entries, so a receipt and the statement covering it can
 * never contradict each other.
 */
class ReceiptBuilder
{
    private const TITLES = [
        LedgerEvent::DEPOSIT              => 'Deposit Receipt',
        LedgerEvent::PROFIT_CREDITED      => 'Profit Distribution Receipt',
        LedgerEvent::CAPITAL_ALLOCATED    => 'Investment Capital Allocation Receipt',
        LedgerEvent::CAPITAL_RETURNED     => 'Capital Return Receipt',
        LedgerEvent::WITHDRAWAL_REQUESTED => 'Withdrawal Request Receipt',
        LedgerEvent::WITHDRAWAL_PAID      => 'Withdrawal Payment Receipt',
        LedgerEvent::WITHDRAWAL_REVERSED  => 'Withdrawal Reversal Receipt',
        LedgerEvent::BUCKET_CORRECTION    => 'Account Adjustment Receipt',
        LedgerEvent::ADMIN_ADJUSTMENT     => 'Account Adjustment Receipt',
        LedgerEvent::TRANSFER_IN          => 'Transfer Receipt',
        LedgerEvent::TRANSFER_OUT         => 'Transfer Receipt',
        LedgerEvent::BONUS                => 'Credit Receipt',
    ];

    private const EXPLANATIONS = [
        LedgerEvent::CAPITAL_ALLOCATED => 'This amount has been committed to the trading deal shown above and is'
            . ' temporarily unavailable for withdrawal until the capital is released at the end of the deal'
            . ' lifecycle. This is a record of a USD capital commitment. It is not a gold ownership certificate'
            . ' and confers no title to any physical gold.',
        LedgerEvent::CAPITAL_RETURNED => 'The capital committed to this deal has been released and returned to'
            . ' the balances it was originally taken from. Any profit earned is credited separately.',
        LedgerEvent::PROFIT_CREDITED => 'This is your agreed share of the net trading result of the deal shown'
            . ' above, credited to your profit balance as USD.',
        LedgerEvent::WITHDRAWAL_REQUESTED => 'This confirms your withdrawal request has been received and the'
            . ' amount reserved from your account. It is not confirmation of payment; a separate payment receipt'
            . ' is issued once the payment has been made.',
        LedgerEvent::WITHDRAWAL_PAID => 'This confirms the payment of the withdrawal previously requested.',
        LedgerEvent::WITHDRAWAL_REVERSED => 'The withdrawal request was not completed and the amount has been'
            . ' returned to the balance it was taken from.',
        LedgerEvent::BUCKET_CORRECTION => 'This adjustment re-classifies money already held in your account'
            . ' between balances. Your total account position is unchanged by it.',
    ];

    public function build(User $user, string $reference): array
    {
        $legs = LedgerEntry::forUser($user->id)
            ->where('reference', $reference)
            ->chronological()
            ->get();

        if ($legs->isEmpty()) {
            throw new LedgerException('No movement found with reference ' . $reference . ' for ' . $user->username . '.');
        }

        $first = $legs->first();
        $last = $legs->last();

        // The position immediately before this movement is the position recorded
        // against the entry that precedes its first leg.
        $previous = LedgerEntry::forUser($user->id)->where('seq', '<', $first->seq)->orderByDesc('seq')->first();

        $before = $previous
            ? [
                Bucket::AVAILABLE => (float) $previous->balance_available,
                Bucket::PROFIT    => (float) $previous->balance_profit,
                Bucket::COMMITTED => (float) $previous->balance_committed,
            ]
            : Bucket::zeroed();

        $after = [
            Bucket::AVAILABLE => (float) $last->balance_available,
            Bucket::PROFIT    => (float) $last->balance_profit,
            Bucket::COMMITTED => (float) $last->balance_committed,
        ];

        $event = $first->event_type;
        $fromProfit = $legs->firstWhere(fn ($l) => $l->bucket === Bucket::PROFIT && $l->amount_usd < 0);

        // Committing money that includes earned profit is a reinvestment, and is
        // worth naming as one: it is the investor putting their return back to work.
        $isReinvestment = $event === LedgerEvent::CAPITAL_ALLOCATED && $fromProfit !== null;

        return [
            'title'       => $isReinvestment ? 'Reinvestment Receipt' : (self::TITLES[$event] ?? 'Account Receipt'),
            'event'       => $event,
            'explanation' => self::EXPLANATIONS[$event] ?? null,
            'reference'   => $reference,
            'trx_id'      => $first->trx_id,
            'date'        => $first->occurred_at,
            'flow'        => $first->flow,
            'is_marker'   => $first->flow === Flow::MARKER,
            'is_internal' => $first->flow === Flow::INTERNAL,
            'description' => $first->description,
            'amount'      => $this->headlineAmount($legs),
            'currency'    => Currency::where('default', true)->value('code') ?? 'USD',
            'legs'        => $legs->map(fn ($l) => [
                'bucket' => Bucket::label($l->bucket),
                'amount' => (float) $l->amount_usd,
            ])->all(),
            'before'   => $before + ['total' => array_sum($before)],
            'after'    => $after + ['total' => array_sum($after)],
            'lot'      => $first->lot,
            'meta'     => $first->meta,
            'investor' => [
                'name'     => trim(($user->firstname ?? '') . ' ' . ($user->lastname ?? '')) ?: $user->username,
                'username' => $user->username,
                'email'    => $user->email,
                'account'  => 'INV-' . str_pad((string) $user->id, 6, '0', STR_PAD_LEFT),
            ],
            'status' => $this->status($event),
        ];
    }

    /**
     * The single figure the receipt leads with.
     *
     * For an internal movement the legs cancel out, so the meaningful number is
     * the amount that moved, not the net of nothing.
     */
    private function headlineAmount($legs): float
    {
        $positive = (float) $legs->where('amount_usd', '>', 0)->sum('amount_usd');

        return $positive > 0
            ? $positive
            : abs((float) $legs->sum('amount_usd'));
    }

    private function status(string $event): string
    {
        return match ($event) {
            LedgerEvent::WITHDRAWAL_REQUESTED => 'PENDING APPROVAL',
            LedgerEvent::WITHDRAWAL_PAID      => 'PAID',
            LedgerEvent::WITHDRAWAL_REVERSED  => 'REVERSED',
            LedgerEvent::CAPITAL_ALLOCATED    => 'CAPITAL COMMITTED',
            LedgerEvent::CAPITAL_RETURNED     => 'CAPITAL RETURNED',
            LedgerEvent::PROFIT_CREDITED      => 'CREDITED',
            default                           => 'RECORDED',
        };
    }
}
