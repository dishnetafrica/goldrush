<?php

namespace App\Http\Controllers\Api\V1\User;

use Exception;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Constants\GlobalConst;
use App\Http\Helpers\Response;
use App\Http\Controllers\Controller;
use App\Constants\PaymentGatewayConst;
use Illuminate\Support\Facades\Validator;

class TransactionController extends Controller
{
    public function slugValue($slug) {
        $values =  [
            'add-money'         => PaymentGatewayConst::TYPEADDMONEY,
            'money-out'         => PaymentGatewayConst::TYPEMONEYOUT,
            'money-transfer'    => PaymentGatewayConst::TYPETRANSFERMONEY,
            'withdraw'          => PaymentGatewayConst::TYPEWITHDRAW,
            'capital-return'    => PaymentGatewayConst::TYPECAPITALRETURN,
            'add-subtract-balance' => PaymentGatewayConst::TYPEADDSUBTRACTBALANCE
        ];

        if(!array_key_exists($slug,$values)) return abort(404);
        return $values[$slug];
    }

    public function log(Request $request) {

        $validator = Validator::make($request->all(),[
            'slug'      => "nullable|string|in:add-money,money-out,money-transfer,money-exchange,withdraw,capital-return,add-subtract-balance",
        ]);
        if($validator->fails()) return Response::error($validator->errors()->all(),[]);

        $validated = $validator->validate();

        try{
            $user_id = auth()->user()->id;
            if(isset($validated['slug']) && $validated['slug'] != "") {
                // $transactions = Transaction::auth()->where("type",$this->slugValue($validated['slug']))->orderByDesc("id")->get();
                $transactions = Transaction::where('type', $this->slugValue($validated['slug']))
                ->where(function($q) use ($user_id) {
                    $q->where('user_id', $user_id)
                        ->orWhere('receiver_id', $user_id);
                })
                ->orderByDesc('id')
                ->get();
            }else {
                // $transactions = Transaction::auth()->orderByDesc("id")->get();
                $transactions = Transaction::where(function($q) {
                    $q->where('user_type',GlobalConst::USER)->orWhere('receiver_type',GlobalConst::USER);
                })->where(function($q) {
                    $q->where('user_id',auth()->user()->id)->orWhere('receiver_id',auth()->user()->id);
                })->orderByDesc('id')->get();
            }

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
                'updated_at',
                'gateway_currency',
            ]);

        }catch(Exception $e) {
            return Response::error([__('Something went wrong! Please try again')],[],500);
        }

        return Response::success([__('Transactions fetch successfully!')],[
            'instructions'  => [
                'slug'      => "add-money,money-transfer,withdraw,capital-return,add-subtract-balance",
                'status'    => "1: Success, 2: Pending, 3: Hold, 4: Rejected, 5: Waiting"
            ],
            'transaction_types' => [
                PaymentGatewayConst::TYPEADDMONEY,
                PaymentGatewayConst::TYPETRANSFERMONEY,
                PaymentGatewayConst::TYPEWITHDRAW,
                PaymentGatewayConst::TYPECAPITALRETURN,
                PaymentGatewayConst::TYPEADDSUBTRACTBALANCE
            ],
            'transactions'  => $transactions,
        ],200);
    }
}
