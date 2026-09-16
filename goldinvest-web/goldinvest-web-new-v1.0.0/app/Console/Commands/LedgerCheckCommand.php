<?php

namespace App\Console\Commands;

use App\Investor\Ledger\Bucket;
use App\Investor\Services\LedgerReconciler;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Asks the ledger to prove it is still true. Nothing investor-facing should be
 * produced for an investor this command reports as failing.
 */
class LedgerCheckCommand extends Command
{
    protected $signature = 'ledger:check
                            {user? : username or email; omit for every investor}
                            {--strict : exit non-zero on the first failure}';

    protected $description = 'Reconcile investor ledgers against transactions, wallets and gold allocations';

    public function handle(LedgerReconciler $reconciler): int
    {
        $users = $this->resolveUsers();
        $failed = 0;

        foreach ($users as $user) {
            $report = $reconciler->forUser($user);

            $this->newLine();
            $this->line('Investor: ' . $report['user'] . '   (' . $report['entries'] . ' ledger entries)');

            $this->table(['Bucket', 'Amount'], [
                [Bucket::label(Bucket::AVAILABLE), number_format($report['position']['available'], 2)],
                [Bucket::label(Bucket::PROFIT), number_format($report['position']['profit'], 2)],
                [Bucket::label(Bucket::COMMITTED), number_format($report['position']['committed'], 2)],
                ['TOTAL POSITION', number_format($report['total'], 2)],
            ]);

            foreach ($report['checks'] as $name => $check) {
                $this->line(sprintf(
                    '  [%s] %-13s %s',
                    $check['passed'] ? 'PASS' : 'FAIL',
                    $name,
                    $check['passed'] ? $check['message'] : ''
                ));

                foreach ($check['discrepancies'] as $d) {
                    $this->line('         - ' . $d);
                }
            }

            if ($report['passed']) {
                $this->info('  OVERALL: PASS');
            } else {
                $this->error('  OVERALL: FAIL');
                $failed++;

                if ($this->option('strict')) {
                    return self::FAILURE;
                }
            }
        }

        $this->newLine();

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    /** @return iterable<User> */
    private function resolveUsers(): iterable
    {
        $identifier = $this->argument('user');

        if ($identifier) {
            $user = User::where('username', $identifier)->orWhere('email', $identifier)->first();

            if (! $user) {
                $this->error('No user matches ' . $identifier);

                return [];
            }

            return [$user];
        }

        return User::whereIn('id', DB::table('transactions')->distinct()->pluck('user_id')->filter())
            ->orderBy('id')
            ->get();
    }
}
