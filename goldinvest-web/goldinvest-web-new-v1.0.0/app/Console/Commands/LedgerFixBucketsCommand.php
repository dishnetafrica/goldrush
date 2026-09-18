<?php

namespace App\Console\Commands;

use App\GoldTrading\Models\CapitalAllocation;
use App\Investor\Ledger\Bucket;
use App\Investor\Ledger\LedgerEvent;
use App\Investor\Models\LedgerEntry;
use App\Investor\Services\LedgerRecorder;
use App\Investor\Services\TransactionLedgerMapper;
use App\Models\Admin\Currency;
use App\Models\User;
use App\Models\UserWallet;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Corrects capital returns that credited profit-sourced capital to the wrong bucket.
 *
 * Before the split was recorded, closing a deal returned the whole commitment to
 * the available balance, even when part of it had been taken from the investor's
 * profit balance. No money was created or lost — the total position was always
 * right — but profit quietly became capital, which a statement would then report
 * as fact.
 *
 * The fix is posted, not retrofitted: the original movement stays exactly as it
 * was recorded, and a bucket_correction moves the difference back. Both remain
 * visible, which is the point of an append-only ledger.
 */
class LedgerFixBucketsCommand extends Command
{
    protected $signature = 'ledger:fix-buckets
                            {user : username or email}
                            {--dry-run : show what would be corrected and change nothing}';

    protected $description = 'Post corrections for capital returned to the wrong bucket';

    public function handle(LedgerRecorder $recorder): int
    {
        $user = User::where('username', $this->argument('user'))
            ->orWhere('email', $this->argument('user'))
            ->first();

        if (! $user) {
            $this->error('No user matches ' . $this->argument('user'));

            return self::FAILURE;
        }

        $currency = Currency::where('default', true)->first();
        $wallet = UserWallet::where('user_id', $user->id)
            ->whereHas('currency', fn ($q) => $q->where('code', $currency?->code))
            ->first();

        if (! $wallet) {
            $this->error('No default-currency wallet for ' . $user->username . '.');

            return self::FAILURE;
        }

        $pending = $this->findMisbucketed($user);

        if ($pending === []) {
            $this->info('Nothing to correct: every capital return went back to the bucket it came from.');

            return self::SUCCESS;
        }

        $this->newLine();
        $this->line('Capital returned to the wrong bucket for ' . $user->username . ':');
        $this->table(
            ['Deal', 'Returned', 'Came from profit', 'Credited to profit', 'To correct'],
            array_map(fn ($p) => [
                $p['lot_code'],
                number_format($p['allocation']->amount_usd, 2),
                number_format($p['allocation']->locked_from_profit_usd, 2),
                number_format($p['credited_to_profit'], 2),
                number_format($p['shortfall'], 2),
            ], $pending)
        );

        $total = array_sum(array_column($pending, 'shortfall'));

        $this->newLine();
        $this->line('Correction: move ' . number_format($total, 2) . ' ' . $currency->code
            . ' from ' . Bucket::label(Bucket::AVAILABLE) . ' to ' . Bucket::label(Bucket::PROFIT) . '.');
        $this->line('Total investor position does not change.');

        if ($this->option('dry-run')) {
            $this->newLine();
            $this->info('Dry run: nothing was changed.');

            return self::SUCCESS;
        }

        if ((float) $wallet->balance + 0.005 < $total) {
            $this->error('The available balance is only ' . number_format((float) $wallet->balance, 2)
                . ', which is less than the correction. Refusing, because the money has since been spent'
                . ' and moving it now would misstate the wallet.');

            return self::FAILURE;
        }

        foreach ($pending as $item) {
            DB::beginTransaction();

            try {
                $amount = $item['shortfall'];
                $wallet->refresh();
                $newBalance = (float) $wallet->balance - $amount;

                $trxId = generate_unique_string('transactions', 'trx_id', 16);

                $transactionId = DB::table('transactions')->insertGetId([
                    'type'              => TransactionLedgerMapper::TRX_BUCKET_CORRECTION,
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
                    'remark'            => 'Reclassified capital returned from deal ' . $item['lot_code']
                        . ' back to profit balance',
                    'details'           => json_encode([
                        'lot_code'      => $item['lot_code'],
                        'allocation_id' => $item['allocation']->id,
                        'from_bucket'   => Bucket::AVAILABLE,
                        'to_bucket'     => Bucket::PROFIT,
                        'amount_usd'    => $amount,
                        'reason'        => 'Capital taken from the profit balance was returned to the available'
                            . ' balance when the deal closed. Total position unchanged.',
                    ]),
                    'status'     => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $affected = DB::table('user_wallets')
                    ->where('id', $wallet->id)
                    ->where('balance', '>=', $amount)
                    ->update([
                        'balance'        => DB::raw('balance - ' . $amount),
                        'profit_balance' => DB::raw('profit_balance + ' . $amount),
                        'updated_at'     => now(),
                    ]);

                if ($affected !== 1) {
                    throw new \RuntimeException('The wallet changed while the correction was being written.');
                }

                $recorder->post(
                    $user->id,
                    LedgerEvent::BUCKET_CORRECTION,
                    [
                        ['bucket' => Bucket::AVAILABLE, 'amount' => -$amount],
                        ['bucket' => Bucket::PROFIT,    'amount' => $amount],
                    ],
                    [
                        'trx_id'         => $trxId,
                        'transaction_id' => $transactionId,
                        'gold_lot_id'    => $item['allocation']->gold_lot_id,
                        'allocation_id'  => $item['allocation']->id,
                        'description'    => 'Capital from deal ' . $item['lot_code']
                            . ' reclassified back to profit balance',
                        'meta'           => [
                            'stage'              => TransactionLedgerMapper::STAGE_POSTED,
                            'corrects_trx_id'    => $item['allocation']->release_trx_id,
                            'reason'             => 'capital_returned_to_wrong_bucket',
                        ],
                    ]
                );

                DB::commit();

                $this->info('Corrected ' . number_format($amount, 2) . ' ' . $currency->code
                    . ' for deal ' . $item['lot_code'] . '  (' . $trxId . ')');
            } catch (\Throwable $e) {
                DB::rollBack();
                $this->error('Correction for ' . $item['lot_code'] . ' failed: ' . $e->getMessage());

                return self::FAILURE;
            }
        }

        $this->newLine();
        $this->info('Corrections posted. Run ledger:check ' . $user->username . ' to confirm.');

        return self::SUCCESS;
    }

    /**
     * Finds closed allocations whose profit-sourced capital did not go back to the
     * profit balance, and which have not already been corrected.
     */
    private function findMisbucketed(User $user): array
    {
        $allocations = CapitalAllocation::with('lot')
            ->where('user_id', $user->id)
            ->where('status', CapitalAllocation::STATUS_RETURNED)
            ->where('locked_balance', true)
            ->where('locked_from_profit_usd', '>', 0)
            ->get();

        $pending = [];

        foreach ($allocations as $allocation) {
            $alreadyCorrected = LedgerEntry::where('user_id', $user->id)
                ->where('event_type', LedgerEvent::BUCKET_CORRECTION)
                ->where('allocation_id', $allocation->id)
                ->exists();

            if ($alreadyCorrected) {
                continue;
            }

            $creditedToProfit = (float) LedgerEntry::where('user_id', $user->id)
                ->where('trx_id', $allocation->release_trx_id)
                ->where('event_type', LedgerEvent::CAPITAL_RETURNED)
                ->where('bucket', Bucket::PROFIT)
                ->sum('amount_usd');

            $shortfall = round((float) $allocation->locked_from_profit_usd - $creditedToProfit, 8);

            if ($shortfall > 0.005) {
                $pending[] = [
                    'allocation'         => $allocation,
                    'lot_code'           => $allocation->lot->lot_code ?? ('#' . $allocation->gold_lot_id),
                    'credited_to_profit' => $creditedToProfit,
                    'shortfall'          => $shortfall,
                ];
            }
        }

        return $pending;
    }
}
