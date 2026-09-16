<?php

namespace App\Console\Commands;

use App\Investor\Ledger\Bucket;
use App\Investor\Ledger\Flow;
use App\Investor\Models\LedgerEntry;
use App\Investor\Services\LedgerReconciler;
use App\Models\Admin\Currency;
use App\Models\User;
use Illuminate\Console\Command;

/**
 * An investor's cash book, read straight off the ledger.
 *
 * It used to infer direction from the transactions table, which meant guessing
 * at admin adjustments and having no way to show committed capital at all. Now
 * it reads what the ledger recorded, so the console, and later the statement PDF
 * and the dashboard, can only ever say the same thing.
 *
 * If the ledger does not reconcile, this says so rather than printing numbers
 * that look fine.
 */
class GoldStatementCommand extends Command
{
    protected $signature = 'gold:statement
                            {investor : username or email}
                            {--limit=100 : most recent movements to print}';

    protected $description = 'Print an investor\'s money-in / money-out cash book';

    public function handle(LedgerReconciler $reconciler): int
    {
        $user = User::where('username', $this->argument('investor'))
            ->orWhere('email', $this->argument('investor'))
            ->first();

        if (! $user) {
            $this->error('No user matches ' . $this->argument('investor'));

            return self::FAILURE;
        }

        $code = Currency::where('default', true)->value('code') ?? 'USD';

        $entries = LedgerEntry::forUser($user->id)->chronological()->get();

        if ($entries->isEmpty()) {
            $this->warn('No ledger entries for ' . $user->username . '.');
            $this->line('Build the ledger first:  php artisan ledger:backfill ' . $user->username);

            return self::SUCCESS;
        }

        $report = $reconciler->forUser($user);

        // One printed row per movement, not per leg: an investor thinks of putting
        // money into a deal as one thing, not as three bucket adjustments.
        $rows = [];

        foreach ($entries->groupBy('group_uuid') as $legs) {
            $first = $legs->first();
            $last = $legs->last();

            $in = $legs->where('amount_usd', '>', 0)->sum('amount_usd');
            $out = abs($legs->where('amount_usd', '<', 0)->sum('amount_usd'));

            $rows[] = [
                $first->occurred_at->format('d M Y'),
                $first->description,
                $first->flow === Flow::INTERNAL ? '(internal)' : ($in > 0 ? number_format($in, 2) : ''),
                $first->flow === Flow::INTERNAL ? '' : ($out > 0 ? number_format($out, 2) : ''),
                number_format($last->balance_available, 2),
                number_format($last->balance_profit, 2),
                number_format($last->balance_committed, 2),
                $first->reference,
            ];
        }

        $limit = max(1, (int) $this->option('limit'));
        $shown = array_slice($rows, -$limit);

        $this->newLine();
        $this->line('Cash book — ' . $user->username . ' (' . $user->email . ')   all figures in ' . $code);
        $this->newLine();

        if (count($rows) > count($shown)) {
            $this->line('… ' . (count($rows) - count($shown)) . ' earlier movements not shown');
        }

        $this->table(
            ['Date', 'Movement', 'Money In', 'Money Out', 'Available', 'Profit', 'Committed', 'Reference'],
            $shown
        );

        $position = $report['position'];
        $totals = $report['totals'];

        $this->line('Total money in   : ' . number_format($totals['external_in'], 2) . ' ' . $code);
        $this->line('Total money out  : ' . number_format($totals['external_out'], 2) . ' ' . $code);
        $this->line('Moved internally : ' . number_format($totals['internal_gross'], 2) . ' ' . $code
            . '   (between buckets; changes no total)');
        $this->newLine();
        $this->line(str_pad(Bucket::label(Bucket::AVAILABLE), 26) . number_format($position['available'], 2) . ' ' . $code);
        $this->line(str_pad(Bucket::label(Bucket::PROFIT), 26) . number_format($position['profit'], 2) . ' ' . $code);
        $this->line(str_pad(Bucket::label(Bucket::COMMITTED), 26) . number_format($position['committed'], 2) . ' ' . $code
            . '   (working in gold, not withdrawable)');
        $this->line(str_pad('TOTAL POSITION', 26) . number_format($report['total'], 2) . ' ' . $code);
        $this->newLine();
        $this->info('Can withdraw today: ' . number_format($position['available'] + $position['profit'], 2) . ' ' . $code);

        $openDeals = $entries->where('bucket', Bucket::COMMITTED)
            ->where('amount_usd', '>', 0)
            ->whereNotNull('gold_lot_id');

        if ($position['committed'] > 0 && $openDeals->isNotEmpty()) {
            $this->newLine();
            $this->line('Deals holding this investor\'s capital:');
            $this->table(
                ['Deal', 'Committed', 'Since'],
                $openDeals->map(fn ($e) => [
                    $e->lot->lot_code ?? '-',
                    number_format($e->amount_usd, 2),
                    $e->occurred_at->format('d M Y'),
                ])->all()
            );
        }

        if (! $report['passed']) {
            $this->newLine();
            $this->error('This ledger does not reconcile. Treat the figures above as unverified.');
            foreach ($report['discrepancies'] as $d) {
                $this->line('  - ' . $d);
            }

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
