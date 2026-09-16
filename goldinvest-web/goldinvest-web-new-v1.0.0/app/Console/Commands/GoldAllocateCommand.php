<?php

namespace App\Console\Commands;

use App\GoldTrading\Models\CapitalAllocation;
use App\GoldTrading\Models\GoldLot;
use App\Models\Admin\Currency;
use App\Models\User;
use App\Models\UserWallet;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Puts an investor's money into a deal.
 *
 * By default this commits the money: the investor's spendable balance is debited
 * and the amount is held against the lot until the deal is paid out. That is what
 * stops the same money being withdrawn through Money Out while it is physically
 * sitting in gold. Pass --no-lock to record the funding without moving anything,
 * which is only right when the cash never passed through the platform.
 */
class GoldAllocateCommand extends Command
{
    protected $signature = 'gold:allocate
                            {lot : lot code}
                            {--investor= : username or email}
                            {--amount= : USD to put into this deal}
                            {--all : use everything the investor has available}
                            {--from=balance : balance|profit|both — which wallet the money comes from}
                            {--share= : this investor\'s profit share for this deal, in percent}
                            {--no-lock : record the funding without debiting the wallet}
                            {--note= }
                            {--dry-run : show what would happen and change nothing}';

    protected $description = 'Commit an investor\'s money to a gold deal';

    public function handle(): int
    {
        $lot = GoldLot::where('lot_code', $this->argument('lot'))->first();
        if (! $lot) {
            $this->error('No lot found with code ' . $this->argument('lot'));

            return self::FAILURE;
        }

        $identifier = $this->option('investor');
        if (! $identifier) {
            $this->error('Name the investor: --investor=username');

            return self::FAILURE;
        }

        $user = User::where('username', $identifier)->orWhere('email', $identifier)->first();
        if (! $user) {
            $this->error('No user matches ' . $identifier);

            return self::FAILURE;
        }

        if (CapitalAllocation::where('gold_lot_id', $lot->id)->where('user_id', $user->id)->exists()) {
            $this->error($user->username . ' already has capital recorded against ' . $lot->lot_code . '.');
            $this->line('Remove or amend that allocation first rather than adding a second one.');

            return self::FAILURE;
        }

        $from = strtolower((string) $this->option('from'));
        if (! in_array($from, ['balance', 'profit', 'both'], true)) {
            $this->error('--from must be balance, profit or both.');

            return self::FAILURE;
        }

        $currency = Currency::where('default', true)->first();
        if (! $currency) {
            $this->error('No default currency is configured.');

            return self::FAILURE;
        }

        $wallet = UserWallet::where('user_id', $user->id)
            ->whereHas('currency', fn ($q) => $q->where('code', $currency->code))
            ->first();

        if (! $wallet) {
            $this->error('No ' . $currency->code . ' wallet for ' . $user->username . '.');

            return self::FAILURE;
        }

        $lock = ! $this->option('no-lock');

        $fromBalance = in_array($from, ['balance', 'both'], true) ? (float) $wallet->balance : 0.0;
        $fromProfit  = in_array($from, ['profit', 'both'], true) ? (float) $wallet->profit_balance : 0.0;
        $available   = $fromBalance + $fromProfit;

        if ($this->option('all')) {
            $amount = $available;
        } elseif ($this->option('amount') !== null) {
            $amount = (float) $this->option('amount');
        } else {
            $this->error('Give an amount: --amount=2943.31, or --all.');

            return self::FAILURE;
        }

        $amount = round($amount, 8);

        if ($amount <= 0) {
            $this->error('There is nothing to allocate.');

            return self::FAILURE;
        }

        if ($lock && $amount > $available + 0.00000001) {
            $this->error($user->username . ' has ' . number_format($available, 2) . ' ' . $currency->code
                . ' available from ' . $from . ', which is less than ' . number_format($amount, 2) . '.');
            $this->line('  Current balance: ' . number_format((float) $wallet->balance, 2));
            $this->line('  Profit balance:  ' . number_format((float) $wallet->profit_balance, 2));

            return self::FAILURE;
        }

        // Spend the current balance before the profit balance, so profit stays liquid
        // for as long as the investor's own instruction allows.
        $takeFromBalance = min($amount, $fromBalance);
        $takeFromProfit  = round($amount - $takeFromBalance, 8);

        $lockedFrom = match (true) {
            $takeFromProfit <= 0  => CapitalAllocation::FROM_BALANCE,
            $takeFromBalance <= 0 => CapitalAllocation::FROM_PROFIT,
            default               => CapitalAllocation::FROM_MIXED,
        };

        // Capital committed beyond what the gold actually cost earns nothing: the
        // profit is made by the grams, not by the size of the float sitting behind
        // them. Say so before the money is tied up rather than after.
        $alreadyIn = (float) CapitalAllocation::where('gold_lot_id', $lot->id)
            ->whereIn('status', [CapitalAllocation::STATUS_ALLOCATED])
            ->sum('amount_usd');
        $lotCost = (float) $lot->total_cost_usd;
        $overFunded = round($alreadyIn + $amount - $lotCost, 2);

        $this->line('Deal      : ' . $lot->lot_code . ' — ' . ($lot->project_name ?? ''));
        $this->line('Investor  : ' . $user->username);
        $this->line('Amount    : ' . number_format($amount, 2) . ' ' . $currency->code);
        if ($lock) {
            $this->line('Taken from: ' . number_format($takeFromBalance, 2) . ' current balance + '
                . number_format($takeFromProfit, 2) . ' profit balance');
            $this->line('After     : balance ' . number_format((float) $wallet->balance - $takeFromBalance, 2)
                . ', profit ' . number_format((float) $wallet->profit_balance - $takeFromProfit, 2)
                . ', committed to this deal ' . number_format($amount, 2));
        } else {
            $this->warn('Attribution only: no wallet balance will move, and this money stays withdrawable.');
        }
        if ($this->option('share') !== null) {
            $this->line('Share     : ' . number_format((float) $this->option('share'), 2) . ' % of this deal\'s profit');
        }

        $this->newLine();
        $this->line('This deal cost ' . number_format($lotCost, 2) . ' ' . $currency->code
            . '; capital committed to it would be ' . number_format($alreadyIn + $amount, 2) . '.');

        if ($overFunded > 0.01) {
            $this->warn('That is ' . number_format($overFunded, 2) . ' ' . $currency->code
                . ' more than the gold cost. The extra earns nothing and cannot be withdrawn'
                . ' until this deal closes.');
            $this->line('Commit ' . number_format(max(0, $lotCost - $alreadyIn), 2) . ' instead to leave the rest liquid.');

            if (! $this->option('dry-run') && ! $this->confirm('Commit the full amount anyway?', false)) {
                return self::SUCCESS;
            }
        }

        if ($this->option('dry-run')) {
            $this->newLine();
            $this->info('Dry run: nothing was changed.');

            return self::SUCCESS;
        }

        DB::beginTransaction();
        try {
            $trxId = null;

            if ($lock) {
                $trxId = generate_unique_string('transactions', 'trx_id', 16, 'GC');
                $newBalance = (float) $wallet->balance - $takeFromBalance;

                DB::table('transactions')->insert([
                    'type'              => CapitalAllocation::TRX_LOCK,
                    'trx_id'            => $trxId,
                    'user_type'         => 'USER',
                    'user_id'           => $user->id,
                    'wallet_id'         => $wallet->id,
                    'request_amount'    => $amount,
                    'request_currency'  => $currency->code,
                    'exchange_rate'     => 1,
                    'percent_charge'    => 0,
                    'fixed_charge'      => 0,
                    'total_charge'      => 0,
                    'total_payable'     => $amount,
                    'receive_amount'    => $amount,
                    'receiver_type'     => 'USER',
                    'receiver_id'       => $user->id,
                    'available_balance' => $newBalance,
                    'payment_currency'  => $currency->code,
                    'remark'            => 'Capital committed to gold deal (' . $lot->lot_code . ')',
                    'details'           => json_encode([
                        'lot_code'          => $lot->lot_code,
                        'project'           => $lot->project_name,
                        'purchase_date'     => optional($lot->purchase_date)->toDateString(),
                        'from_balance_usd'  => $takeFromBalance,
                        'from_profit_usd'   => $takeFromProfit,
                        'direction'         => 'out',
                    ]),
                    'status'     => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $affected = DB::table('user_wallets')
                    ->where('id', $wallet->id)
                    ->where('balance', '>=', $takeFromBalance)
                    ->where('profit_balance', '>=', $takeFromProfit)
                    ->update([
                        'balance'        => DB::raw('balance - ' . $takeFromBalance),
                        'profit_balance' => DB::raw('profit_balance - ' . $takeFromProfit),
                        'updated_at'     => now(),
                    ]);

                if ($affected !== 1) {
                    throw new \RuntimeException('The wallet balance changed while this allocation was being written. Nothing was debited.');
                }
            }

            CapitalAllocation::create([
                'gold_lot_id'             => $lot->id,
                'user_id'                 => $user->id,
                'amount_usd'              => $amount,
                'share_percent'           => $this->option('share') !== null ? (float) $this->option('share') : null,
                'allocated_at'            => now()->toDateString(),
                'status'                  => CapitalAllocation::STATUS_ALLOCATED,
                'locked_balance'          => $lock,
                'locked_from'             => $lock ? $lockedFrom : null,
                'locked_from_balance_usd' => $lock ? $takeFromBalance : 0,
                'locked_from_profit_usd'  => $lock ? $takeFromProfit : 0,
                'lock_trx_id'             => $trxId,
                'notes'                   => $this->option('note'),
            ]);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Allocation failed: ' . $e->getMessage());

            return self::FAILURE;
        }

        $this->newLine();
        if ($lock) {
            $this->info('Committed ' . number_format($amount, 2) . ' ' . $currency->code . ' to ' . $lot->lot_code
                . ' for ' . $user->username . ($trxId ? '  (' . $trxId . ')' : ''));
            $this->line('That money is now out of reach of Money Out until this deal is paid out.');
        } else {
            $this->info('Recorded ' . number_format($amount, 2) . ' ' . $currency->code . ' of funding for '
                . $lot->lot_code . ' without touching any balance.');
        }

        return self::SUCCESS;
    }
}
