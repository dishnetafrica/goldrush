<?php
namespace App\Http\Controllers\User;
use App\Models\UserWallet;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Constants\GlobalConst;
use App\Models\InvestmentProfitLog;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Constants\PaymentGatewayConst;
use App\Models\User\UserHasInvestPlan;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $page_title = __("Dashboard");
        $breadcrumb = __("Dashboard");
        $auth_user = Auth::user();
        $wallet = UserWallet::where('user_id', Auth::id())->first();

        $add_money_amount = Transaction::auth()->where('type', PaymentGatewayConst::TYPEADDMONEY)->where('status', 1)->sum('request_amount');
        $send_money_amount = Transaction::auth()->where('type', PaymentGatewayConst::TYPETRANSFERMONEY)->where('status', 1)->sum('request_amount');
        $money_out_amount = Transaction::auth()->where('type', PaymentGatewayConst::TYPEWITHDRAW)->where('status', 1)->sum('request_amount');
        $transactions = Transaction::where(function ($query) {
            $query->where('user_type', GlobalConst::USER)
                  ->orWhere('receiver_type',GlobalConst::USER)
                  ->where('receiver_id', auth()->user()->id);
        })->orWhere('user_id', auth()->user()->id)
          ->orderByDesc('id')
          ->take(3)
          ->get();

        $total_transactions =  Transaction::auth()->where('status', 1)->sum('request_amount');

        $start = Carbon::now()->startOfMonth();
        $end = Carbon::now()->endOfMonth();

        $invest_amount_data = [];
        $profit_amount_data = [];
        $month_day = [];
        $send_money_data = [];

        while ($start->lessThanOrEqualTo($end)) {
            $start_date = $start->toDateString();

            $invest_amount = UserHasInvestPlan::auth()->whereDate('created_at', $start_date)->sum('invest_amount');
            $profit_amount = InvestmentProfitLog::auth()->whereDate('created_at', $start_date)->sum('profit_amount');
            $send_money = Transaction::auth()
                ->where('type', PaymentGatewayConst::TYPETRANSFERMONEY)
                ->where('status', 1)
                ->whereDate('created_at', $start_date)
                ->sum('request_amount');

            $invest_amount_data[] = $invest_amount;
            $profit_amount_data[] = $profit_amount;
            $send_money_data[] = $send_money;
            $month_day[] = $start_date;

            // Move to the next day
            $start->addDay();
        }

        // Chart one
         $chart_one_data = [
            'invest_data'  => $invest_amount_data,
            'profit_data'  => $profit_amount_data,

        ];
        // Chart two
        $chart_two_data = [
            'send_money_data'  => $send_money_data,
        ];

        $chartData =[
            'chart_one_data'   => $chart_one_data,
            'chart_two_data'   => $chart_two_data,
            'month_day'        => $month_day,
        ];

        return view('user.dashboard',compact("page_title","wallet","add_money_amount","transactions","breadcrumb","auth_user","send_money_amount","money_out_amount","total_transactions","chartData"));
    }

    public function logout(Request $request) {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('frontend.index');
    }
}
