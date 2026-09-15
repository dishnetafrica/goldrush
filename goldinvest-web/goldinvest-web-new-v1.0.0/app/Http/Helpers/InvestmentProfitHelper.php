<?php

namespace App\Http\Helpers;

use App\Models\UserWallet;
use App\Constants\GlobalConst;
use Illuminate\Support\Carbon;
use App\Models\User\UserHasInvestPlan;
use App\Providers\Admin\CurrencyProvider;
use App\Jobs\User\InvestmentProfitDistribute;

class InvestmentProfitHelper
{


    /**
     * Store plan details - eloquent model
     */
    public $plan;

    /**
     * Store invest details - eloquent model
     */
    public $investment;

    /**
     * Set some default when loaded this class
     */
    public function __construct(UserHasInvestPlan $invest_record)
    {
        $this->plan = $invest_record->investPlan;
        $this->investment = $invest_record;
    }

    /**
     * Get Plan Profit Return Type From Eloquent Model
     * @return string
     */
    public function profitReturnType()
    {
        return $this->plan->profit_return_type;
    }

    /**
     * Start execution investment profit related arguments
     */
    public function execute()
    {
        return $this->handle();
    }

    /**
     * Resolve one time / daily basis profit
     * @return array
     */
    public function resolver()
    {
        if ($this->profitReturnType() == GlobalConst::INVEST_PROFIT_ONE_TIME) return $this->oneTimeProfit();
        return $this->dailyBasisProfit();
    }

    /**
     * Get Daily Basis Profit List
     * @return array
    */
    public function dailyBasisProfit()
    {
        $invest_amount = $this->investment->invest_amount;
        $fixed_profit = $this->investment->investPlan->profit;
        $percent_profit = ($invest_amount / 100) * $this->investment->investPlan->profit_percentage;
        $total_profit = $fixed_profit + $percent_profit;
        $purchase_time = $this->investment->created_at;
        $profit_per_day = ($total_profit / $this->plan->plan_duration);

        $profit_iteration = [];

        for ($i = 1; $i <= $this->plan->plan_duration; $i++) {

            $profit_execute_at = now()->addDays($i);

            $profit_iteration[] = [
                'user_id'           => auth()->user()->id,
                'invest_amount'     => $invest_amount,
                'fixed_profit'      => $fixed_profit,
                'percent_profit'    => $percent_profit,
                'total_profit'      => $profit_per_day,
                'purchase_at'       => $purchase_time,
                'profit_execute_at' => $profit_execute_at,
                'profit_wallet'     => $this->profitWallet(),
                'invest_id'         => $this->investment->id,
                'remark'            => ucwords(strtolower(remove_special_char(GlobalConst::INVEST_PROFIT_DAILY_BASIS, " "))),
                'user'              => auth()->user(),
                'investment'        => $this->investment,
                'action_type'       => GlobalConst::PROFIT,
            ];
        }


        $profit_iteration[] = $this->returnInvestment($profit_execute_at);
        return $profit_iteration;
    }

    /**
     * Return user investment after complete all profit cycle
     * @return array
     */
    public function returnInvestment($profit_execute_at)
    {
        $invest_amount = $this->investment->invest_amount;
        $fixed_profit = $this->investment->investPlan->profit;
        $percent_profit = ($invest_amount / 100) * $this->investment->investPlan->profit_percentage;
        $purchase_time = $this->investment->created_at;

        return [
            'user_id'           => auth()->user()->id,
            'invest_amount'     => $invest_amount,
            'fixed_profit'      => $fixed_profit,
            'percent_profit'    => $percent_profit,
            'total_profit'      => $invest_amount,
            'purchase_at'       => $purchase_time,
            'profit_execute_at' => $profit_execute_at,
            'profit_wallet'     => $this->profitWallet(),
            'invest_id'         => $this->investment->id,
            'remark'            => "Invest Amount",
            'user'              => auth()->user(),
            'investment'        => $this->investment,
            'action_type'       => GlobalConst::INVESTMENT,
        ];
    }

    /**
     * set user profit wallet
     */
    public function profitWallet($wallet = null)
    {
        if (empty($wallet)) {
            $default_currency = CurrencyProvider::default();
            $wallet = UserWallet::auth()->whereHas('currency', function ($q) use ($default_currency) {
                $q->where('code', $default_currency->code)->active();
            })->first();
        }
        return $wallet;
    }

    /**
     * Get One Time Profit List
     * @return array
     */
    public function oneTimeProfit()
    {
        $invest_amount = $this->investment->invest_amount;
        $fixed_profit = $this->investment->investPlan->profit;
        $percent_profit = ($invest_amount / 100) * $this->investment->investPlan->profit_percentage;
        $total_profit = $fixed_profit + $percent_profit;
        $purchase_time = $this->investment->created_at;
        $profit_execute_at = now()->addDays($this->investment->investPlan->plan_duration) < now() ? now() : now()->addDays($this->investment->investPlan->plan_duration);

        $profit_iteration[] = [
            'user_id'           => auth()->user()->id,
            'invest_amount'     => $invest_amount,
            'fixed_profit'      => $fixed_profit,
            'percent_profit'    => $percent_profit,
            'total_profit'      => $total_profit,
            'purchase_at'       => $purchase_time,
            'profit_execute_at' => $profit_execute_at,
            'profit_wallet'     => $this->profitWallet(),
            'invest_id'         => $this->investment->id,
            'user'              => auth()->user(),
            'investment'        => $this->investment,
            'action_type'       => GlobalConst::PROFIT,
        ];

        $profit_iteration[] = $this->returnInvestment($profit_execute_at);
        return $profit_iteration;
    }

    /**
     * Handle investment profit
     */
    public function handle()
    {
        foreach ($this->resolver() as $item) {
            InvestmentProfitDistribute::dispatch($item)->delay(Carbon::parse($item['profit_execute_at'])->diffInSeconds($item['purchase_at']));
            // InvestmentProfitDistribute::dispatch($item)->delay(10);
        };
    }
}
