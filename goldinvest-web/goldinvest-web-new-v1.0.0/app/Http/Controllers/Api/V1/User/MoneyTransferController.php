<?php

namespace App\Http\Controllers\Api\V1\User;

use Exception;
use App\Models\User;
use App\Models\UserWallet;
use Illuminate\Http\Request;
use App\Constants\GlobalConst;
use App\Http\Helpers\Response;
use App\Models\Admin\Currency;
use Illuminate\Support\Facades\DB;
use App\Models\Admin\BasicSettings;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Constants\PaymentGatewayConst;
use App\Models\Admin\UserNotification;
use App\Models\Admin\TransactionSetting;
use App\Notifications\User\SendMoneyMail;
use App\Providers\Admin\CurrencyProvider;
use Illuminate\Support\Facades\Validator;
use App\Notifications\User\ReceivedMoneyMail;

class MoneyTransferController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function getWallets()
    {
        $sender_wallets = UserWallet::auth()->whereHas('currency', function ($q) {
            $q->where("sender", GlobalConst::ACTIVE)->where("status", GlobalConst::ACTIVE);
        })->active()->with(['currency' => function ($q) {
            return $q->select('id', 'code', 'rate');
        }])->get();

        $sender_wallets->makeHidden(['id', 'user_id', 'currency_id', 'created_at', 'updated_at']);

        $receiver_wallets = Currency::receiver()->active()->select("code", "rate", "status", "default")->get();

        $charges = TransactionSetting::where("slug", GlobalConst::TRANSFER)->first();
        $charges->makeHidden(['admin', 'id', 'admin_id', 'slug', 'created_at', 'updated_at']);

        return Response::success([__('User wallets fetch successfully!')], [
            'user_wallets'              => $sender_wallets,
            'receiver_currencies'       => $receiver_wallets,
            'charges'                   => $charges,
        ], 200);
    }

    public function submit(Request $request)
    {

        $validated = Validator::make($request->all(), [
            'sender_amount'     => "required|numeric|gt:0",
            'receiver'          => "required|string",
        ])->validate();

        $default_currency = CurrencyProvider::default();

        $sender_wallet = UserWallet::auth()->whereHas("currency", function ($q) use ($default_currency) {
            $q->where("code", $default_currency->code)->active();
        })->active()->first();
        if (!$sender_wallet) return Response::error([__("Your wallet isn't available with currency (") . $default_currency->code . ')'], [], 404);

        $receiver_currency = Currency::receiver()->active()->where('code', $default_currency->code)->first();
        if (!$receiver_currency) return Response::error([__('Currency (') . $validated['receiver_currency'] . __(") isn't available for receive any transaction")], [], 404);

        $trx_charges = TransactionSetting::where("slug", GlobalConst::TRANSFER)->first();
        $charges = $this->transferCharges($validated['sender_amount'], $trx_charges, $sender_wallet, $receiver_currency);

        // Check transaction limit
        $sender_currency_rate = $sender_wallet->currency->rate;
        $min_amount = $trx_charges->min_limit * $sender_currency_rate;
        $max_amount = $trx_charges->max_limit * $sender_currency_rate;
        if ($charges['sender_amount'] < $min_amount || $charges['sender_amount'] > $max_amount) {
            return Response::error([__('Please follow the transaction limit. (Min ') . $min_amount . ' ' . $sender_wallet->currency->code . __(' - Max ') . $max_amount . ' ' . $sender_wallet->currency->code . ')'], [], 400);
        }

        $field_name = "username";
        if (check_email($validated['receiver'])) {
            $field_name = "email";
        }
        if (Auth::guard(get_auth_guard())->check()) {
            $user = auth()->guard(get_auth_guard())->user();
        }
        $basic_setting = BasicSettings::first();
        $receiver = User::notAuth()->where($field_name, $validated['receiver'])->active()->first();
        if (!$receiver) return Response::error([__(__("Receiver doesn't exists or Receiver is temporary banned"))], [], 400);

        $receiver_wallet = UserWallet::where("user_id", $receiver->id)->whereHas("currency", function ($q) use ($receiver_currency) {
            $q->receiver()->where("code", $receiver_currency->code);
        })->first();

        if (!$receiver_wallet) return Response::error([__('Receiver wallet not available')], [], 404);

        if ($charges['payable'] > $sender_wallet->balance) return Response::error([__('Your wallet balance is insufficient')], [], 400);

        // Transaction Start
        DB::beginTransaction();
        try {
            $trx_id = generate_unique_string("transactions", "trx_id", 16);
            // Sender TRX
            $inserted_id = DB::table("transactions")->insertGetId([
                'type'              => PaymentGatewayConst::TYPETRANSFERMONEY,
                'trx_id'            => $trx_id,
                'user_type'         => GlobalConst::USER,
                'user_id'           => $sender_wallet->user->id,
                'wallet_id'         => $sender_wallet->id,
                'request_amount'    => $charges['sender_amount'],
                'request_currency'  => $receiver_wallet->currency->code,
                'exchange_rate'     => $default_currency->rate,
                'percent_charge'    => $charges['percent_charge'],
                'fixed_charge'      => $charges['fixed_charge'],
                'total_charge'      => $charges['total_charge'],
                'total_payable'     => $charges['payable'],
                'receive_amount'    => $charges['receiver_amount'],
                'receiver_type'     => GlobalConst::USER,
                'receiver_id'       => $receiver_wallet->user->id,
                'available_balance' => $sender_wallet->balance - $charges['payable'],
                'payment_currency'  => $sender_wallet->currency->code,
                'details'           => json_encode(['receiver_username' => $receiver_wallet->user->username, 'sender_username' => $sender_wallet->user->username]),
                'status'            => GlobalConst::SUCCESS,
                'created_at'        => now(),
            ]);
            $mail_data = ([
                'rfullname' => $receiver_wallet->user->fullname,
                'sfullname' => $sender_wallet->user->fullname,
                'sender_amount' => $charges['sender_amount'],
                'sender_currency' => $sender_wallet->currency->code,
                'receiver_amount' => $charges['receiver_amount'],
                'receiver_currency' => $receiver_wallet->currency->code,
                'trx_id'    => $trx_id,
                'exchange_rate' => $default_currency->rate,
                'total_charge' => $charges['total_charge']
            ]);
            $sender_wallet->balance -= $charges['payable'];
            $sender_wallet->save();

            $receiver_wallet->balance += $charges['receiver_amount'];
            $receiver_wallet->save();
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            return Response::error([__('Transaction failed! Something went wrong! Please try again')], [], 500);
        }
        try {
            //USER notification
            $notification_content = [
                'title'         => __("Transfer Money"),
                'message'       => __("Transfer Money to  ") . $receiver_wallet->user->username . ' ' . $charges['receiver_amount'] . ' ' . $receiver_wallet->currency->code . __(" successful"),
                'image'         => files_asset_path('profile-default'),
            ];

            $this->createNotification($sender_wallet->user, $notification_content);

            //Receiver notification
            $notification_content = [
                'title'         => __("Transfer Money"),
                'message'       => __("Transfer Money from  ") . $sender_wallet->user->username . ' ' . $charges['receiver_amount'] . ' ' . $receiver_wallet->currency->code .__(" successful"),
                'image'         => files_asset_path('profile-default'),
            ];

            $this->createNotification($receiver_wallet->user, $notification_content);
            if ($basic_setting->email_notification == true) {
                $receiver->notify(new ReceivedMoneyMail($mail_data));
                $user->notify(new SendMoneyMail($mail_data));
            }
        } catch (Exception $e) {
            //throw $th;
        }
        return Response::success([__('Successfully money transfer to @') . $receiver_wallet->user->username . __(' success')], [], 200);
    }

      /**
     *this method is used to create new notification
     */
    public function createNotification($user, $message)
    {
        UserNotification::create([
            'type'      => PaymentGatewayConst::TYPETRANSFERMONEY,
            'user_id'  => $user->id,
            'message'   => $message,
        ]);
    }
    public function transferCharges($sender_amount, $charges, $sender_wallet, $receiver_currency)
    {
        $exchange_rate = $receiver_currency->rate / $sender_wallet->currency->rate;

        $data['exchange_rate']              = $exchange_rate;
        $data['sender_amount']              = $sender_amount;
        $data['sender_currency']            = $sender_wallet->currency->code;
        $data['receiver_amount']            = $sender_amount * $exchange_rate;
        $data['receiver_currency']          = $receiver_currency->code;
        $data['percent_charge']             = ($sender_amount / 100) * $charges->percent_charge ?? 0;
        $data['fixed_charge']               = $sender_wallet->currency->rate * $charges->fixed_charge ?? 0;
        $data['total_charge']               = $data['percent_charge'] + $data['fixed_charge'];
        $data['sender_wallet_balance']      = $sender_wallet->balance;
        $data['payable']                    = $sender_amount + $data['total_charge'];
        $data['default_currency_amount']    = ($sender_amount / $sender_wallet->currency->rate);
        $data['sender_currency_rate']       = $sender_wallet->currency->rate;
        return $data;
    }
}
