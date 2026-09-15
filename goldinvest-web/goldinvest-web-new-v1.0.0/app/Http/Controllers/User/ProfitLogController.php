<?php

namespace App\Http\Controllers\User;

use Illuminate\Http\Request;
use App\Http\Helpers\Response;
use App\Models\InvestmentProfitLog;
use App\Http\Controllers\Controller;

class ProfitLogController extends Controller
{
    public function profitLog(Request $request){
        $lang = $request->lang;
        $default = 'en';
        $user    = auth()->user();
        $profits = InvestmentProfitLog::auth()->has('invest')->orderByDesc("id")->paginate(7);

        $data = $profits->map(function ($profit) use ($lang, $default) {
            $title = isset($profit->invest->investPlan->data->language->$lang)
                ? $profit->invest->investPlan->data->language->$lang->title
                : $profit->invest->investPlan->data->language->$default->title;

            return [
                'title' => $title,
                'duration' => $profit->invest->investPlan->plan_duration,
                'profit_amount' => $profit->profit_amount,
                'investment_amount' => $profit->invest->invest_amount,
                'created_at' => $profit->created_at,
            ];
        });
        return Response::success([__('Profit log fetched successfully!')],[
            'profits'  => $data,
        ],200);

    }
}
