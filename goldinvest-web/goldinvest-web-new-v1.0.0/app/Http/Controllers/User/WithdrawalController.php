<?php

namespace App\Http\Controllers\User;

use Exception;
use App\Models\UserWallet;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Models\TemporaryData;
use App\Constants\GlobalConst;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\Admin\PaymentGateway;
use App\Models\Admin\MoneyOutSetting;
use App\Constants\PaymentGatewayConst;
use App\Providers\Admin\CurrencyProvider;
use App\Traits\ControlDynamicInputFields;
use Illuminate\Support\Facades\Validator;
use App\Models\Admin\PaymentGatewayCurrency;

class WithdrawalController extends Controller
{
    use ControlDynamicInputFields;

    public function index()
    {
        $page_title = __("Money Out");
        $breadcrumb = __("Money Out");
        $payment_gateways = PaymentGateway::moneyOut()->manual()->active()->get();
        $transactions = Transaction::auth()->moneyOut()->with('gateway_currency')->latest('id')->take(3)->get();
        $money_out_settings = MoneyOutSetting::first();
        return view('user.sections.withdraw-money.index', compact('page_title','payment_gateways','transactions','money_out_settings'));
    }

    public function submit(Request $request) {

        $validated = $request->validate([
            'payment_gateway'   => "required|exists:payment_gateways,alias",
            'amount'            => "required|numeric|gt:0",
            'wallet_type'       => 'required|string|in:c_balance,p_balance',
        ]);

        $money_out_settings = MoneyOutSetting::first();
        $money_out_features = [
            'c_balance'     => $money_out_settings?->c_balance,
            'p_balance'     => $money_out_settings?->p_balance,
        ];

        if($money_out_features[$validated['wallet_type']] != true) {
            return back()->with(['error' => [__('Service not available!')]]);
        }

        $default_currency = CurrencyProvider::default();

        $sender_wallet = UserWallet::auth()->whereHas('currency',function($query) use ($default_currency) {
            $query->where('code',$default_currency->code)->active();
        })->first();

        $gateway = PaymentGateway::moneyOut()->gateway($validated['payment_gateway'])->first();
        if(!$gateway->isManual()) return back()->with(['error' => [__("Gateway isn't available for this transaction")]]);
        $gateway_currency = $gateway->currencies->first();

        $charges = $this->moneyOutCharges($validated['amount'],$gateway_currency,$sender_wallet); // Withdraw charge

        if($validated['wallet_type'] == 'c_balance') {
            if($sender_wallet->balance < $charges->total_payable) return back()->with(['error' => [__('Your wallet balance is insufficient')]]);
        }else if($validated['wallet_type'] == 'p_balance') {
            if($sender_wallet->profit_balance < $charges->total_payable) return back()->with(['error' => [__('Your Profit balance is insufficient')]]);
        }

        $exchange_request_amount    = $charges->request_amount;
        $gateway_min_limit          = $gateway_currency->min_limit / $charges->exchange_rate;
        $gateway_max_limit          = $gateway_currency->max_limit / $charges->exchange_rate;

        if($exchange_request_amount < $gateway_min_limit || $exchange_request_amount > $gateway_max_limit) return back()->with(['error' => [__('Please follow the transaction limit. (Min ').$gateway_min_limit . ' ' . $sender_wallet->currency->code .__(' - Max ').$gateway_max_limit. ' ' . $sender_wallet->currency->code . ')']]);

        // Store Temp Data
        try{
            $token = generate_unique_string("temporary_datas","identifier",16);
            TemporaryData::create([
                'type'          => PaymentGatewayConst::money_out_slug(),
                'identifier'    => $token,
                'data'          => [
                    'gateway_currency_id'   => $gateway_currency->id,
                    'wallet_id'             => $sender_wallet->id,
                    'charges'               => $charges,
                    'wallet_type'           => $validated['wallet_type'],
                ],
            ]);
        }catch(Exception $e) {
            return back()->with(['error' => [__('Something went wrong! Please try again')]]);
        }

        return redirect()->route('user.withdraw.money.instruction',$token);

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

    public function instruction($token) {
        $tempData = TemporaryData::where('identifier',$token)->first();
        $gateway_currency_id = $tempData->data->gateway_currency_id ?? "";
        if(!$gateway_currency_id) return redirect()->route('user.withdraw.money.index')->with(['error' => [__('Invalid Request!')]]);

        $gateway_currency = PaymentGatewayCurrency::find($gateway_currency_id);
        if(!$gateway_currency) return redirect()->route('user.withdraw.money.index')->with(['error' => [__('Payment gateway currency is invalid!')]]);
        $gateway = $gateway_currency->gateway;
        $input_fields = $gateway->input_fields;
        if($input_fields == null || !is_array($input_fields)) return redirect()->route('user.withdraw.money.index')->with(['error' => [__('This gateway is temporary pause or under maintenance!')]]);
        $amount = $tempData->data->charges;
        $page_title = __("Money Out");
        $breadcrumb = __("Money Out Instructions");
        return view('user.sections.withdraw-money.instructions',compact('page_title','gateway','token','amount','breadcrumb'));
    }

    public function instructionSubmit(Request $request,$token) {

        $tempData = TemporaryData::where('identifier',$token)->first();
        $gateway_currency_id = $tempData->data->gateway_currency_id ?? "";
        if(!$gateway_currency_id) return redirect()->route('user.withdraw.money.index')->with(['error' => [__('Invalid Request!')]]);

        $gateway_currency = PaymentGatewayCurrency::find($gateway_currency_id);
        if(!$gateway_currency) return redirect()->route('user.withdraw.money.index')->with(['error' => [__('Payment gateway currency is invalid!')]]);
        $gateway = $gateway_currency->gateway;

        $wallet_id = $tempData->data->wallet_id ?? null;
        $wallet = UserWallet::auth()->active()->find($wallet_id);
        if(!$wallet) return redirect()->route('user.withdraw.money.index')->with(['error' => [__('Your wallet is invalid!')]]);

        $this->file_store_location = "transaction";
        $dy_validation_rules = $this->generateValidationRules($gateway->input_fields);

        $validated = Validator::make($request->all(),$dy_validation_rules)->validate();
        $get_values = $this->placeValueWithFields($gateway->input_fields,$validated);

        $amount = $tempData->data->charges;

        $wallet_balance = 0;
        if($tempData->data->wallet_type == 'c_balance') {
            $wallet_balance = $wallet->balance;
            if($wallet->balance < $amount->total_payable) return redirect()->route('user.withdraw.money.index')->with(['error' => [__('Your wallet balance is insufficient!')]]);
        }elseif($tempData->data->wallet_type == 'p_balance') {
            $wallet_balance = $wallet->profit_balance;
            if($wallet->profit_balance < $amount->total_payable) return redirect()->route('user.withdraw.money.index')->with(['error' => [__('Your Profit balance is insufficient!')]]);
        }else {
            return redirect()->route('user.withdraw.index')->with(['error' => [__('Oops! Wallet type not defined')]]);
        }

        // Make Transaction
        DB::beginTransaction();
        try{
            DB::table("transactions")->insertGetId([
                'type'                          => PaymentGatewayConst::TYPEWITHDRAW,
                'trx_id'                        => generate_unique_string('transactions','trx_id',16, "WM"),
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
                'details'                       => json_encode(['input_values' => $get_values,'charges' => $amount,'wallet_type' => $tempData->data->wallet_type]),
                'status'                        => PaymentGatewayConst::STATUSPENDING,
                'created_at'                    => now(),
            ]);

            if($tempData->data->wallet_type == 'c_balance') {
                DB::table($wallet->getTable())->where("id",$wallet->id)->update([
                    'balance'       => ($wallet->balance - $amount->total_payable),
                ]);
            }else {
                DB::table($wallet->getTable())->where("id",$wallet->id)->update([
                    'profit_balance'       => ($wallet->profit_balance - $amount->total_payable),
                ]);
            }

            DB::commit();
        }catch(Exception $e) {
            DB::rollBack();
            return redirect()->route('user.withdraw.money.instruction',$token)->with(['error' => [__('Something went wrong! Please try again')]]);
        }
        $tempData->delete();

        return redirect()->route('user.withdraw.money.index')->with(['success' => [__('Transaction success! Please wait for confirmation')]]);
    }
}
