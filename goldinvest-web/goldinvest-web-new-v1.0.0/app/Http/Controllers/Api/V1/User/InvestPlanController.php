<?php

namespace App\Http\Controllers\Api\V1\User;


use Exception;
use Illuminate\Http\Request;
use App\Constants\GlobalConst;
use App\Http\Helpers\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\Admin\BasicSettings;
use App\Constants\NotificationConst;
use App\Http\Controllers\Controller;
use App\Models\Admin\InvestmentPlan;
use App\Traits\User\RegisteredUsers;
use App\Models\Admin\UserNotification;
use App\Models\User\UserHasInvestPlan;
use App\Providers\Admin\CurrencyProvider;
use Illuminate\Support\Facades\Validator;
use App\Http\Helpers\InvestmentProfitHelper;
use App\Notifications\User\PlanNotification;
use Illuminate\Support\Facades\Notification;

class InvestPlanController extends Controller
{
    use RegisteredUsers;

    public function getPlans(Request $request)
    {
        $lang = $request->lang;
        $default = 'en';
        $plan = InvestmentPlan::where('status', 1)->latest('id')->get();
        if (isset($plan)) {
            $plans = [];
            foreach ($plan ?? [] as  $value) {

                $name = isset($value->data->language->$lang) ? $value->data->language->$lang->name : $value->data->language->$default->name;
                $title = isset($value->data->language->$lang) ? $value->data->language->$lang->title : $value->data->language->$default->title;

                $plans[] = [
                    'id' => $value->id,
                    'name' => $name,
                    'title' => $title,
                    'slug'  => $value->slug,
                    'plan_duration' => $value->plan_duration,
                    'profit_return_type' => $value->profit_return_type,
                    'minimum_investment' => $value->minimum_investment,
                    'minimum_investment_offer' => $value->minimum_investment_offer,
                    'maximum_investment' => $value->maximum_investment,
                    'profit'    => $value->profit,
                    'profit_percentage' => $value->profit_percentage,
                    'image' => $value->image,
                ];
            }
        } else {
            $plans = [];
        }
        $plan_data = [
            'base_url' => url('/'),
            'image_path' => files_asset_path_basename("site-section"),
            'plans' => $plans,
        ];
        return Response::success([__('User Plan data fetched successfully!')],  $plan_data, 200);
    }
    public function purchase(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'invest_amount'     => "required|numeric",
            'slug'              => "required|string|exists:investment_plans,slug"
        ]);

        if ($validator->fails()) {
            return Response::error($validator->errors()->all(), []);
        }

        $validated = $validator->validate();
        $invest_plan = InvestmentPlan::where('slug', $validated['slug'])->first();

        if ($invest_plan->status != GlobalConst::ACTIVE) return Response::error([__('Oops! This plan is no longer available')], [], 400);

        $user = auth()->user();

        $default_currency = CurrencyProvider::default();
        $user_wallet = $user->wallets->filter(function ($item) use ($default_currency) {
            if ($item->currency->code == $default_currency->code) return true;
            return false;
        })->first();

        if (!$user_wallet) return Response::error([__('Oops! Wallet not found!')], [], 400);

        if ($validated['invest_amount'] < $invest_plan->min_invest_requirement || $validated['invest_amount'] > $invest_plan->maximum_investment) {
            return Response::error([__('You can invest minimum ' . get_amount($invest_plan->min_invest_requirement, $user_wallet->currency->code, 4) .__(" to maximum ")  . get_amount($invest_plan->maximum_investment, $user_wallet->currency->code, 4))], [], 400);
        }
        $basic_settings = BasicSettings::first();
        if ($user_wallet->balance < $validated['invest_amount']) return Response::error([__('Your wallet balance is insufficient')], [], 400);
        $lang = $request->lang;
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
            return Response::error([__('Oops! Something went wrong! Please try again')], [], 400);
        }
        try {
            $notification_content = [
                'title'     => __("Plan Purchase"),
                'message'   => __("You have invested ") . get_amount($validated['invest_amount'], $default_currency->code, 2) . __(" to purchase ") . $invest_plan->data->language->$lang->name . __(" plan."),
                'image'     => files_asset_path('profile-default')
            ];
            $this->createUserNotification($user, $notification_content);
            if ($basic_settings->email_notification == true) {
                Notification::route("mail", $email)->notify(new PlanNotification($mail_data));
            }
        } catch (Exception $e) {
        }
        return Response::success([__('New Investment Plan Purchase Successfully!')], [], 200);
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
    public function myInvestments(Request $request)
    {
        $lang = $request->lang;
        $default = 'en';
        $invests = UserHasInvestPlan::auth()->with('investPlan')->with('user.wallets')->latest('id')->get();

        if (isset($invests)) {
            $invest = [];
            foreach ($invests ?? [] as  $value) {
                $name = isset($value->investPlan->data->language->$lang) ? $value->investPlan->data->language->$lang->name : $value->investPlan->data->language->$default->name;
                $title = isset($value->investPlan->data->language->$lang) ? $value->investPlan->data->language->$lang->title : $value->investPlan->data->language->$default->title;

                $invest[] = [
                    'user_id' => $value->user_id,
                    'invest_plan_id' => $value->invest_plan_id,
                    'invest_amount' => $value->invest_amount,
                    'available_balance' => authWalletBalance(),
                    'exp_at' => $value->exp_at,
                    'created_at' => $value->created_at,
                    'status' => $value->status,
                    'invest_plan' => [
                        'id' => $value->investPlan->id,
                        'name' => $name,
                        'title' => $title,
                        'slug'  => $value->investPlan->slug,
                        'plan_duration' => $value->investPlan->plan_duration,
                        'profit'    => $value->investPlan->profit,
                        'profit_percentage' => $value->investPlan->profit_percentage,
                        'profit_return_type' => $value->investPlan->profit_return_type
                    ],
                ];
            }
        } else {
            $invest = [];
        }
        $instructions = [
            'status'      => "1: Complete, 2: Running, 3: Cancel",
        ];
        $invest_data = [
            'instructions' => $instructions,
            'invest' => $invest,
        ];
        return Response::success([__('User Invest data fetched successfully!')],  $invest_data, 200);
    }
}
