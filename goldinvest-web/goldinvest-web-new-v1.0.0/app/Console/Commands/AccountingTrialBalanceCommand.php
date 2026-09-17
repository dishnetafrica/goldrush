<?php

namespace App\Console\Commands;

use App\Accounting\Ledger\AccountType;
use App\Accounting\Models\AccountingPeriod;
use App\Accounting\Services\TrialBalance;
use App\Investor\Support\Money;
use Illuminate\Console\Command;

/**
 * Prints the trial balance.
 *
 * If the totals differ the books do not hold together, and nothing downstream —
 * no report, no period close, no distribution — should be trusted until they do.
 */
class AccountingTrialBalanceCommand extends Command
{
    protected $signature = 'accounting:trial-balance
                            {--as-at= : include journals up to this date, YYYY-MM-DD}
                            {--period= : restrict to one period code, e.g. 2026-09}';

    protected $description = 'Print the general ledger trial balance';

    public function handle(TrialBalance $trialBalance): int
    {
        $periodId = null;

        if ($this->option('period')) {
            $period = AccountingPeriod::where('code', $this->option('period'))->first();

            if (! $period) {
                $this->error('No period with code ' . $this->option('period'));

                return self::FAILURE;
            }

            $periodId = $period->id;
        }

        $report = $trialBalance->build($this->option('as-at'), $periodId);

        $this->newLine();
        $this->line('TRIAL BALANCE   ' . ($report['period'] ?? 'all periods')
            . ($report['as_at'] ? '   as at ' . $report['as_at'] : ''));
        $this->line(str_repeat('-', 78));

        if ($report['rows'] === []) {
            $this->warn('No journals have been posted yet.');

            return self::SUCCESS;
        }

        $this->table(
            ['Code', 'Account', 'Type', 'Debits', 'Credits', 'Balance'],
            array_map(fn ($r) => [
                $r['code'], $r['name'], $r['type'],
                Money::format($r['debits']), Money::format($r['credits']), Money::format($r['balance']),
            ], $report['rows'])
        );

        $this->line('  Total debits  : ' . Money::format($report['total_debits']));
        $this->line('  Total credits : ' . Money::format($report['total_credits']));
        $this->line('  Difference    : ' . Money::exact($report['difference']));
        $this->line('  Journals      : ' . $report['journals']['posted'] . ' posted, '
            . $report['journals']['reversed'] . ' reversed');
        $this->newLine();

        foreach ($report['by_type'] as $type => $balance) {
            $this->line('  ' . str_pad(AccountType::label($type), 22) . Money::format($balance));
        }

        $this->newLine();

        if (! $report['balanced']) {
            $this->error('OUT OF BALANCE by ' . Money::exact($report['difference'])
                . '. Do not close a period or issue reports until this is resolved.');

            return self::FAILURE;
        }

        $this->info('BALANCED - debits equal credits.');

        return self::SUCCESS;
    }
}
