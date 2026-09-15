<?php

namespace App\Http\Controllers\Admin;

use Exception;
use Carbon\Carbon;
use App\Models\User;
use App\Models\User\Order;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Http\Helpers\Response;
use App\Models\UserSupportTicket;
use App\Models\InvestmentProfitLog;
use App\Constants\NotificationConst;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Constants\PaymentGatewayConst;
use App\Models\User\UserHasInvestPlan;
use App\Models\Admin\AdminNotification;
use App\Providers\Admin\BasicSettingsProvider;
use Pusher\PushNotifications\PushNotifications;

class DashboardController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $page_title = __("Dashboard");
        $add_money_request = Transaction::where('type', PaymentGatewayConst::TYPEADDMONEY)->count();
        $add_money_success_request = Transaction::where('type', PaymentGatewayConst::TYPEADDMONEY)->where('status', 1)->count();
        $add_money_pending_request = Transaction::where('type', PaymentGatewayConst::TYPEADDMONEY)->where('status', 2)->count();
        $add_money_amount = Transaction::where('type', PaymentGatewayConst::TYPEADDMONEY)->sum('request_amount');
        $add_money_pending = Transaction::where('type', PaymentGatewayConst::TYPEADDMONEY)->where('status', 2)->sum('request_amount');
        $money_out_request = Transaction::where('type', PaymentGatewayConst::TYPEWITHDRAW)->count();
        $money_out_success_request = Transaction::where('type', PaymentGatewayConst::TYPEWITHDRAW)->where('status', 1)->count();
        $money_out_pending_request = Transaction::where('type', PaymentGatewayConst::TYPEWITHDRAW)->where('status', 2)->count();
        $money_out_amount = Transaction::where('type', PaymentGatewayConst::TYPEWITHDRAW)->sum('request_amount');
        $money_out_pending = Transaction::where('type', PaymentGatewayConst::TYPEWITHDRAW)->where('status', 2)->sum('request_amount');
        $active_tickets = UserSupportTicket::where('status', 2)->count();
        $solved_tickets = UserSupportTicket::where('status', 1)->count();
        $pending_tickets = UserSupportTicket::where('status', 3)->count();
        $active_users = User::where('status', 1)->count();
        $banned_users = User::where('status', 0)->count();

        $unverified_users = User::where('email_verified', 0)->count();

        $users = User::count();
        $total_invest = UserHasInvestPlan::count();
        $total_invest_running = UserHasInvestPlan::where('status',2)->count();
        $total_invest_completed = UserHasInvestPlan::where('status',1)->count();
        $total_profit = InvestmentProfitLog::sum('profit_amount');

        $total_order = Order::where('status',1)->count();
        $total_order_ongoing = Order::where('status',1)->where('order_status',2)->count();
        $total_order_delivered = Order::where('status',1)->where('order_status',3)->count();

        $transactions = Transaction::with(
            'user:id,email,username,full_mobile,image,firstname,lastname',
            'payment_gateway:id,name',
        )->where('type', PaymentGatewayConst::TYPEADDMONEY)->limit(3)->get();

        $last_month_start = Carbon::now()->subMonth()->startOfMonth()->toDateString();
        $last_month_end = Carbon::now()->subMonth()->endOfMonth()->toDateString();
        $this_month_start = Carbon::now()->startOfMonth()->toDateString();
        $this_month_end = Carbon::now()->toDateString();

        $this_week = Carbon::now()->subWeek()->toDateString();
        $this_month = Carbon::now()->subMonth()->toDateString();
        $this_year = Carbon::now()->subYear()->toDateString();

        // Queries for new users
        $today_new_user = User::where('status', 1)->whereDate('created_at', $this_month_end)->count();
        $this_week_new_user = User::where('status', 1)->whereDate('created_at', '>=', $this_week)->count();
        $this_month_new_user = User::where('status', 1)->whereDate('created_at', '>=', $this_month)->count();
        $this_year_new_user = User::where('status', 1)->whereDate('created_at', '>=', $this_year)->count();

        // Monthly Add Money loop - Start and end of the current month
        $start = Carbon::now()->startOfMonth();
        $end = Carbon::now()->endOfMonth();

        $pending_data = [];
        $success_data = [];
        $canceled_data = [];
        $hold_data = [];
        $pending_withdraw_data = [];
        $success_withdraw_data = [];
        $canceled_withdraw_data = [];
        $hold_withdraw_data = [];
        $invest_amount_data = [];
        $profit_amount_data = [];
        $month_day = [];

        // Loop through each day of the current month
        while ($start->lessThanOrEqualTo($end)) {
            $start_date = $start->toDateString(); // Format the date as 'Y-m-d'

            // Monthly add money queries
            $pending = Transaction::where('type', PaymentGatewayConst::TYPEADDMONEY)
                                  ->whereDate('created_at', $start_date)
                                  ->where('status', 2)
                                  ->count();
            $success = Transaction::where('type', PaymentGatewayConst::TYPEADDMONEY)
                                  ->whereDate('created_at', $start_date)
                                  ->where('status', 1)
                                  ->count();
            $canceled = Transaction::where('type', PaymentGatewayConst::TYPEADDMONEY)
                                   ->whereDate('created_at', $start_date)
                                   ->where('status', 4)
                                   ->count();
            $hold = Transaction::where('type', PaymentGatewayConst::TYPEADDMONEY)
                               ->whereDate('created_at', $start_date)
                               ->where('status', 3)
                               ->count();

            // Monthly withdraw queries
            $pending_withdraw = Transaction::where('type', PaymentGatewayConst::TYPEWITHDRAW)
                                           ->whereDate('created_at', $start_date)
                                           ->where('status', 2)
                                           ->count();
            $success_withdraw = Transaction::where('type', PaymentGatewayConst::TYPEWITHDRAW)
                                           ->whereDate('created_at', $start_date)
                                           ->where('status', 1)
                                           ->count();
            $canceled_withdraw = Transaction::where('type', PaymentGatewayConst::TYPEWITHDRAW)
                                            ->whereDate('created_at', $start_date)
                                            ->where('status', 4)
                                            ->count();
            $hold_withdraw = Transaction::where('type', PaymentGatewayConst::TYPEWITHDRAW)
                                        ->whereDate('created_at', $start_date)
                                        ->where('status', 3)
                                        ->count();

            // Monthly investment and profit queries
            $invest_amount = UserHasInvestPlan::whereDate('created_at', $start_date)->sum('invest_amount');
            $profit_amount = Transaction::where('status', 1)->whereDate('created_at', $start_date)->sum('total_charge');

            // Append data to arrays
            $pending_data[] = $pending;
            $success_data[] = $success;
            $canceled_data[] = $canceled;
            $hold_data[] = $hold;

            $pending_withdraw_data[] = $pending_withdraw;
            $success_withdraw_data[] = $success_withdraw;
            $canceled_withdraw_data[] = $canceled_withdraw;
            $hold_withdraw_data[] = $hold_withdraw;

            $invest_amount_data[] = $invest_amount;
            $profit_amount_data[] = $profit_amount;

            $month_day[] = $start_date;

            // Move to the next day
            $start->addDay();
        }
           // Chart one
        $chart_one_data = [
            'pending_data'  => $pending_data,
            'success_data'  => $success_data,
            'canceled_data' => $canceled_data,
            'hold_data'     => $hold_data,
        ];
            // Chart two
        $chart_two_data = [
            'invest_data'  => $invest_amount_data,
            'profit_data'  => $profit_amount_data,
        ];
          // Chart three
        $chart_three_data = [
            'pending_data'  => $pending_withdraw_data,
            'success_data'  => $success_withdraw_data,
            'canceled_data' => $canceled_withdraw_data,
            'hold_data'     => $hold_withdraw_data,
        ];

        // Chart four | User analysis
        $chart_four_data = [$active_users, $banned_users,$unverified_users,$users];

        // Chart five | Profit Growth
        $chart_five_data = [round($today_new_user), round($this_week_new_user),round($this_month_new_user),round($this_year_new_user)];

        $chartData =[
            'chart_one_data'   => $chart_one_data,
            'chart_two_data'   => $chart_two_data,
            'chart_three_data'   => $chart_three_data,
            'chart_four_data'   => $chart_four_data,
            'chart_five_data'   => $chart_five_data,
            'month_day'        => $month_day,
        ];
        return view('admin.sections.dashboard.index',compact(
            'page_title',
            'add_money_request',
            'add_money_amount',
            'money_out_request',
            'money_out_amount',
            'active_tickets',
            'solved_tickets',
            'pending_tickets',
            'active_users',
            'banned_users',
            'unverified_users',
            'users',
            'add_money_pending',
            'money_out_pending',
            'add_money_success_request',
            'add_money_pending_request',
            'money_out_success_request',
            'money_out_pending_request',
            'total_invest',
            'total_invest_running',
            'total_invest_completed',
            'total_profit',
            'chartData',
            'total_order',
            'total_order_ongoing',
            'total_order_delivered',
            'transactions'
        ));
    }


    /**
     * Logout Admin From Dashboard
     * @return view
     */
    public function logout(Request $request) {

        $push_notification_setting = BasicSettingsProvider::get()->push_notification_config;

        if($push_notification_setting) {
            $method = $push_notification_setting->method ?? false;

            if($method == "pusher") {
                $instant_id     = $push_notification_setting->instance_id ?? false;
                $primary_key    = $push_notification_setting->primary_key ?? false;

                if($instant_id && $primary_key) {
                    $pusher_instance = new PushNotifications([
                        "instanceId"    => $instant_id,
                        "secretKey"     => $primary_key,
                    ]);

                    $pusher_instance->deleteUser("".Auth::user()->id."");
                }
            }

        }

        $admin = auth()->user();
        try{
            $admin->update([
                'last_logged_out'   => now(),
                'login_status'      => false,
            ]);
        }catch(Exception $e) {
            // Handle Error
        }

        Auth::guard()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }


    /**
     * Function for clear admin notification
     */
    public function notificationsClear() {
        $admin = auth()->user();

        if(!$admin) {
            return false;
        }

        try{
            $admin->update([
                'notification_clear_at'     => now(),
            ]);
        }catch(Exception $e) {
            $error = ['error' => [__('Something went wrong! Please try again.')]];
            return Response::error($error,null,404);
        }

        $success = ['success' => [__('Notifications clear successfully!')]];
        return Response::success($success,null,200);
    }
}
