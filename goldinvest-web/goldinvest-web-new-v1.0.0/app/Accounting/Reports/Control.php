<?php

namespace App\Accounting\Reports;

/**
 * One control: two figures that must agree, and whether they do.
 *
 * Three outcomes. RECONCILED: they agree. CONTROL EXCEPTION: they do not,
 * and nothing in the books explains why. PRE-BACKFILL: they do not, and the
 * reason is the known, open D2/D3 gap - historical investor balances with no
 * company-side posting yet. The third is shown as plainly as the second; the
 * label says why the difference exists, it never makes it disappear.
 */
final class Control
{
    public const EPSILON = 0.00000001;

    public const RECONCILED  = 'RECONCILED';
    public const EXCEPTION   = 'CONTROL EXCEPTION';
    public const PRE_BACKFILL = 'PRE-BACKFILL';

    public const PRE_BACKFILL_CAPTION = 'Pre-backfill reconciliation difference - historical attribution/funding '
        . 'not yet posted to company GL. D2/D3 open.';

    public static function make(
        string $key, string $name, string $leftLabel, float $left, string $rightLabel, float $right,
        bool $preBackfill = false, ?string $caption = null, array $rows = []
    ): array {
        $difference = round($left - $right, 8);
        $passed = abs($difference) <= self::EPSILON;

        return [
            'key'         => $key,
            'name'        => $name,
            'left_label'  => $leftLabel,
            'left'        => round($left, 8),
            'right_label' => $rightLabel,
            'right'       => round($right, 8),
            'difference'  => $difference,
            'passed'      => $passed,
            'status'      => $passed ? self::RECONCILED : ($preBackfill ? self::PRE_BACKFILL : self::EXCEPTION),
            'caption'     => $passed ? null : ($caption ?? ($preBackfill ? self::PRE_BACKFILL_CAPTION : null)),
            'rows'        => $rows,
        ];
    }

    /** A control that is a yes/no rather than two figures. */
    public static function flag(string $key, string $name, bool $passed, string $detail, ?string $caption = null): array
    {
        return [
            'key' => $key, 'name' => $name, 'left_label' => $detail, 'left' => 0.0, 'right_label' => '', 'right' => 0.0,
            'difference' => 0.0, 'passed' => $passed, 'status' => $passed ? self::RECONCILED : self::EXCEPTION,
            'caption' => $passed ? null : $caption, 'rows' => [],
        ];
    }

    public static function allPassed(array $controls): bool
    {
        foreach ($controls as $c) {
            if (! $c['passed']) {
                return false;
            }
        }

        return true;
    }

    /** True when every failure is the known pre-backfill gap and nothing else. */
    public static function onlyPreBackfill(array $controls): bool
    {
        foreach ($controls as $c) {
            if (! $c['passed'] && $c['status'] !== self::PRE_BACKFILL) {
                return false;
            }
        }

        return true;
    }
}
