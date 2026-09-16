<?php

namespace App\Investor\Services;

use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * Hands out gapless, per-day reference numbers: CAP-20260916-000001,
 * RCP-20260918-000004, and so on.
 *
 * The counter row is locked for the rest of the caller's transaction, so two
 * things numbered at the same instant cannot be given the same number. That
 * rules out the max()+1 race that would otherwise surface as a duplicate
 * receipt number on a busy day.
 */
class SequenceAllocator
{
    public function next(string $prefix, CarbonInterface $on): string
    {
        $day = $on->toDateString();

        $row = DB::table('ledger_reference_sequences')
            ->where('prefix', $prefix)
            ->where('day', $day)
            ->lockForUpdate()
            ->first();

        if (! $row) {
            DB::table('ledger_reference_sequences')->insert([
                'prefix' => $prefix, 'day' => $day, 'next_number' => 1,
                'created_at' => now(), 'updated_at' => now(),
            ]);

            $number = 1;
        } else {
            $number = (int) $row->next_number;
        }

        DB::table('ledger_reference_sequences')
            ->where('prefix', $prefix)
            ->where('day', $day)
            ->update(['next_number' => $number + 1, 'updated_at' => now()]);

        return sprintf('%s-%s-%06d', $prefix, str_replace('-', '', $day), $number);
    }
}
