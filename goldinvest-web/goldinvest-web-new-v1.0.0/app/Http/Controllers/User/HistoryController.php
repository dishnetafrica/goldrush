<?php

namespace App\Http\Controllers\User;

use App\Models\User\Order;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Constants\GlobalConst;
use App\Models\InvestmentProfitLog;
use App\Http\Controllers\Controller;
use App\Constants\PaymentGatewayConst;

class HistoryController extends Controller
{
    public function slugValue($slug) {
        $values =  [
            'add-money-log'      => PaymentGatewayConst::TYPEADDMONEY,
            'money-out-log'      => PaymentGatewayConst::TYPEWITHDRAW,
            'send-money-log'     => PaymentGatewayConst::TYPETRANSFERMONEY,
        ];

        if(!array_key_exists($slug,$values)) return abort(404);
        return $values[$slug];
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index($slug = null) {
        $breadcrumb = __("Transaction");
        if($slug != null){
            $user_id = auth()->user()->id;
            $transactions = Transaction::where('type', $this->slugValue($slug))
            ->where(function($q) use ($user_id) {
                $q->where('user_id', $user_id)
                    ->orWhere('receiver_id', $user_id);
            })
            ->orderByDesc('id')
            ->paginate(10);
            $page_title = ucwords(str_replace("-", " ", $slug));
        }else {
            $transactions = Transaction::where(function($q) {
                $q->where('user_type',GlobalConst::USER)->orWhere('receiver_type',GlobalConst::USER);
            })->where(function($q) {
                $q->where('user_id',auth()->user()->id)->orWhere('receiver_id',auth()->user()->id);
            })->orderByDesc('id')->paginate(10);
            $page_title = __("Transaction Log");
        }

        return view('user.sections.history.transaction',compact("page_title","transactions","slug","breadcrumb"));
    }
    public function order(){
        $breadcrumb    = __("Order log");
        $page_title    = __("Order log");
        $user          = auth()->user();
        $lang = selectedLang();
        $orders = Order::auth()->with('user')->with('gold')->where('status', 1)->latest('id')->paginate(4);

        return view('user.sections.history.order',compact(
            'breadcrumb',
            'page_title',
            'user',
            'orders',
            'lang'
        ));
    }
    public function profit(){
        $breadcrumb    = __("Profit Log");
        $page_title    = __("Profit Log");
        $user          = auth()->user();
        $lang = selectedLang();
        $profits = InvestmentProfitLog::auth()->has('invest')->orderByDesc("id")->paginate(7);


        return view('user.sections.history.profit-log',compact(
            'breadcrumb',
            'page_title',
            'user',
            'profits',
            'lang'
        ));
    }
}
