<?php

namespace App\Http\Controllers\Api\V1\User;

use Carbon\CarbonPeriod;
use App\Models\UserWallet;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Constants\GlobalConst;
use App\Http\Helpers\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\InvestmentProfitLog;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Constants\PaymentGatewayConst;
use App\Models\Admin\UserNotification;
use App\Models\User\UserHasInvestPlan;
use App\Providers\Admin\CurrencyProvider;

class DashboardController extends Controller
{
    public function dashboard()
    {
        $default_currency = CurrencyProvider::default();

        // User Wallets
        $user_wallets = UserWallet::auth()->whereHas('currency', function ($q) use ($default_currency) {
            $q->where('code', $default_currency->code);
        })->select('id', 'user_id', 'currency_id', 'balance', 'status', 'created_at')->with(['currency' => function ($q) {
            $q->select('id', 'code');
        }])->get();

        // Transaction logs
        $transactions = Transaction::auth()->latest()->take(10)->get();
        $transactions->makeHidden([
            'id',
            'user_type',
            'user_id',
            'wallet_id',
            'payment_gateway_currency_id',
            'request_amount',
            'exchange_rate',
            'percent_charge',
            'fixed_charge',
            'total_charge',
            'total_payable',
            'receiver_type',
            'receiver_id',
            'available_balance',
            'payment_currency',
            'input_values',
            'details',
            'reject_reason',
            'remark',
            'stringStatus',
            'callback_ref',
            'updated_at',
        ]);

        // Chart Data
        $monthly_day_list = CarbonPeriod::between(now()->startOfDay()->subDays(30), today()->endOfDay())->toArray();
        $define_day_value = array_fill_keys(array_values($monthly_day_list), "0.00");

        // User Information
        $user_info = auth()->user()->only([
            'id',
            'firstname',
            'lastname',
            'fullname',
            'username',
            'email',
            'image',
            'mobile_code',
            'mobile',
            'full_mobile',
            'email_verified',
            'kyc_verified',
            'two_factor_verified',
            'two_factor_status',
            'two_factor_secret',
        ]);

        $profile_image_paths = [
            'base_url'          => url("/"),
            'path_location'     => files_asset_path_basename("user-profile"),
            'default_image'     => files_asset_path_basename("profile-default"),
        ];
        $invest_amount = UserHasInvestPlan::auth()->sum('invest_amount');
        $profit_amount = InvestmentProfitLog::auth()->sum('profit_amount');
        // Chart Data
        $add_money_record = Transaction::where('type', PaymentGatewayConst::TYPEADDMONEY)
            ->whereBetween('created_at', [now()->subDays(30), today()->endOfDay()])
            ->where('user_type', GlobalConst::USER)
            ->where('user_id', auth()->user()->id)
            ->select([
                DB::raw('DATE(created_at) as date'),
                DB::raw("(sum(receive_amount)) as total_amount"),
            ])
            ->groupBy('date')
            ->pluck("total_amount", "date")
            ->toArray();


        $money_out_record = Transaction::where('type', PaymentGatewayConst::TYPEWITHDRAW)
            ->whereBetween('created_at', [now()->subDays(30), today()->endOfDay()])
            ->where('user_type', GlobalConst::USER)
            ->where('user_id', auth()->user()->id)
            ->select([
                DB::raw('DATE(created_at) as date'),
                DB::raw("(sum(request_amount)) as total_amount"),
            ])
            ->groupBy('date')
            ->pluck("total_amount", "date")
            ->toArray();

        $add_money_statistics = [];
        $money_out_statistics = [];
        foreach ($define_day_value as $timestamp => $value) {

            $add_money_statistics[$timestamp]['timestamp']  = $timestamp;
            $add_money_statistics[$timestamp]['value']      = $value;

            $money_out_statistics[$timestamp]['timestamp']  = $timestamp;
            $money_out_statistics[$timestamp]['value']      = $value;

            // add money record
            foreach ($add_money_record as $date => $amount) {
                if (Carbon::parse($timestamp)->toDateString() == Carbon::parse($date)->toDateString()) {
                    $add_money_statistics[$timestamp]['value']  = (string) $amount;
                }
            }

            // money out record
            foreach ($money_out_record as $date => $amount) {
                if (Carbon::parse($timestamp)->toDateString() == Carbon::parse($date)->toDateString()) {
                    $money_out_statistics[$timestamp]['value']  = (string) $amount;
                }
            }
        }

        return Response::success([__('User dashboard data fetch successfully!')], [
            'instructions'  => [
                'transaction_types' => [
                    PaymentGatewayConst::TYPEADDMONEY,
                    PaymentGatewayConst::TYPETRANSFERMONEY,
                    PaymentGatewayConst::TYPEWITHDRAW,
                ],
                'recent_transactions'   => [
                    'status'        => '1: Success, 2: Pending, 3: Hold, 4: Rejected',
                ],
                'user_info'         => [
                    'kyc_verified'  => "0: Default, 1: Approved, 2: Pending, 3:Rejected",
                ]
            ],

            'user_info'     => $user_info,
            'wallets'       => $user_wallets,
            'invest_amount' => $invest_amount,
            'profit_amount' => $profit_amount,
            'recent_transactions'   => $transactions,
            'chart_data'        => [
                'add_money_statistics'      => array_values($add_money_statistics),
                'money_out_statistics'      => array_values($money_out_statistics),
            ],
            'profile_image_paths'   => $profile_image_paths,
        ]);
    }

    public function notifications()
    {
        $user = Auth::user();
        $notifications  = UserNotification::where('user_id', $user->id)->latest()->take(10)->get();
        return Response::success([__('User Notification data fetched successfully!')], ['notifications' => $notifications], 200);
    }
}
