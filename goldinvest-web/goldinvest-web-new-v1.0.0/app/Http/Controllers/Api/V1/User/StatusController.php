<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Models\ReferredUser;
use Illuminate\Http\Request;
use App\Http\Helpers\Response;
use App\Http\Controllers\Controller;

class StatusController extends Controller
{
    public function statusInfo(){
        $user = auth()->guard("api")->user();
        $refer_users = ReferredUser::where('refer_user_id', $user->id)->with(['user' => function($query) {
            $query->with(['referUsers']);
        }])->paginate(10);

        $total_refers = $user->referUsers->count();
        $total_investment = $user->investPlans->sum('invest_amount');
        $level = $user->referLevel->title;
        $refer_code = $user->referral_id;
        $refer_link = route('user.register', $user->referral_id);

        return Response::success([__('Status info fetch successfully!')],[
            'total_refers'  => $total_refers,
            'total_investment' => $total_investment ,
            'level'   => $level,
            'refer_code'     => $refer_code,
            'refer_link'     => $refer_link,
            'refer_users'    => $refer_users,
        ],200);
    }
}
