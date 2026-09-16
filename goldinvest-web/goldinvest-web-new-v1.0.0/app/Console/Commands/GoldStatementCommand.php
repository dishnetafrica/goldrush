<?php

namespace App\Console\Commands;

use App\Constants\PaymentGatewayConst;
use App\GoldTrading\Models\CapitalAllocation;
use App\GoldTrading\Models\ProfitDistribution;
use App\Models\Admin\Currency;
use App\Models\User;
use App\Models\UserWallet;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * An investor's cash book: every movement in and out of their account, oldest
 * first, with a running balance and a statement of what they can actually
 * withdraw today versus what is committed to open gold deals.
 *
 * Everything here is read from the transactions table the platform already
 * writes; nothing is stored separately, so the cash book cannot drift from the
 * money.
 */
class GoldStatementCommand extends Command
{
    protected $signature = 'gold:statement
                            {investor : username or email}
                            {--limit=100 : most recent movements to print}';

    protected $description = 'Print an investor\'s money-in / money-out cash book';

    /** Movements that always add to the spendable balance. */
    private const IN_BALANCE = [
        PaymentGatewayConst::TYPEADDMONEY,
        PaymentGatewayConst::TYPECAPITALRETURN,
        PaymentGatewayConst::TYPEBONUS,
        PaymentGatewayConst::TYPEREFERBONUS,
        PaymentGatewayConst::TYPECOMMISSION,
        CapitalAllocation::TRX_RELEASE,
    ];

    public function handle(): int
    {
        $user = User::where('username', $this->argument('investor'))
            ->orWhere('email', $this->argument('investor'))
            ->first();

        if (! $user) {
            $this->error('No user matches ' . $this->argument('investor'));

            return self::FAILURE;
        }

        $currency = Currency::where('default', true)->first();
        $wallet = UserWallet::where('user_id', $user->id)
            ->whereHas('currency', fn ($q) => $q->where('code', $currency?->code))
            ->first();

        $code = $currency?->code ?? 'USD';

        $transactions = DB::table('transactions')
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)->orWhere('receiver_id', $user->id);
            })
            ->orderBy('id')
            ->get();

        $runBalance = 0.0;
        $runProfit  = 0.0;
        $totalIn    = 0.0;
        $totalOut   = 0.0;
        $rows       = [];

        foreach ($transactions as $trx) {
            $details = json_decode($trx->details ?? 'null');
            $amount  = (float) $trx->request_amount;

            [$inBalance, $outBalance, $inProfit, $outProfit, $label] =
                $this->classify($trx, $details, $amount, $user->id, $runBalance);

            $runBalance += $inBalance - $outBalance;
            $runProfit  += $inProfit - $outProfit;

            $in  = $inBalance + $inProfit;
            $out = $outBalance + $outProfit;

            $totalIn  += $in;
            $totalOut += $out;

            $rows[] = [
                date('d M Y', strtotime($trx->created_at)),
                $label,
                $in > 0 ? number_format($in, 2) : '',
                $out > 0 ? number_format($out, 2) : '',
                number_format($runBalance, 2),
                number_format($runProfit, 2),
                $trx->trx_id,
            ];
        }

        $limit = max(1, (int) $this->option('limit'));
        $shown = array_slice($rows, -$limit);

        $this->newLine();
        $this->line('Cash book — ' . $user->username . ' (' . $user->email . ')   all figures in ' . $code);
        $this->newLine();

        if ($shown === []) {
            $this->warn('No movements recorded for this investor yet.');
        } else {
            if (count($rows) > count($shown)) {
                $this->line('… ' . (count($rows) - count($shown)) . ' earlier movements not shown');
            }
            $this->table(
                ['Date', 'Movement', 'Money In', 'Money Out', 'Balance', 'Profit', 'Reference'],
                $shown
            );
        }

        $committed = CapitalAllocation::committedFor($user->id);

        $openDeals = CapitalAllocation::with('lot')
            ->where('user_id', $user->id)
            ->where('status', CapitalAllocation::STATUS_ALLOCATED)
            ->where('locked_balance', true)
            ->get();

        $balance = (float) ($wallet->balance ?? 0);
        $profit  = (float) ($wallet->profit_balance ?? 0);

        $this->line('Total money in   : ' . number_format($totalIn, 2) . ' ' . $code);
        $this->line('Total money out  : ' . number_format($totalOut, 2) . ' ' . $code);
        $this->newLine();
        $this->line('Current balance  : ' . number_format($balance, 2) . ' ' . $code . '   (spendable)');
        $this->line('Profit balance   : ' . number_format($profit, 2) . ' ' . $code . '   (spendable)');
        $this->line('In open deals    : ' . number_format($committed, 2) . ' ' . $code . '   (working in gold, not withdrawable)');
        $this->line('Total position   : ' . number_format($balance + $profit + $committed, 2) . ' ' . $code);
        $this->newLine();
        $this->info('Can withdraw today: ' . number_format($balance + $profit, 2) . ' ' . $code);

        if ($openDeals->isNotEmpty()) {
            $this->newLine();
            $this->line('Open deals holding this investor\'s capital:');
            $this->table(
                ['Deal', 'Project', 'Committed', 'Since'],
                $openDeals->map(fn ($a) => [
                    $a->lot->lot_code ?? '-',
                    $a->lot->project_name ?? '-',
                    number_format($a->amount_usd, 2),
                    optional($a->allocated_at)->format('d M Y'),
                ])->all()
            );
        }

        if ($committed <= 0 && $balance + $profit > 0) {
            $this->newLine();
            $this->warn('None of this money is committed to a deal, so all of it is withdrawable through Money Out.');
        }

        return self::SUCCESS;
    }

    /**
     * Works out which way a transaction moved money, and out of which balance.
     *
     * @return array{0:float,1:float,2:float,3:float,4:string}
     *         in-balance, out-balance, in-profit, out-profit, label
     */
    private function classify(object $trx, mixed $details, float $amount, int $userId, float $runBalance): array
    {
        $type = $trx->type;

        if (in_array($type, self::IN_BALANCE, true)) {
            return [$amount, 0.0, 0.0, 0.0, $this->label($type)];
        }

        if ($type === ProfitDistribution::TRX_TYPE) {
            return [0.0, 0.0, $amount, 0.0, $this->label($type)];
        }

        if ($type === CapitalAllocation::TRX_LOCK) {
            $fromBalance = (float) ($details->from_balance_usd ?? $amount);
            $fromProfit  = (float) ($details->from_profit_usd ?? 0);

            return [0.0, $fromBalance, 0.0, $fromProfit, $this->label($type)];
        }

        if (in_array($type, [PaymentGatewayConst::TYPEWITHDRAW, PaymentGatewayConst::TYPEMONEYOUT], true)) {
            $fromProfit = ($details->wallet_type ?? 'c_balance') === 'p_balance';

            return $fromProfit
                ? [0.0, 0.0, 0.0, $amount, $this->label($type)]
                : [0.0, $amount, 0.0, 0.0, $this->label($type)];
        }

        if ($type === PaymentGatewayConst::TYPETRANSFERMONEY) {
            return (int) $trx->receiver_id === $userId && (int) $trx->user_id !== $userId
                ? [(float) $trx->receive_amount, 0.0, 0.0, 0.0, 'Transfer in']
                : [0.0, $amount, 0.0, 0.0, 'Transfer out'];
        }

        if ($type === PaymentGatewayConst::TYPEADDSUBTRACTBALANCE) {
            // The admin adjustment stores no direction, but available_balance is the
            // balance immediately after it, so the sign is recoverable.
            $added = (float) $trx->available_balance >= $runBalance;

            return $added
                ? [$amount, 0.0, 0.0, 0.0, 'Admin adjustment (add)']
                : [0.0, $amount, 0.0, 0.0, 'Admin adjustment (subtract)'];
        }

        // Anything else is treated as money leaving the balance, which is the safe
        // reading for orders and payments.
        return [0.0, $amount, 0.0, 0.0, $this->label($type)];
    }

    private function label(string $type): string
    {
        return match ($type) {
            PaymentGatewayConst::TYPEADDMONEY      => 'Deposit',
            PaymentGatewayConst::TYPEWITHDRAW,
            PaymentGatewayConst::TYPEMONEYOUT      => 'Money out',
            PaymentGatewayConst::TYPECAPITALRETURN => 'Capital return',
            PaymentGatewayConst::TYPEBONUS         => 'Bonus',
            PaymentGatewayConst::TYPEREFERBONUS    => 'Referral bonus',
            PaymentGatewayConst::TYPECOMMISSION    => 'Commission',
            ProfitDistribution::TRX_TYPE           => 'Gold deal profit',
            CapitalAllocation::TRX_LOCK            => 'Into gold deal',
            CapitalAllocation::TRX_RELEASE         => 'Capital back from deal',
            default                                => ucfirst(strtolower(str_replace('-', ' ', $type))),
        };
    }
}
