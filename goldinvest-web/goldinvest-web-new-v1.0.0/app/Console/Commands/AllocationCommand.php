<?php

namespace App\Console\Commands;

use App\Accounting\Models\AccountingPeriod;
use App\Accounting\Services\InvestorAllocation;
use App\Investor\Support\Money;
use App\Models\User;
use Illuminate\Console\Command;

/**
 * What a period's investor allocation would be, and why it cannot yet be made.
 *
 * A preview only. It moves nothing, and there is deliberately no action here
 * that does: the distribution itself is a separate, later approval.
 */
class AllocationCommand extends Command
{
    protected $signature = 'allocation
                            {action=preview : preview}
                            {period? : the period code, e.g. 2026-09}';

    protected $description = 'Preview the investor allocation of a period\'s realized result under the approved policy';

    public function handle(InvestorAllocation $allocation): int
    {
        if (strtolower((string) $this->argument('action')) !== 'preview') {
            $this->error('Only preview is available. Distribution is not implemented.');

            return self::FAILURE;
        }

        if (! $this->argument('period')) {
            $this->error('Name the period: allocation preview 2026-09');

            return self::FAILURE;
        }

        $period = AccountingPeriod::where('code', $this->argument('period'))->first();

        if (! $period) {
            $this->error('No accounting period with code ' . $this->argument('period') . '.');

            return self::FAILURE;
        }

        $a = $allocation->forPeriod($period);

        $this->line('INVESTOR ALLOCATION PREVIEW   ' . $a['period'] . ' (' . $a['period_status'] . ')');
        $this->line('Policy: ' . $a['policy']);
        $this->line(str_repeat('-', 78));

        if ($a['eligible'] !== []) {
            $this->table(
                ['Deal', 'Realized', 'Policy', 'Pool basis', 'Share %', 'To investors', 'Company keeps'],
                array_map(fn ($d) => [
                    $d['lot_code'], Money::format($d['realized_usd']), $d['expense_policy'],
                    Money::format($d['pool_basis_usd']), number_format($d['investor_share_pct'], 2),
                    Money::format($d['investor_usd']), Money::format($d['company_usd']),
                ], $a['eligible'])
            );
        } else {
            $this->warn('No eligible finalized result in this period.');
        }

        foreach ($a['ineligible'] as $i) {
            $this->line('  not eligible  ' . $i['lot_code'] . ': ' . $i['reason']);
        }

        foreach ($a['blocked'] as $b) {
            $this->warn('  BLOCKED       ' . $b['lot_code'] . ': ' . $b['reason']);
        }

        $this->newLine();
        $this->line('  Company realized trading result   ' . $this->right($a['company_trading_result_usd']));
        $this->line('  Overheads belonging to no deal    ' . $this->right($a['company_overheads_excluded_usd'])
            . '   excluded from the pool');
        $this->line('  Eligible realized results         ' . $this->right($a['eligible_realized_usd']));
        $this->line('  Gross investor pool               ' . $this->right($a['gross_investor_pool_usd']));
        $this->line('  Reserve (' . number_format($a['reserve_percent'], 2) . ' %)                  '
            . $this->right(-$a['reserve_usd']));
        $this->line('  ' . str_repeat('-', 50));
        $this->line('  Investor allocation pool          ' . $this->right($a['investor_pool_usd']));
        $this->line('  Company retains                   ' . $this->right($a['company_retains_usd']));
        $this->line('  Loss policy                       ' . $a['loss_policy_status']);

        if ($a['by_investor'] !== []) {
            $this->newLine();
            $this->table(['Investor', 'Allocation'], array_map(fn ($uid, $amt) => [
                User::find($uid)?->username ?? ('user #' . $uid), Money::exact($amt),
            ], array_keys($a['by_investor']), $a['by_investor']));
        }

        $this->newLine();

        if ($a['distributable']) {
            $this->info('This allocation could be distributed. Distribution is not implemented; nothing was moved.');
        } elseif ($a['nothing_to_distribute']) {
            $this->info('Nothing to distribute.');
        } else {
            $this->warn('Cannot be distributed:');
            foreach ($a['refusals'] as $r) {
                $this->line('  - ' . $r);
            }
        }

        return self::SUCCESS;
    }

    private function right(float $amount): string
    {
        return str_pad(Money::format($amount), 16, ' ', STR_PAD_LEFT);
    }
}
