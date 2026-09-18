<?php

namespace App\Investor\Support;

/**
 * The rounding policy for everything an investor is shown.
 *
 * One rule: calculate on the stored 8-decimal values and round once, at the
 * moment of display. Never sum figures that have already been rounded.
 *
 * It matters. Bhavin's profit balance is 1804.37632, made of 943.31 and
 * 861.06632. Rounding each part first gives 943.31 + 861.07 = 1804.38, and
 * rounding the true total gives 1804.38 as well — this time. Do it across
 * enough lines and the column stops adding up to the total printed beneath it,
 * which is exactly how a statement loses an investor's trust.
 */
final class Money
{
    public const DISPLAY_SCALE = 2;

    /** Amounts are equal when they agree at the precision the database stores. */
    public const EXACT_EPSILON = 0.00000001;

    /** For display only. Never feed the result back into a calculation. */
    public static function format(float $amount): string
    {
        return number_format(round($amount, self::DISPLAY_SCALE), self::DISPLAY_SCALE, '.', ',');
    }

    /** Display with an explicit sign, for money-in / money-out columns. */
    public static function signed(float $amount): string
    {
        return ($amount < 0 ? '-' : '+') . self::format(abs($amount));
    }

    public static function equal(float $a, float $b): bool
    {
        return abs($a - $b) <= self::EXACT_EPSILON;
    }

    /** The full stored value, for audit lines and reconciliation reports. */
    public static function exact(float $amount): string
    {
        return number_format($amount, 8, '.', '');
    }
}
