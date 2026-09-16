<?php

namespace App\Investor\Services;

use App\Constants\PaymentGatewayConst;
use App\GoldTrading\Models\CapitalAllocation;
use App\GoldTrading\Models\GoldLot;
use App\GoldTrading\Models\ProfitDistribution;
use App\Investor\Exceptions\LedgerException;
use App\Investor\Exceptions\UnmappableTransaction;
use App\Investor\Ledger\Bucket;
use App\Investor\Ledger\Flow;
use App\Investor\Ledger\LedgerEvent;

/**
 * Turns a row of the platform's transactions table into ledger movements.
 *
 * This is the single place where "what the app recorded" becomes "what the
 * investor is told", so both the historical backfill and the ongoing sync use
 * it. Anything it cannot classify it refuses, loudly.
 *
 * Withdrawals are the awkward case. The platform debits the wallet when the
 * request is made and then mutates that same row when an admin approves or
 * rejects it, so one transaction produces up to two movements at different
 * times. The stage recorded against each movement is what lets the sync pick up
 * where it left off.
 */
class TransactionLedgerMapper
{
    /**
     * Transaction type written when a correction reclassifies money between
     * buckets. It is posted by ledger:fix-buckets, which knows why the correction
     * is being made; it is never derived from the transaction row alone.
     */
    public const TRX_BUCKET_CORRECTION = 'GOLD-BUCKET-CORRECTION';

    public const STAGE_POSTED    = 'posted';
    public const STAGE_REQUESTED = 'requested';
    public const STAGE_PAID      = 'paid';
    public const STAGE_REVERSED  = 'reversed';

    /** Types that simply add to the investor's available balance. */
    private const SIMPLE_CREDITS = [
        PaymentGatewayConst::TYPEBONUS,
        PaymentGatewayConst::TYPEREFERBONUS,
        PaymentGatewayConst::TYPECOMMISSION,
        PaymentGatewayConst::TYPECAPITALRETURN,
    ];

    /**
     * @param  object  $trx  a row from the transactions table
     * @param  array<string, float>  $running  the investor's position before this transaction
     * @param  string|null  $postedStage  the stage already in the ledger, if any
     * @return array<int, array{event: string, legs: array, context: array, stage: string}>
     */
    public function map(object $trx, int $userId, array $running, ?string $postedStage): array
    {
        $details = $this->details($trx);
        $status = (int) ($trx->status ?? 0);

        return match (true) {
            $trx->type === PaymentGatewayConst::TYPEADDMONEY
                => $this->deposit($trx, $status, $postedStage),

            $trx->type === ProfitDistribution::TRX_TYPE
                => $this->profit($trx, $details, $postedStage),

            $trx->type === CapitalAllocation::TRX_LOCK
                => $this->capitalAllocated($trx, $details, $postedStage),

            $trx->type === CapitalAllocation::TRX_RELEASE
                => $this->capitalReturned($trx, $details, $postedStage),

            in_array($trx->type, [PaymentGatewayConst::TYPEWITHDRAW, PaymentGatewayConst::TYPEMONEYOUT], true)
                => $this->withdrawal($trx, $details, $status, $postedStage),

            $trx->type === PaymentGatewayConst::TYPETRANSFERMONEY
                => $this->transfer($trx, $userId, $postedStage),

            $trx->type === PaymentGatewayConst::TYPEADDSUBTRACTBALANCE
                => $this->adminAdjustment($trx, $running, $postedStage),

            in_array($trx->type, self::SIMPLE_CREDITS, true)
                => $this->simpleCredit($trx, $postedStage),

            $trx->type === self::TRX_BUCKET_CORRECTION
                => $this->bucketCorrection($trx, $postedStage),

            default => throw new UnmappableTransaction((string) $trx->type, $trx->trx_id ?? null),
        };
    }

    private function deposit(object $trx, int $status, ?string $postedStage): array
    {
        // Money only exists once the deposit is approved. A pending deposit is
        // left unposted and picked up by a later sync.
        if ($postedStage !== null || $status !== PaymentGatewayConst::STATUSSUCCESS) {
            return [];
        }

        return [$this->movement(
            LedgerEvent::DEPOSIT,
            [['bucket' => Bucket::AVAILABLE, 'amount' => (float) $trx->receive_amount]],
            $trx,
            'Deposit received',
        )];
    }

    private function profit(object $trx, ?object $details, ?string $postedStage): array
    {
        if ($postedStage !== null) {
            return [];
        }

        $lot = isset($details->lot_code) ? GoldLot::where('lot_code', $details->lot_code)->first() : null;

        return [$this->movement(
            LedgerEvent::PROFIT_CREDITED,
            [['bucket' => Bucket::PROFIT, 'amount' => (float) $trx->receive_amount]],
            $trx,
            'Trading profit' . ($details->lot_code ?? null ? ' from deal ' . $details->lot_code : ''),
            ['gold_lot_id' => $lot?->id],
        )];
    }

    private function capitalAllocated(object $trx, ?object $details, ?string $postedStage): array
    {
        if ($postedStage !== null) {
            return [];
        }

        $total = (float) $trx->request_amount;
        $fromAvailable = (float) ($details->from_balance_usd ?? $total);
        $fromProfit = (float) ($details->from_profit_usd ?? 0);

        $this->assertSplitAddsUp($fromAvailable + $fromProfit, $total, $trx, 'allocation');

        $lot = isset($details->lot_code) ? GoldLot::where('lot_code', $details->lot_code)->first() : null;

        return [$this->movement(
            LedgerEvent::CAPITAL_ALLOCATED,
            [
                ['bucket' => Bucket::AVAILABLE, 'amount' => -$fromAvailable],
                ['bucket' => Bucket::PROFIT,    'amount' => -$fromProfit],
                ['bucket' => Bucket::COMMITTED, 'amount' => $total],
            ],
            $trx,
            'Capital committed to deal ' . ($details->lot_code ?? '?'),
            ['gold_lot_id' => $lot?->id],
        )];
    }

    private function capitalReturned(object $trx, ?object $details, ?string $postedStage): array
    {
        if ($postedStage !== null) {
            return [];
        }

        $total = (float) $trx->request_amount;

        // Returns recorded before the bucket fix credited everything to the
        // available balance. Those rows have no split, and the ledger records what
        // actually happened; a bucket_correction puts it right afterwards.
        $toAvailable = (float) ($details->to_balance_usd ?? $total);
        $toProfit = (float) ($details->to_profit_usd ?? 0);

        $this->assertSplitAddsUp($toAvailable + $toProfit, $total, $trx, 'capital return');

        $lot = isset($details->lot_code) ? GoldLot::where('lot_code', $details->lot_code)->first() : null;

        return [$this->movement(
            LedgerEvent::CAPITAL_RETURNED,
            [
                ['bucket' => Bucket::COMMITTED, 'amount' => -$total],
                ['bucket' => Bucket::AVAILABLE, 'amount' => $toAvailable],
                ['bucket' => Bucket::PROFIT,    'amount' => $toProfit],
            ],
            $trx,
            'Capital returned from deal ' . ($details->lot_code ?? '?'),
            ['gold_lot_id' => $lot?->id],
        )];
    }

    private function withdrawal(object $trx, ?object $details, int $status, ?string $postedStage): array
    {
        $bucket = ($details->wallet_type ?? 'c_balance') === 'p_balance' ? Bucket::PROFIT : Bucket::AVAILABLE;
        $amount = (float) $trx->total_payable > 0
            ? (float) $trx->total_payable
            : (float) $trx->request_amount;
        $movements = [];

        // The money leaves the wallet when the request is made, not when it is
        // approved, so the debit is posted first whatever the row says now.
        if ($postedStage === null) {
            $movements[] = $this->movement(
                LedgerEvent::WITHDRAWAL_REQUESTED,
                [['bucket' => $bucket, 'amount' => -$amount]],
                $trx,
                'Withdrawal requested from ' . Bucket::label($bucket),
                [],
                self::STAGE_REQUESTED,
            );

            $postedStage = self::STAGE_REQUESTED;
        }

        if ($status === PaymentGatewayConst::STATUSSUCCESS && $postedStage === self::STAGE_REQUESTED) {
            $movements[] = $this->movement(
                LedgerEvent::WITHDRAWAL_PAID,
                [],
                $trx,
                'Withdrawal paid',
                ['flow' => Flow::MARKER],
                self::STAGE_PAID,
            );
        }

        if ($status === PaymentGatewayConst::STATUSREJECTED && $postedStage === self::STAGE_REQUESTED) {
            $movements[] = $this->movement(
                LedgerEvent::WITHDRAWAL_REVERSED,
                [['bucket' => $bucket, 'amount' => $amount]],
                $trx,
                'Withdrawal rejected, amount returned to ' . Bucket::label($bucket),
                [],
                self::STAGE_REVERSED,
            );
        }

        return $movements;
    }

    private function transfer(object $trx, int $userId, ?string $postedStage): array
    {
        if ($postedStage !== null) {
            return [];
        }

        $isReceiver = (int) $trx->receiver_id === $userId && (int) $trx->user_id !== $userId;

        return [$isReceiver
            ? $this->movement(
                LedgerEvent::TRANSFER_IN,
                [['bucket' => Bucket::AVAILABLE, 'amount' => (float) $trx->receive_amount]],
                $trx,
                'Transfer received',
            )
            : $this->movement(
                LedgerEvent::TRANSFER_OUT,
                [['bucket' => Bucket::AVAILABLE, 'amount' => -((float) $trx->total_payable > 0
                    ? (float) $trx->total_payable
                    : (float) $trx->request_amount)]],
                $trx,
                'Transfer sent',
            )];
    }

    private function adminAdjustment(object $trx, array $running, ?string $postedStage): array
    {
        if ($postedStage !== null) {
            return [];
        }

        // The platform records the amount without a sign, but available_balance is
        // the wallet balance immediately afterwards, so the direction is recoverable
        // by comparing it against where the ledger had got to.
        $delta = round((float) $trx->available_balance - $running[Bucket::AVAILABLE], 8);
        $stated = (float) $trx->request_amount;

        if (abs(abs($delta) - $stated) > 0.01) {
            throw new LedgerException(
                'Cannot tell whether admin adjustment ' . $trx->trx_id . ' added or subtracted. '
                . 'It states ' . number_format($stated, 2) . ' but the balance moved by '
                . number_format($delta, 2) . '. Resolve this before generating statements.'
            );
        }

        return [$this->movement(
            LedgerEvent::ADMIN_ADJUSTMENT,
            [['bucket' => Bucket::AVAILABLE, 'amount' => $delta]],
            $trx,
            trim('Administrative adjustment ' . ($trx->remark ?? '')),
        )];
    }

    private function simpleCredit(object $trx, ?string $postedStage): array
    {
        if ($postedStage !== null) {
            return [];
        }

        return [$this->movement(
            LedgerEvent::BONUS,
            [['bucket' => Bucket::AVAILABLE, 'amount' => (float) $trx->receive_amount]],
            $trx,
            (string) $trx->type,
        )];
    }

    /**
     * A correction is posted with its reason by the command that decided to make
     * it. Seeing one here that the ledger has no entry for means a correction was
     * written straight into the transactions table, which is exactly the kind of
     * silent reclassification this ledger exists to prevent.
     */
    private function bucketCorrection(object $trx, ?string $postedStage): array
    {
        if ($postedStage !== null) {
            return [];
        }

        throw new LedgerException(
            'Bucket correction ' . $trx->trx_id . ' exists in the transactions table but not in the ledger. '
            . 'Corrections must be posted through ledger:fix-buckets so their reason is recorded; '
            . 'the ledger will not infer one.'
        );
    }

    private function movement(
        string $event,
        array $legs,
        object $trx,
        string $description,
        array $extraContext = [],
        string $stage = self::STAGE_POSTED,
    ): array {
        return [
            'event' => $event,
            'legs'  => $legs,
            'stage' => $stage,
            'context' => array_merge([
                'occurred_at'    => $trx->created_at,
                'trx_id'         => $trx->trx_id,
                'transaction_id' => $trx->id,
                'description'    => $description,
            ], $extraContext),
        ];
    }

    private function assertSplitAddsUp(float $split, float $total, object $trx, string $what): void
    {
        if (abs($split - $total) > 0.01) {
            throw new LedgerException(
                'The ' . $what . ' in ' . $trx->trx_id . ' does not add up: its parts total '
                . number_format($split, 2) . ' but the movement was ' . number_format($total, 2) . '.'
            );
        }
    }

    private function details(object $trx): ?object
    {
        $details = $trx->details ?? null;

        if (is_string($details)) {
            $details = json_decode($details);
        }

        return is_object($details) ? $details : null;
    }
}
