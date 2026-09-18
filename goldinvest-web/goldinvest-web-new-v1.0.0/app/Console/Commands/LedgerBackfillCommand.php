<?php

namespace App\Console\Commands;

use App\Investor\Services\LedgerReconciler;
use App\Investor\Services\LedgerSynchroniser;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Builds an investor's ledger from the transactions the platform has already
 * recorded.
 *
 * It records what actually happened, including anything that happened wrongly.
 * Historical mistakes are not tidied up here; they are corrected afterwards by
 * a posted correction, so that the mistake and its correction both stay on the
 * record. See ledger:fix-buckets.
 *
 * If the result does not reconcile, the whole backfill for that investor is
 * rolled back and the gap is reported. It never inserts a balancing entry to
 * make the numbers look right.
 */
class LedgerBackfillCommand extends Command
{
    protected $signature = 'ledger:backfill
                            {user? : username or email}
                            {--all : every investor who has transactions}
                            {--rebuild : discard derived entries and replay from the start}';

    protected $description = 'Build investor ledgers from existing transactions';

    public function handle(LedgerSynchroniser $synchroniser, LedgerReconciler $reconciler): int
    {
        $users = $this->resolveUsers();

        if ($users === []) {
            $this->error('Name an investor, or pass --all.');

            return self::FAILURE;
        }

        $failures = 0;

        foreach ($users as $user) {
            $this->line('');
            $this->line('Backfilling ' . $user->username . ' …');

            DB::beginTransaction();

            try {
                $report = $synchroniser->syncUser($user, $this->option('rebuild'));
                $check = $reconciler->forUser($user);

                if (! $check['passed']) {
                    DB::rollBack();
                    $failures++;

                    $this->error('Refused: ' . $user->username . "'s ledger does not reconcile. Nothing was written.");
                    foreach ($check['discrepancies'] as $d) {
                        $this->line('  - ' . $d);
                    }

                    continue;
                }

                DB::commit();

                $this->info('  ' . $report['scanned'] . ' transaction(s) read, '
                    . $report['movements'] . ' movement(s), ' . $report['entries'] . ' ledger entries.');

                foreach ($report['events'] as $event => $count) {
                    $this->line('    ' . str_pad($event, 24) . $count);
                }

                $this->line('  Position: available ' . number_format($check['position']['available'], 2)
                    . ', profit ' . number_format($check['position']['profit'], 2)
                    . ', committed ' . number_format($check['position']['committed'], 2)
                    . '  =  ' . number_format($check['total'], 2));
            } catch (\Throwable $e) {
                DB::rollBack();
                $failures++;
                $this->error('  Failed: ' . $e->getMessage());
            }
        }

        $this->newLine();

        if ($failures > 0) {
            $this->error($failures . ' investor(s) could not be backfilled.');

            return self::FAILURE;
        }

        $this->info('All ledgers built and reconciled.');

        return self::SUCCESS;
    }

    /** @return array<int, User> */
    private function resolveUsers(): array
    {
        if ($this->option('all')) {
            return User::whereIn('id', DB::table('transactions')->distinct()->pluck('user_id')->filter())
                ->orderBy('id')
                ->get()
                ->all();
        }

        $identifier = $this->argument('user');

        if (! $identifier) {
            return [];
        }

        $user = User::where('username', $identifier)->orWhere('email', $identifier)->first();

        if (! $user) {
            $this->error('No user matches ' . $identifier);

            return [];
        }

        return [$user];
    }
}
