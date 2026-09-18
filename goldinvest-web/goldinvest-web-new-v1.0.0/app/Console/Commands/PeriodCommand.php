<?php

namespace App\Console\Commands;

use App\Accounting\Models\AccountingPeriod;
use App\Accounting\Services\PeriodCloseService;
use App\Investor\Support\Money;
use Illuminate\Console\Command;

/**
 * Closing a month, and seeing what stands in the way of closing it.
 *
 * Checking is separate from closing on purpose. The list of blockers is the
 * to-do list for the month end, and it should be possible to read it as often
 * as needed without any of those readings being a close.
 */
class PeriodCommand extends Command
{
    protected $signature = 'period
                            {action : status|check|close|reopen}
                            {code? : the period code, e.g. 2026-09}
                            {--reason= : why the period is being closed or reopened}';

    protected $description = 'Check, close or reopen an accounting period';

    public function handle(PeriodCloseService $service): int
    {
        try {
            return match (strtolower((string) $this->argument('action'))) {
                'status' => $this->status(),
                'check'  => $this->check($service, $this->period()),
                'close'  => $this->close($service, $this->period()),
                'reopen' => $this->reopen($service, $this->period()),
                default  => throw new \InvalidArgumentException('Action must be status, check, close or reopen.'),
            };
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }

    private function status(): int
    {
        $this->table(
            ['Period', 'From', 'To', 'Status', 'Closed', 'Reference'],
            AccountingPeriod::orderBy('starts_on')->get()->map(fn (AccountingPeriod $p) => [
                $p->code,
                $p->starts_on->format('d M Y'),
                $p->ends_on->format('d M Y'),
                $p->status,
                $p->closed_at ? $p->closed_at->format('d M Y H:i') . ($p->closedBy ? ' by ' . $p->closedBy->username : '') : '-',
                $p->close_reference ?? '-',
            ])->all()
        );

        return self::SUCCESS;
    }

    private function check(PeriodCloseService $service, AccountingPeriod $period): int
    {
        $blockers = $service->blockers($period);

        if ($blockers === []) {
            $this->info('Period ' . $period->code . ' can be closed. Nothing in it is still moving.');

            return self::SUCCESS;
        }

        $this->warn('Period ' . $period->code . ' cannot be closed yet:');

        foreach ($blockers as $blocker) {
            $this->line('  - ' . $blocker);
        }

        return self::FAILURE;
    }

    private function close(PeriodCloseService $service, AccountingPeriod $period): int
    {
        $closed = $service->close($period, null, $this->option('reason'));
        $snapshot = $closed->snapshot;

        $this->info('Period ' . $closed->code . ' closed as ' . $closed->close_reference
            . ' at ' . $closed->closed_at->format('d M Y H:i') . '.');

        $this->line('  Trading revenue         ' . $this->right($snapshot['trading_revenue_usd']));
        $this->line('  Cost of gold sold       ' . $this->right(-$snapshot['trading_cogs_usd']));
        $this->line('  Deal expenses           ' . $this->right(-$snapshot['trading_expenses_usd']));
        $this->line('  Realized trading result ' . $this->right($snapshot['trading_result_usd']));
        $this->line('  Costs belonging to no deal ' . $this->right(-$snapshot['unattributed_expenses_usd']));
        $this->line('  Operating result        ' . $this->right($snapshot['operating_result_usd']));
        $this->newLine();
        $this->line('Nothing further may be posted into it. What share of this result, if any, becomes an');
        $this->line('investor\'s is not decided here: ' . $snapshot['investor_allocation'] . '.');

        return self::SUCCESS;
    }

    private function reopen(PeriodCloseService $service, AccountingPeriod $period): int
    {
        $service->reopen($period, (string) $this->option('reason'));

        $this->info('Period ' . $period->code . ' is open again. Its close record (' . $period->close_reference
            . ') is kept as history.');

        return self::SUCCESS;
    }

    private function period(): AccountingPeriod
    {
        if (! $this->argument('code')) {
            throw new \InvalidArgumentException('Name the period: period ' . $this->argument('action') . ' 2026-09');
        }

        $period = AccountingPeriod::where('code', $this->argument('code'))->first();

        if (! $period) {
            throw new \InvalidArgumentException('No accounting period with code ' . $this->argument('code') . '.');
        }

        return $period;
    }

    private function right(float $amount): string
    {
        return str_pad(Money::format($amount), 16, ' ', STR_PAD_LEFT);
    }
}
