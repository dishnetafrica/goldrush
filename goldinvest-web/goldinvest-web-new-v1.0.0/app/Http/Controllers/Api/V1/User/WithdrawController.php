<?php

namespace App\Http\Controllers\Api\V1\User;

use Exception;
use App\Models\UserWallet;
use Illuminate\Http\Request;
use App\Constants\GlobalConst;
use App\Http\Helpers\Response;
use App\Models\Admin\Currency;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\Admin\PaymentGateway;
use App\Models\Admin\MoneyOutSetting;
use App\Constants\PaymentGatewayConst;
use App\Providers\Admin\CurrencyProvider;
use App\Traits\ControlDynamicInputFields;
use Illuminate\Support\Facades\Validator;
use App\Models\Admin\PaymentGatewayCurrency;

class WithdrawController extends Controller
{
    use ControlDynamicInputFields;

    public function walletGateways() {
       

        $payment_gateways = PaymentGateway::moneyOut()->manual()->active()->has("currencies")->get()->map(function($item) {
            return [
                'id'        => $item->id,
                'type'      => $item->type,
                'name'      => $item->name,
                'alias'     => $item->alias,
                'desc'      => $item->desc,
                'status'    => $item->status,
                'currencies'      => [
                    [
                        'gateway_alias'                     => $item->alias,
                        'id'                                => $item->currencies->first()->id,
                        'alias'                             => $item->currencies->first()->alias,
                        'payment_gateway_id'                => $item->currencies->first()->payment_gateway_id,
                        'name'                              => $item->currencies->first()->name,
                        'currency_code'                     => $item->currencies->first()->currency_code,
                        'currency_symbol'                   => $item->currencies->first()->currency_symbol,
                        'image'                             => $item->currencies->first()->image,
                        'min_limit'                         => $item->currencies->first()->min_limit,
                        'max_limit'                         => $item->currencies->first()->max_limit,
                        'percent_charge'                    => $item->currencies->first()->percent_charge,
                        'fixed_charge'                      => $item->currencies->first()->fixed_charge,
                        'rate'                              => $item->currencies->first()->rate,
                        'created_at'                        => $item->currencies->first()->created_at,
                        'updated_at'                        => $item->currencies->first()->updated_at,
                    ]
                ],
            ];
        });

        $user_wallets = UserWallet::auth()->active()->with("currency:id,code,rate")->get();
        $user_wallets->makeHidden(['id','user_id','currency_id','created_at','updated_at']);

        $money_out_settings = MoneyOutSetting::first();
        $balance_type['status']     = false;
        $balance_type['message']    = __("Withdraw service is temporary unavailable. If you have any queries, feel free to contact support. Thanks");
        $balance_type['types'] = [];
        if($money_out_settings) {
            if($money_out_settings->p_balance == true) {
                $balance_type['status']     = true;
                $balance_type['message'] = "";
                $balance_type['types'][] = [
                    'name'            => 'Profit Balance',
                    'value'           => 'p_balance',
                ];
            }

            if($money_out_settings->c_balance == true) {
                $balance_type['status']     = true;
                $balance_type['message'] = "";
                $balance_type['types'][] = [
                    'name'            => 'Wallet Balance',
                    'value'           => 'c_balance',
                ];
            }
        }

        return Response::success([__('Request data fetch successfully!')],[
            'balance_type'          => $balance_type,
            'user_wallets'          => $user_wallets,
            'payment_gateways'      => $payment_gateways,
        ],200);
    }

    public function gatewayInputFields(Request $request) {
        $validator = Validator::make($request->all(),[
            'currency'      => "required|string|exists:payment_gateway_currencies,alias"
        ]);
        if($validator->fails()) return Response::error($validator->errors()->all(),[]);
        $validated = $validator->validate();

        $gateway_currency = PaymentGatewayCurrency::where("alias",$validated['currency'])->whereHas("gateway",function($q) {
            $q->where("type",PaymentGatewayConst::MANUAL);
        })->first();

        if(!$gateway_currency) return Response::error([__('Payment gateway not found!')],[],404);
        $gateway = $gateway_currency->gateway;

        try{
            $input_fields = $gateway->input_fields ?? null;
            if(!$input_fields) return Response::error([__('Payment gateway is under maintenance. Please try with another gateway')]);

            if($input_fields != null) {
                $input_fields = json_decode(json_encode($input_fields),true);
            }

        }catch(Exception $e) {
            return Response::error([__('Something went wrong! Please try again')],[],500);
        }

        return Response::success([__('Payment gateway input field fetch successfully!')],[
            'gateway'           => [
                'desc'          => $gateway->desc,
            ],
            'input_fields'      => $input_fields,
        ],200);
    }

    public function submit(Request $request) {

        $validator = Validator::make($request->all(),[
            'payment_gateway'   => "required|exists:payment_gateways,alias",
            'amount'            => "required|numeric|gt:0",
            'wallet_type'       => 'required|string|in:c_balance,p_balance',
        ]);

        if($validator->fails()) {
            return Response::error($validator->errors()->all());
        }

        $validated = $validator->validate();

        $money_out_settings = MoneyOutSetting::first();
        $money_out_features = [
            'c_balance'     => $money_out_settings?->c_balance,
            'p_balance'     => $money_out_settings?->p_balance,
        ];

        if($money_out_features[$validated['wallet_type']] != true) {
            return Response::error([__('Service not available!')],[],503);
        }

        $default_currency = CurrencyProvider::default();

        $wallet = UserWallet::auth()->whereHas('currency',function($query) use ($default_currency) {
            $query->where('code',$default_currency->code)->active();
        })->first();

        $gateway = PaymentGateway::moneyOut()->gateway($validated['payment_gateway'])->first();
        if(!$gateway->isManual()) return back()->with(['error' => [__("Gateway isn't available for this transaction")]]);
        $gateway_currency = $gateway->currencies->first();

        $charges = $this->moneyOutCharges($validated['amount'],$gateway_currency,$wallet); // Withdraw charge

        $wallet_balance = 0;
        if($validated['wallet_type'] == 'c_balance') {
            $wallet_balance = $wallet->balance;
            if($wallet->balance < $charges->total_payable) return Response::error([__('Your wallet balance is insufficient')],[],400);
        }else if($validated['wallet_type'] == 'p_balance') {
            $wallet_balance = $wallet->profit_balance;
            if($wallet->profit_balance < $charges->total_payable) return Response::error([__('Your Profit balance is insufficient')],[],400);
        }else {
            return Response::error([__('Oops! Wallet type not defined')],[],404);
        }

        $exchange_request_amount    = $charges->request_amount;
        $gateway_min_limit          = $gateway_currency->min_limit / $charges->exchange_rate;
        $gateway_max_limit          = $gateway_currency->max_limit / $charges->exchange_rate;

        if($exchange_request_amount < $gateway_min_limit || $exchange_request_amount > $gateway_max_limit) return Response::error([__('Please follow the transaction limit. (Min ').$gateway_min_limit . ' ' . $wallet->currency->code .__(' - Max ').$gateway_max_limit. ' ' . $wallet->currency->code . ')'],[],400);

        $this->file_store_location = "transaction";
        $dy_validation_rules = $this->generateValidationRules($gateway->input_fields);

        $dy_field_validated = Validator::make($request->all(),$dy_validation_rules)->validate();

        $get_values = $this->placeValueWithFields($gateway->input_fields,$dy_field_validated);

        $amount = $charges;

        DB::beginTransaction();
        try{
            DB::table("transactions")->insertGetId([
                'type'                          => PaymentGatewayConst::TYPEWITHDRAW,
                'trx_id'                        => generate_unique_string('transactions','trx_id',16),
                'user_type'                     => GlobalConst::USER,
                'user_id'                       => $wallet->user->id,
                'wallet_id'                     => $wallet->id,
                'payment_gateway_currency_id'   => $gateway_currency->id,
                'request_amount'                => $amount->request_amount,
                'request_currency'              => $wallet->currency->code,
                'exchange_rate'                 => $amount->exchange_rate,
                'percent_charge'                => $amount->percent_charge,
                'fixed_charge'                  => $amount->fixed_charge,
                'total_charge'                  => $amount->total_charge,
                'total_payable'                 => $amount->total_payable,
                'available_balance'             => $wallet_balance - $amount->total_payable,
                'receive_amount'                => $amount->will_get,
                'receiver_type'                 => GlobalConst::USER,
                'receiver_id'                   => $wallet->user->id,
                'payment_currency'              => $gateway_currency->currency_code,
                'details'                       => json_encode(['input_values' => $get_values,'charges' => $amount,'wallet_type' => $validated['wallet_type']]),
                'status'                        => PaymentGatewayConst::STATUSPENDING,
                'created_at'                    => now(),
            ]);

            if($validated['wallet_type'] == 'c_balance') {
                DB::table($wallet->getTable())->where("id",$wallet->id)->update([
                    'balance'       => ($wallet->balance - $amount->total_payable),
                ]);
            }else if($validated['wallet_type'] == 'p_balance') {
                DB::table($wallet->getTable())->where("id",$wallet->id)->update([
                    'profit_balance'       => ($wallet->profit_balance - $amount->total_payable),
                ]);
            }

            DB::commit();
        }catch(Exception $e) {
            DB::rollBack();
            return Response::error([__('Something went wrong! Please try again')],[],500);
        }

        return Response::success([__('Transaction success! Please wait for confirmation')],[],200);
    }

    public function moneyOutCharges($amount,$currency,$wallet) {

        $data['exchange_rate']          = $currency->rate;
        $data['request_amount']         = $amount;
        $data['fixed_charge']           = $currency->fixed_charge / $data['exchange_rate'];
        $data['percent_charge']         = ((($amount * $currency->rate) / 100) * $currency->percent_charge) / $currency->rate;
        $data['gateway_currency_code']  = $currency->currency_code;
        $data['gateway_currency_id']    = $currency->id;
        $data['sender_currency_code']   = $wallet->currency->code;
        $data['sender_wallet_id']       = $wallet->id;
        $data['will_get']               = ($amount * $data['exchange_rate']);
        $data['receive_currency']       = $currency->currency_code;
        $data['sender_currency']        = $wallet->currency->code;
        $data['total_charge']           = $data['fixed_charge'] + $data['percent_charge']; // in sender currency
        $data['total_payable']          = $data['request_amount'] + $data['total_charge']; // in sender currency

        return (object) $data;
    }
}
