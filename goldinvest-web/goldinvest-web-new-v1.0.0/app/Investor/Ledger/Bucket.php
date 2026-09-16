<?php

namespace App\Investor\Ledger;

use InvalidArgumentException;

/**
 * The three buckets an investor's money can be in. Together they are the whole
 * of what the company owes them, which is why the statement adds them up and
 * calls the result the total position.
 */
final class Bucket
{
    /** Spendable and withdrawable right now. */
    public const AVAILABLE = 'available';

    /** Trading profit credited to the investor. Also spendable. */
    public const PROFIT = 'profit';

    /** Capital sitting inside an open deal. Real, owed, but not withdrawable. */
    public const COMMITTED = 'committed';

    public static function all(): array
    {
        return [self::AVAILABLE, self::PROFIT, self::COMMITTED];
    }

    public static function assert(string $bucket): void
    {
        if (! in_array($bucket, self::all(), true)) {
            throw new InvalidArgumentException('Unknown ledger bucket: ' . $bucket);
        }
    }

    public static function label(string $bucket): string
    {
        return match ($bucket) {
            self::AVAILABLE => 'Available Balance',
            self::PROFIT    => 'Profit Balance',
            self::COMMITTED => 'Capital in Active Deals',
            default         => $bucket,
        };
    }

    /** An empty position, used as the opening state of a brand new ledger. */
    public static function zeroed(): array
    {
        return [self::AVAILABLE => 0.0, self::PROFIT => 0.0, self::COMMITTED => 0.0];
    }
}
