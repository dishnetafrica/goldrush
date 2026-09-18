<?php

namespace App\Console\Commands;

use App\Accounting\Models\AccountingPeriod;
use App\Accounting\Models\InvestorDistribution;
use App\Accounting\Services\InvestorAllocation;
use App\Accounting\Services\InvestorDistributor;
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
                            {action=preview : preview|distribute|reverse|show}
                            {period? : the period code, e.g. 2026-09}
                            {--confirm : required to distribute; nothing moves without it}
                            {--date= : the date the distribution is declared (default today)}
                            {--reason= : why a distribution is being reversed}
                            {--note= }';

    protected $description = 'Preview, distribute, show or reverse the investor allocation of a period';

    public function handle(InvestorAllocation $allocation, InvestorDistributor $distributor): int
    {
        if (! $this->argument('period')) {
            $this->error('Name the period: allocation ' . $this->argument('action') . ' 2026-09');

            return self::FAILURE;
        }

        $period = AccountingPeriod::where('code', $this->argument('period'))->first();

        if (! $period) {
            $this->error('No accounting period with code ' . $this->argument('period') . '.');

            return self::FAILURE;
        }

        try {
            return match (strtolower((string) $this->argument('action'))) {
                'preview'    => $this->preview($allocation, $period),
                'distribute' => $this->distribute($distributor, $period),
                'show'       => $this->show($distributor, $period),
                'reverse'    => $this->reverse($distributor, $period),
                default      => throw new \InvalidArgumentException('Action must be preview, distribute, show or reverse.'),
            };
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }

    private function distribute(InvestorDistributor $distributor, AccountingPeriod $period): int
    {
        if (! $this->option('confirm')) {
            $this->warn('This creates a liability the company owes and credits investors. Nothing was done.');
            $this->line('Preview it first, then run again with --confirm.');

            return self::FAILURE;
        }

        $already = $distributor->current($period);
        $distribution = $distributor->distribute($period, null, array_filter([
            'date' => $this->option('date'), 'note' => $this->option('note'),
        ]));

        $this->info(($already ? 'Already distributed as ' : 'Distributed as ') . $distribution->reference
            . ($already ? '; nothing was moved a second time.' : '.'));

        return $this->show($distributor, $period);
    }

    private function show(InvestorDistributor $distributor, AccountingPeriod $period): int
    {
        $distribution = $distributor->current($period)
            ?? InvestorDistribution::where('accounting_period_id', $period->id)->orderByDesc('id')->with('lines')->first();

        if (! $distribution) {
            $this->warn('Period ' . $period->code . ' has no distribution.');

            return self::SUCCESS;
        }

        $this->line('DISTRIBUTION ' . $distribution->reference . '   ' . $distribution->status);
        $this->table(['', ''], [
            ['Period', $period->code],
            ['Journal', $distribution->journal?->reference . '   Dr 7000 / Cr 2010 ' . Money::exact($distribution->pool_usd)],
            ['Company result', Money::format($distribution->company_result_usd)],
            ['Investor pool', Money::exact($distribution->pool_usd)],
            ['Investors', $distribution->investors_count],
            ['Distributed', $distribution->distributed_at->format('d M Y H:i')
                . ($distribution->distributedBy ? ' by ' . $distribution->distributedBy->username : ' (console)')],
            ['Reversed', $distribution->reversed_at ? $distribution->reversed_at->format('d M Y H:i') . ' - ' . $distribution->reversal_reason : '-'],
        ]);
        $this->table(['Investor', 'Credited', 'Transaction', 'Ledger entry'], $distribution->lines->map(fn ($l) => [
            User::find($l->user_id)?->username ?? ('user #' . $l->user_id),
            Money::exact($l->amount_usd), $l->trx_id, $l->ledger_reference,
        ])->all());

        return self::SUCCESS;
    }

    private function reverse(InvestorDistributor $distributor, AccountingPeriod $period): int
    {
        $distribution = $distributor->current($period);

        if (! $distribution) {
            $this->error('Period ' . $period->code . ' has no posted distribution to reverse.');

            return self::FAILURE;
        }

        $distributor->reverse($distribution, (string) $this->option('reason'));
        $this->info('Reversed ' . $distribution->reference . '. Both entries stay in the books.');

        return $this->show($distributor, $period);
    }

    private function preview(InvestorAllocation $allocation, AccountingPeriod $period): int
    {
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
            $this->info('This allocation can be distributed: allocation distribute ' . $period->code . ' --confirm. Nothing was moved.');
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
