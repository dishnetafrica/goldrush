<?php

namespace App\Http\Controllers\User;

use Exception;
use App\Models\User\Order;
use App\Models\UserWallet;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use App\Constants\GlobalConst;
use Illuminate\Support\Carbon;
use App\Models\Admin\GoldStock;
use App\Models\Admin\UsefulLink;
use Illuminate\Support\Facades\DB;
use App\Models\Admin\BasicSettings;
use App\Constants\NotificationConst;
use App\Http\Controllers\Controller;
use App\Models\Admin\InvestmentPlan;
use App\Traits\User\RegisteredUsers;
use App\Constants\PaymentGatewayConst;
use App\Models\Admin\UserNotification;
use App\Models\User\UserHasInvestPlan;
use App\Notifications\orderNotification;
use App\Providers\Admin\CurrencyProvider;
use Illuminate\Support\Facades\Validator;
use App\Http\Helpers\InvestmentProfitHelper;
use App\Notifications\User\PlanNotification;
use Illuminate\Support\Facades\Notification;

class InvestmentController extends Controller
{
    use RegisteredUsers;

    public function goldStoreIndex()
    {
        $page_title = __("Buy Gold");
        $breadcrumb = __("Gold Store");
        $golds = GoldStock::where('status', 1)->latest('id')->paginate(6);
        $lang = selectedLang();
        return view('user.sections.investment.gold-store', compact("page_title", 'breadcrumb', 'golds', 'lang'));
    }

    public function checkout($slug)
    {
        $page_title = __("Checkout");
        $breadcrumb = __("Gold Store");
        $gold = GoldStock::where('status', 1)->where('slug', $slug)->first();
        $lang = selectedLang();

        return view('user.sections.investment.checkout', compact("page_title", 'breadcrumb', 'gold', 'lang'));
    }

    public function order(Request $request, $slug)
    {

        $validated = Validator::make($request->all(), [
            'qtybutton'     => "required|integer|gt:0",
            'payment_type'  => "required|in:" . GlobalConst::PAYMENT_TYPE_USER_WALLET . "," . GlobalConst::PAYMENT_TYPE_CASH_ON_DELIVERY,
            'country'       => "required|string|max:50",
            'phone_code'    => "required|string|max:20",
            'phone'         => "required|string|max:20",
            'state'         => "required|string|max:50",
            'city'          => "required|string|max:50",
            'zip_code'      => "required|string",
            'address'       => "nullable|string|max:250",
        ])->validate();

        $validated['mobile']        = remove_speacial_char($validated['phone']);
        $validated['mobile_code']   = remove_speacial_char($validated['phone_code']);
        $complete_phone             = $validated['mobile_code'] . $validated['mobile'];
        $validated['full_mobile']   = $complete_phone;
        $validated                  = Arr::except($validated, ['agree', 'phone_code', 'phone']);
        $validated['address']       = [
            'country'   => $validated['country'],
            'state'     => $validated['state'] ?? "",
            'city'      => $validated['city'] ?? "",
            'zip_code'       => $validated['zip_code'] ?? "",
            'address'   => $validated['address'] ?? "",
        ];

        $validated['quantity'] = $request->qtybutton;
        $validated['payment_type'] = $request->payment_type;
        $gold = GoldStock::where('slug', $slug)->first();
        $total_amount =  $validated['quantity'] *  $gold->price + $gold->charge;
        $validated['total_amount'] = $total_amount;
        $user_wallet = UserWallet::auth()->first();
        $basic_settings = BasicSettings::first();
        if ($validated['payment_type'] == 'USER-WALLET') {
            if ($total_amount > $user_wallet->balance) return back()->with(['error' => [__('Your wallet balance is insufficient')]]);
            $user_wallet->balance -= $total_amount;
            $user_wallet->save();
            $validated['payment_status'] = true;
        } else {
            $validated['payment_status'] = false;
        }
        $validated['status'] = true;
        $validated['order_status'] = 1;
        $validated['gold_stock_id'] = $gold->id;
        $validated['user_id'] = $user_wallet->user->id;
        $email = $user_wallet->user->email;
        $lang = selectedLang();
        $mail_data = ([
            'item'  => $gold->title->language->$lang->title,
            'quantity' => $validated['quantity'],
            'total_amount' => $validated['total_amount'],
            'payment_type' => $validated['payment_type'],
            'order_status' => $validated['order_status'],
            'mobile' => $validated['full_mobile'],
            'address' => $validated['address']['address'],
            'city' => $validated['address']['city'],
            'zip_code' => $validated['address']['zip_code'],
            'state' => $validated['address']['state'],
            'country' => $validated['address']['country'],
            'price' => $gold->price,
            'charge' => $gold->charge,

        ]);

        try {
            Order::create($validated);
        } catch (Exception $e) {
            return back()->with(['error' => [__('Something went wrong. Please try again')]]);
        }
        try {

            if ($basic_settings->email_notification == true) {
                Notification::route("mail", $email)->notify(new orderNotification($mail_data));
            }

            //USER notification
            $notification_content = [
                'title'         => __("Gold Order"),
                'message'       => __("Your ") . $gold->title->language->$lang->title . __(" order is successful"),
                'image'         => files_asset_path('profile-default'),
            ];

            UserNotification::create([
                'type'      => PaymentGatewayConst::TYPEGOLDORDER,
                'user_id'  => $user_wallet->user->id,
                'message'   => $notification_content,
            ]);
        } catch (Exception $e) {
            //throw $th;
        }
        return redirect()->route('user.investment.gold.store')->with(['success' => [__('Your order is successful!')]]);
    }

    public function investmentPlanIndex()
    {
        $page_title = __("Buy Plan");
        $breadcrumb = __("Gold Invest");
        $plans = InvestmentPlan::where('status', 1)->latest('id')->paginate(6);
        $lang = selectedLang();
        $useful_link = UsefulLink::where("type", GlobalConst::USEFUL_LINK_PRIVACY_POLICY)->first();

        return view('user.sections.investment.investment-plan', compact("page_title", 'breadcrumb', 'plans', 'lang', 'useful_link'));
    }
    public function purchase(Request $request, InvestmentPlan $invest_plan)
    {
        $validator = Validator::make($request->all(), [
            'invest_amount'     => "required|numeric",
            'agree'             => "required|string|in:on",
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator->errors())->withInput()->with('modal', 'modal fade');
        }
        $lang = selectedLang();
        $validated = $validator->validate();
        $basic_settings = BasicSettings::first();
        if ($invest_plan->status != GlobalConst::ACTIVE) return back()->with(['error' => __("Oops! This plan is no longer available")]);

        $user = auth()->user();

        $default_currency = CurrencyProvider::default();
        $user_wallet = $user->wallets->filter(function ($item) use ($default_currency) {
            if ($item->currency->code == $default_currency->code) return true;
            return false;
        })->first();

        if (!$user_wallet) return back()->with(['error' => [__('Oops! Wallet not found!')]]);

        if ($validated['invest_amount'] < $invest_plan->min_invest_requirement || $validated['invest_amount'] > $invest_plan->maximum_investment) {
            return back()->with(['error' => [__('Your can invest minimum ') . get_amount($invest_plan->min_invest_requirement, $user_wallet->currency->code, 4) . __(" to maximum ") . get_amount($invest_plan->maximum_investment, $user_wallet->currency->code, 4)]]);
        }

        if ($user_wallet->balance < $validated['invest_amount']) return back()->with(['error' => [__('Your wallet balance is insufficient')]]);
        $email = $user_wallet->user->email;
        DB::beginTransaction();
        try {
            DB::table($user_wallet->getTable())->where('id', $user_wallet->id)->decrement('balance', $validated['invest_amount']);

            $inserted_id = DB::table('user_has_invest_plans')->insertGetId([
                'user_id'           => $user->id,
                'invest_plan_id'    => $invest_plan->id,
                'exp_at'            => Carbon::now()->addDays($invest_plan->plan_duration),
                'invest_amount'     => $validated['invest_amount'],
                'created_at'        => now(),
            ]);
            $mail_data = ([
                'user'              => $user->fullname,
                'plan_name'         => $invest_plan->data->language->$lang->name,
                'invest_amount'     => $validated['invest_amount'],
                'currency'          => $user_wallet->currency->code,
                'created_at'        => now(),
                'exp_at'            => Carbon::now()->addDays($invest_plan->duration)
            ]);
            $plan = UserHasInvestPlan::find($inserted_id);

            (new InvestmentProfitHelper($plan))->execute();

            $this->referUserLevelUpInspection($user);

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            return back()->with(['error' => [__('Oops! Something went wrong! Please try again')]]);
        }
        try {
            $notification_content = [
                'title'     => __("Plan Purchase"),
                'message'   => __("You have invested ") . get_amount($validated['invest_amount'], $default_currency->code, 2) . __(" to purchase ") . $invest_plan->data->language->$lang->name . " plan.",
                'image'     => files_asset_path('profile-default')
            ];
            $this->createUserNotification($user,$notification_content);
            if ($basic_settings->email_notification == true){
                Notification::route("mail", $email)->notify(new PlanNotification($mail_data));
                }
        } catch (Exception $e) {
        }
        return back()->with(['success' => [__('New Investment Plan Purchase Successfully!')]]);
    }

    public function investIndex()
    {
        $page_title = __("My Invest");
        $breadcrumb = __("Investment");
        $invests = UserHasInvestPlan::auth()->with('investPlan')->with('user.wallets')->latest('id')->paginate(3);
        $lang = selectedLang();
        return view('user.sections.investment.my-invest', compact("page_title", 'breadcrumb', 'invests', 'lang'));
    }

    /**
     * method to create user notification
     */
    public function createUserNotification($user, $message){
        UserNotification::create([
            'type'      => NotificationConst::PLAN_PURCHASE,
            'user_id'   => $user->id,
            'message'   => $message
        ]);
    }
}
