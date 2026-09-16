<?php

namespace App\Investor\Ledger;

use InvalidArgumentException;

/**
 * Every kind of movement the ledger knows how to record.
 *
 * The list is deliberately closed. A transaction type that does not map to one
 * of these is refused rather than guessed at, because a ledger that quietly
 * invents a classification is worse than one that admits it does not know.
 */
final class LedgerEvent
{
    public const DEPOSIT              = 'deposit';
    public const PROFIT_CREDITED      = 'profit_credited';
    public const CAPITAL_ALLOCATED    = 'capital_allocated';
    public const CAPITAL_RETURNED     = 'capital_returned';
    public const WITHDRAWAL_REQUESTED = 'withdrawal_requested';
    public const WITHDRAWAL_PAID      = 'withdrawal_paid';
    public const WITHDRAWAL_REVERSED  = 'withdrawal_reversed';
    public const TRANSFER_IN          = 'transfer_in';
    public const TRANSFER_OUT         = 'transfer_out';
    public const BONUS                = 'bonus';
    public const ADMIN_ADJUSTMENT     = 'admin_adjustment';
    public const BUCKET_CORRECTION    = 'bucket_correction';

    /**
     * Movements that shuffle money between an investor's own buckets. Their legs
     * must sum to zero, which the recorder enforces.
     */
    private const INTERNAL = [
        self::CAPITAL_ALLOCATED,
        self::CAPITAL_RETURNED,
        self::BUCKET_CORRECTION,
    ];

    private const REFERENCE_PREFIX = [
        self::DEPOSIT              => 'DEP',
        self::PROFIT_CREDITED      => 'GP',
        self::CAPITAL_ALLOCATED    => 'CAP',
        self::CAPITAL_RETURNED     => 'CRT',
        self::WITHDRAWAL_REQUESTED => 'WD',
        self::WITHDRAWAL_PAID      => 'WDP',
        self::WITHDRAWAL_REVERSED  => 'WDR',
        self::TRANSFER_IN          => 'TRI',
        self::TRANSFER_OUT         => 'TRO',
        self::BONUS                => 'BON',
        self::ADMIN_ADJUSTMENT     => 'ADJ',
        self::BUCKET_CORRECTION    => 'COR',
    ];

    private const LABEL = [
        self::DEPOSIT              => 'Deposit',
        self::PROFIT_CREDITED      => 'Gold Trading Profit',
        self::CAPITAL_ALLOCATED    => 'Capital Allocated to Gold Deal',
        self::CAPITAL_RETURNED     => 'Capital Returned from Gold Deal',
        self::WITHDRAWAL_REQUESTED => 'Withdrawal Requested',
        self::WITHDRAWAL_PAID      => 'Withdrawal Paid',
        self::WITHDRAWAL_REVERSED  => 'Withdrawal Reversed',
        self::TRANSFER_IN          => 'Transfer Received',
        self::TRANSFER_OUT         => 'Transfer Sent',
        self::BONUS                => 'Bonus',
        self::ADMIN_ADJUSTMENT     => 'Administrative Adjustment',
        self::BUCKET_CORRECTION    => 'Bucket Correction',
    ];

    public static function all(): array
    {
        return array_keys(self::LABEL);
    }

    public static function assert(string $event): void
    {
        if (! isset(self::LABEL[$event])) {
            throw new InvalidArgumentException('Unknown ledger event: ' . $event);
        }
    }

    public static function isInternal(string $event): bool
    {
        return in_array($event, self::INTERNAL, true);
    }

    public static function referencePrefix(string $event): string
    {
        self::assert($event);

        return self::REFERENCE_PREFIX[$event];
    }

    public static function label(string $event): string
    {
        return self::LABEL[$event] ?? $event;
    }
}
