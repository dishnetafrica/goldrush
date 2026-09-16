<?php

namespace App\Console\Commands;

use App\GoldTrading\Models\CapitalAllocation;
use App\GoldTrading\Models\GoldLot;
use App\GoldTrading\Models\LotResult;
use App\GoldTrading\Models\ProfitDistribution;
use App\GoldTrading\Services\LotResultCalculator;
use App\Models\Admin\Currency;
use App\Models\User;
use App\Models\UserWallet;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Pays a closed deal's profit to the investors who funded it.
 *
 * Capital is not touched: it already sits in the investor's spendable balance as a
 * claim on the company. Only profit is credited, to profit_balance, together with a
 * transactions row so the payment is visible in the investor's own history.
 *
 * A deal can only be paid once: gold_profit_distributions has a unique key per
 * (lot, investor).
 */
class GoldDistributeCommand extends Command
{
    protected $signature = 'gold:distribute
                            {lot : lot code}
                            {--dry-run : show what would be paid and change nothing}';

    protected $description = 'Credit a closed gold deal\'s profit to the investors who funded it';

    public function handle(LotResultCalculator $calculator): int
    {
        $lot = GoldLot::with('allocations')->where('lot_code', $this->argument('lot'))->first();

        if (! $lot) {
            $this->error('No lot found with code ' . $this->argument('lot'));

            return self::FAILURE;
        }

        $result = $calculator->forLot($lot);
        $split = $calculator->splitResult($lot, $result);

        if ($split['missing_terms']) {
            $this->error('This deal has no agreed profit share. Record it first:');
            $this->line('  php artisan gold:set-terms ' . $lot->lot_code . ' --investor-share=NN');

            return self::FAILURE;
        }

        if ($result['remaining_grams'] > 0) {
            $this->warn('This lot still holds ' . number_format($result['remaining_grams'], 4) . ' g of unsold gold. Paying now distributes profit on the part already sold.');
            if (! $this->option('dry-run') && ! $this->confirm('Continue?', false)) {
                return self::SUCCESS;
            }
        }

        if ($result['expenses_usd'] <= 0) {
            $this->warn('No expenses are recorded against this lot, so the profit being paid is an upper bound.');
        }

        $alreadyPaid = ProfitDistribution::where('gold_lot_id', $lot->id)
            ->where('status', ProfitDistribution::STATUS_PAID)
            ->pluck('user_id')
            ->all();

        $defaultCurrency = Currency::where('default', true)->first();
        if (! $defaultCurrency) {
            $this->error('No default currency is configured.');

            return self::FAILURE;
        }

        $rows = [];
        $toPay = [];

        foreach ($split['investors'] as $line) {
            $user = User::find($line['user_id']);
            $paid = in_array($line['user_id'], $alreadyPaid, true);

            $rows[] = [
                $user?->username ?? ('user #' . $line['user_id']),
                number_format($line['capital_usd'], 2),
                number_format($line['profit_share_percent'], 2) . ' %',
                number_format($line['profit_usd'], 2),
                $paid ? 'already paid' : ($line['profit_usd'] > 0 ? 'to pay' : 'nothing to pay'),
            ];

            if (! $paid && $line['profit_usd'] > 0 && $user) {
                $toPay[] = [$user, $line];
            }
        }

        $this->line('Deal ' . $lot->lot_code . ' — net profit ' . number_format($result['net_profit_usd'], 2) . ' USD');
        $this->table(['Investor', 'Capital', 'Profit share', 'Profit', 'State'], $rows);

        if ($toPay === []) {
            $this->info('No profit left to pay for this deal.');

            if (! $this->option('dry-run')) {
                $this->releaseCapital($lot, $defaultCurrency);
            }

            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->info('Dry run: nothing was changed.');

            return self::SUCCESS;
        }

        foreach ($toPay as [$user, $line]) {
            $wallet = UserWallet::where('user_id', $user->id)
                ->whereHas('currency', fn ($q) => $q->where('code', $defaultCurrency->code))
                ->first();

            if (! $wallet) {
                $this->error('No ' . $defaultCurrency->code . ' wallet for ' . $user->username . '. Skipped.');
                continue;
            }

            DB::beginTransaction();
            try {
                $trxId = generate_unique_string('transactions', 'trx_id', 16, 'GP');
                $newProfitBalance = (float) $wallet->profit_balance + $line['profit_usd'];

                DB::table('transactions')->insert([
                    'type'              => ProfitDistribution::TRX_TYPE,
                    'trx_id'            => $trxId,
                    'user_type'         => 'USER',
                    'user_id'           => $user->id,
                    'wallet_id'         => $wallet->id,
                    'request_amount'    => $line['profit_usd'],
                    'request_currency'  => $defaultCurrency->code,
                    'exchange_rate'     => 1,
                    'percent_charge'    => 0,
                    'fixed_charge'      => 0,
                    'total_charge'      => 0,
                    'total_payable'     => $line['profit_usd'],
                    'receive_amount'    => $line['profit_usd'],
                    'receiver_type'     => 'USER',
                    'receiver_id'       => $user->id,
                    'available_balance' => $newProfitBalance,
                    'payment_currency'  => $defaultCurrency->code,
                    'remark'            => 'Gold deal profit (' . $lot->lot_code . ')',
                    'details'           => json_encode([
                        'lot_code'              => $lot->lot_code,
                        'project'               => $lot->project_name,
                        'refined_grams'         => $result['refined_grams'],
                        'sold_grams'            => $result['sold_grams'],
                        'proceeds_usd'          => $result['proceeds_usd'],
                        'cost_of_goods_sold_usd' => $result['cost_of_goods_sold_usd'],
                        'expenses_usd'          => $result['expenses_usd'],
                        'net_profit_usd'        => $result['net_profit_usd'],
                        'capital_share_percent' => $line['capital_share_percent'],
                        'profit_share_percent'  => $line['profit_share_percent'],
                        'expense_policy'        => $split['applied_expense_policy'],
                    ]),
                    'status'     => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::table('user_wallets')->where('id', $wallet->id)
                    ->increment('profit_balance', $line['profit_usd']);

                ProfitDistribution::create([
                    'gold_lot_id'           => $lot->id,
                    'user_id'               => $user->id,
                    'capital_usd'           => $line['capital_usd'],
                    'capital_share_percent' => $line['capital_share_percent'],
                    'profit_share_percent'  => $line['profit_share_percent'],
                    'amount_usd'            => $line['profit_usd'],
                    'wallet_id'             => $wallet->id,
                    'trx_id'                => $trxId,
                    'credited_to'           => 'profit_balance',
                    'distributed_at'        => now()->toDateString(),
                    'status'                => ProfitDistribution::STATUS_PAID,
                ]);

                DB::commit();

                $this->info('Paid ' . number_format($line['profit_usd'], 2) . ' ' . $defaultCurrency->code
                    . ' to ' . $user->username . '  (' . $trxId . ')');
            } catch (\Throwable $e) {
                DB::rollBack();
                $this->error('Payment to ' . $user->username . ' failed: ' . $e->getMessage());

                return self::FAILURE;
            }
        }

        $this->releaseCapital($lot, $defaultCurrency);

        // The ledger pulls rather than being pushed to, so nudge it now instead of
        // leaving the investor's position a minute out of date after a payout.
        foreach ($toPay as [$user, $line]) {
            $this->call('ledger:sync', ['user' => $user->username, '--quiet-success' => true]);
        }

        LotResult::updateOrCreate(
            ['gold_lot_id' => $lot->id],
            [
                'closed_at'              => now()->toDateString(),
                'refined_grams'          => $result['refined_grams'],
                'sold_grams'             => $result['sold_grams'],
                'cost_of_goods_sold_usd' => $result['cost_of_goods_sold_usd'],
                'expenses_usd'           => $result['expenses_usd'],
                'proceeds_usd'           => $result['proceeds_usd'],
                'gross_profit_usd'       => $result['gross_profit_usd'],
                'net_profit_usd'         => $result['net_profit_usd'],
                'investor_share_percent' => $lot->investor_share_percent ?? 0,
                'investor_profit_usd'    => $split['investor_profit_usd'],
                'company_profit_usd'     => $split['company_profit_usd'],
                'status'                 => LotResult::STATUS_DISTRIBUTED,
            ]
        );

        return self::SUCCESS;
    }

    /**
     * Returns capital that was committed to this deal to the investors' spendable
     * balance, now that the gold has been sold and the money is back in the business.
     *
     * Attribution-only allocations are left alone: nothing was ever debited for them,
     * so the investor's claim never left their balance in the first place.
     */
    private function releaseCapital(GoldLot $lot, Currency $currency): void
    {
        $allocations = CapitalAllocation::where('gold_lot_id', $lot->id)
            ->where('status', CapitalAllocation::STATUS_ALLOCATED)
            ->where('locked_balance', true)
            ->get();

        $this->newLine();

        if ($allocations->isEmpty()) {
            $this->line('No committed capital to return: the investors\' capital claims never left their balance.');

            return;
        }

        foreach ($allocations as $allocation) {
            $user = User::find($allocation->user_id);

            $wallet = $user
                ? UserWallet::where('user_id', $user->id)
                    ->whereHas('currency', fn ($q) => $q->where('code', $currency->code))
                    ->first()
                : null;

            if (! $user || ! $wallet) {
                $this->error('Could not return capital for allocation #' . $allocation->id . '. Skipped.');
                continue;
            }

            // Each part of the commitment goes back to the bucket it was taken
            // from, so that closing a deal never turns profit into capital.
            $split = $allocation->returnSplit();

            DB::beginTransaction();
            try {
                $trxId = generate_unique_string('transactions', 'trx_id', 16, 'GR');
                $newBalance = (float) $wallet->balance + $split['available'];

                DB::table('transactions')->insert([
                    'type'              => CapitalAllocation::TRX_RELEASE,
                    'trx_id'            => $trxId,
                    'user_type'         => 'USER',
                    'user_id'           => $user->id,
                    'wallet_id'         => $wallet->id,
                    'request_amount'    => $allocation->amount_usd,
                    'request_currency'  => $currency->code,
                    'exchange_rate'     => 1,
                    'percent_charge'    => 0,
                    'fixed_charge'      => 0,
                    'total_charge'      => 0,
                    'total_payable'     => $allocation->amount_usd,
                    'receive_amount'    => $allocation->amount_usd,
                    'receiver_type'     => 'USER',
                    'receiver_id'       => $user->id,
                    'available_balance' => $newBalance,
                    'payment_currency'  => $currency->code,
                    'remark'            => 'Capital returned from gold deal (' . $lot->lot_code . ')',
                    'details'           => json_encode([
                        'lot_code'     => $lot->lot_code,
                        'project'      => $lot->project_name,
                        'committed_on'    => optional($allocation->allocated_at)->toDateString(),
                        'lock_trx_id'     => $allocation->lock_trx_id,
                        'to_balance_usd'  => $split['available'],
                        'to_profit_usd'   => $split['profit'],
                        'direction'       => 'in',
                    ]),
                    'status'     => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::table('user_wallets')->where('id', $wallet->id)->update([
                    'balance'        => DB::raw('balance + ' . $split['available']),
                    'profit_balance' => DB::raw('profit_balance + ' . $split['profit']),
                    'updated_at'     => now(),
                ]);

                $allocation->update([
                    'status'         => CapitalAllocation::STATUS_RETURNED,
                    'release_trx_id' => $trxId,
                    'released_at'    => now()->toDateString(),
                ]);

                DB::commit();

                $this->info('Returned ' . number_format($allocation->amount_usd, 2) . ' ' . $currency->code
                    . ' of capital to ' . $user->username . '  (' . $trxId . ')');

                if ($split['profit'] > 0) {
                    $this->line('  ' . number_format($split['available'], 2) . ' to the available balance, '
                        . number_format($split['profit'], 2) . ' back to the profit balance it came from.');
                }
            } catch (\Throwable $e) {
                DB::rollBack();
                $this->error('Capital return for ' . $user->username . ' failed: ' . $e->getMessage());
            }
        }
    }
}
