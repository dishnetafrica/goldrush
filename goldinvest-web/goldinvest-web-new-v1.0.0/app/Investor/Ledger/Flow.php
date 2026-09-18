<?php

namespace App\Investor\Ledger;

/**
 * Whether a movement brings money into the investor's relationship with the
 * company, takes it out, or merely shifts it between their own buckets.
 *
 * The distinction is what makes the statement reconcile:
 *
 *   opening position + external in - external out = closing position
 *
 * Internal movements are deliberately excluded from that identity, because they
 * must never change the total. A marker records that something happened (a
 * withdrawal was approved) without any money moving.
 */
final class Flow
{
    public const EXTERNAL_IN  = 'external_in';
    public const EXTERNAL_OUT = 'external_out';
    public const INTERNAL     = 'internal';
    public const MARKER       = 'marker';

    public static function all(): array
    {
        return [self::EXTERNAL_IN, self::EXTERNAL_OUT, self::INTERNAL, self::MARKER];
    }
}
