<?php

namespace App\Console\Commands;

use App\Accounting\Ledger\AccountType;
use App\Accounting\Models\Account;
use App\Accounting\Models\AccountingPeriod;
use App\Accounting\Services\ChartOfAccounts;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Sets up the chart of accounts and the monthly periods.
 *
 * Idempotent: it creates what is missing and leaves what exists alone, so it can
 * be run again after a deploy without disturbing anything already posted.
 */
class AccountingInstallCommand extends Command
{
    protected $signature = 'accounting:install
                            {--from=2026-09-01 : first period start, the approved opening date}
                            {--through= : last month to create, YYYY-MM (default: this month)}';

    protected $description = 'Install the chart of accounts and monthly accounting periods';

    public function handle(ChartOfAccounts $chart): int
    {
        $result = $chart->install();

        $this->info('Chart of accounts: ' . count($result['created']) . ' created, '
            . count($result['existing']) . ' already present.');

        if ($result['created'] !== []) {
            $this->line('  created: ' . implode(', ', $result['created']));
        }

        $from = Carbon::parse($this->option('from'))->startOfMonth();
        $through = $this->option('through')
            ? Carbon::parse($this->option('through') . '-01')->startOfMonth()
            : Carbon::now()->startOfMonth();

        if ($through->lt($from)) {
            $this->error('--through is before --from.');

            return self::FAILURE;
        }

        $createdPeriods = [];
        $cursor = $from->copy();

        while ($cursor->lte($through)) {
            $code = $cursor->format('Y-m');

            if (! AccountingPeriod::where('code', $code)->exists()) {
                AccountingPeriod::create([
                    'code'      => $code,
                    'starts_on' => $cursor->copy()->startOfMonth()->toDateString(),
                    'ends_on'   => $cursor->copy()->endOfMonth()->toDateString(),
                    'status'    => AccountingPeriod::OPEN,
                ]);

                $createdPeriods[] = $code;
            }

            $cursor->addMonth();
        }

        $this->info('Periods: ' . count($createdPeriods) . ' created.');

        if ($createdPeriods !== []) {
            $this->line('  created: ' . implode(', ', $createdPeriods));
        }

        $this->newLine();
        $this->table(
            ['Code', 'Account', 'Type', 'Normal', 'Control'],
            Account::orderBy('code')->get()->map(fn (Account $a) => [
                $a->code, $a->name, AccountType::label($a->type), $a->normal_balance, $a->control_of ?? '-',
            ])->all()
        );

        $this->table(
            ['Period', 'From', 'To', 'Status'],
            AccountingPeriod::orderBy('starts_on')->get()->map(fn (AccountingPeriod $p) => [
                $p->code, $p->starts_on->format('d M Y'), $p->ends_on->format('d M Y'), $p->status,
            ])->all()
        );

        return self::SUCCESS;
    }
}
