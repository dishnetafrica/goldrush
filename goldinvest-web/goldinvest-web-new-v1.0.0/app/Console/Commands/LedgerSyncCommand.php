<?php

namespace App\Console\Commands;

use App\Investor\Services\LedgerSynchroniser;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Posts any transactions that have happened since the ledger last looked.
 *
 * Run on the schedule, because the platform writes transactions with the query
 * builder and there is no model event to hook. Also called directly by the gold
 * commands so a deal's movements appear immediately rather than within a minute.
 */
class LedgerSyncCommand extends Command
{
    protected $signature = 'ledger:sync
                            {user? : username or email; omit for every investor}
                            {--quiet-success : say nothing when there was nothing to do}';

    protected $description = 'Bring investor ledgers up to date with new transactions';

    public function handle(LedgerSynchroniser $synchroniser): int
    {
        $users = $this->resolveUsers();
        $posted = 0;
        $failed = 0;

        foreach ($users as $user) {
            try {
                $report = $synchroniser->syncUser($user);

                if ($report['movements'] > 0) {
                    $posted += $report['movements'];
                    $this->line($user->username . ': ' . $report['movements'] . ' movement(s) posted.');
                }
            } catch (\Throwable $e) {
                $failed++;
                // A sync failure must be loud somewhere even when nobody is watching
                // the console, because from here on the ledger is behind the money.
                Log::error('Ledger sync failed for ' . $user->username . ': ' . $e->getMessage());
                $this->error($user->username . ': ' . $e->getMessage());
            }
        }

        if ($failed > 0) {
            return self::FAILURE;
        }

        if ($posted === 0 && ! $this->option('quiet-success')) {
            $this->info('Ledgers already up to date.');
        }

        return self::SUCCESS;
    }

    /** @return iterable<User> */
    private function resolveUsers(): iterable
    {
        $identifier = $this->argument('user');

        if ($identifier) {
            $user = User::where('username', $identifier)->orWhere('email', $identifier)->first();

            return $user ? [$user] : [];
        }

        return User::whereIn('id', DB::table('transactions')->distinct()->pluck('user_id')->filter())
            ->orderBy('id')
            ->get();
    }
}
