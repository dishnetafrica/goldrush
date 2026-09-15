<?php

namespace App\Http\Controllers\Api\V1;

use Exception;
use App\Models\User\Order;
use App\Models\UserWallet;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use App\Constants\GlobalConst;
use App\Http\Helpers\Response;
use App\Models\Admin\GoldStock;
use App\Models\Admin\BasicSettings;
use App\Http\Controllers\Controller;
use App\Constants\PaymentGatewayConst;
use App\Models\Admin\UserNotification;
use App\Notifications\orderNotification;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Notification;

class OrderController extends Controller
{
    public function getGolds(Request $request)
    {
        $lang = $request->lang;
        $default = 'en';
        $gold = GoldStock::where('status', 1)->latest('id')->get();
        if (isset($gold)) {
            $golds = [];
            foreach ($gold ?? [] as  $value) {

                $title = isset($value->title->language->$lang) ? $value->title->language->$lang->title : $value->title->language->$default->title;

                $golds[] = [
                    'id' => $value->id,
                    'title' => $title,
                    'slug'  => $value->slug,
                    'type' => $value->type,
                    'price' => $value->price,
                    'charge' => $value->charge,
                    'weight' => $value->weight,
                    'purity' => $value->purity,
                    'manufacturer'    => $value->manufacturer,
                    'country_of_origin' => $value->country_of_origin,
                    'image' => $value->image,
                ];
            }
        } else {
            $golds = [];
        }
        $gold_data = [
            'base_url' => url('/'),
            'image_path' => files_asset_path_basename("site-section"),
            'golds' => $golds,
        ];
        return Response::success([__('User Gold data fetched successfully!')],  $gold_data, 200);
    }

    public function checkout(Request $request)
    {
        $validator = Validator::make(request()->all(), [
            'slug'              => "required|string|exists:gold_stocks,slug",
        ]);

        if($validator->fails()) {
            return Response::error($validator->errors()->all(),[],400);
        }

        $validated = $validator->validate();

        $lang = $request->lang;
        $default = 'en';
        $gold = GoldStock::where('status', 1)->where('slug', $validated['slug'])->first();

        $title = isset($gold->title->language->$lang) ? $gold->title->language->$lang->title : $gold->title->language->$default->title;

        $payment_type = [
            'wallet' => GlobalConst::PAYMENT_TYPE_USER_WALLET,
            'cash_on_delivery' => GlobalConst::PAYMENT_TYPE_CASH_ON_DELIVERY
        ];

        $gold = [
            'id' => $gold->id,
            'title' => $title,
            'slug'  => $gold->slug,
            'type' => $gold->type,
            'price' => $gold->price,
            'charge' => $gold->charge,
            'weight' => $gold->weight,
            'purity' => $gold->purity,
            'manufacturer'    => $gold->manufacturer,
            'country_of_origin' => $gold->country_of_origin,
            'image' => $gold->image,
        ];


        $gold_data = [
            'base_url' => url('/'),
            'image_path' => files_asset_path_basename("site-section"),
            'payment_type' => $payment_type,
            'available_balance' => authWalletBalance(),
            'gold' => $gold,
        ];
        return Response::success([__('User Gold data fetched successfully!')],  $gold_data, 200);
    }
    public function order(Request $request)
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
            'slug'          => "required|string|exists:gold_stocks,slug",
        ])->validate();

        $validated['mobile']        = remove_speacial_char($validated['phone']);
        $validated['mobile_code']   = remove_speacial_char($validated['phone_code']);
        $complete_phone             = $validated['mobile_code'] . $validated['mobile'];
        $validated['full_mobile']   = $complete_phone;
        $validated                  = Arr::except($validated, ['agree', 'phone_code', 'phone']);
        $validated['address']       = [
            'country'   => $validated['country'] ?? "",
            'state'     => $validated['state'] ?? "",
            'city'      => $validated['city'] ?? "",
            'zip_code'       => $validated['zip_code'] ?? "",
            'address'   => $validated['address'] ?? "",
        ];

        $validated['quantity'] = $request->qtybutton;
        $validated['payment_type'] = $request->payment_type;
        $gold = GoldStock::where('slug', $validated['slug'])->first();
        $total_amount =  $validated['quantity'] *  $gold->price + $gold->charge;
        $validated['total_amount'] = $total_amount;
        $user_wallet = UserWallet::auth()->first();
        $basic_settings = BasicSettings::first();
        if ($validated['payment_type'] == 'USER-WALLET') {
            if ($total_amount > $user_wallet->balance) return Response::error([__('Your wallet balance is insufficient')], [], 400);
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
        $lang = $request->lang;
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
            return  Response::error([__('Something went wrong. Please try again')], [], 400);
        }

        try{
            if ($basic_settings->email_notification == true){
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
        }catch(Exception $e){

        }

        return Response::success([__('Your order is successful!')], $mail_data, 200);
    }
    public function orderLog(Request $request)
    {

        $lang = $request->lang;
        $default = 'en';
        $orders = Order::auth()->with('user')->with('gold')->where('status', 1)->latest('id')->get();

        if (isset($orders)) {
            $order = [];
            foreach ($orders ?? [] as  $value) {

                $title = isset($value->gold->title->language->$lang) ? $value->gold->title->language->$lang->title : $value->gold->title->language->$default->title;
                $order[] = [
                    'id' => $value->id,
                    'item' => $title,
                    'quantity' => $value->quantity,
                    'total_amount' => $value->total_amount,
                    'payment_type' => $value->payment_type,
                    'date' => $value->created_at,
                    'order_status' => $value->order_status,
                ];
            }
        } else {
            $order = [];
        }
        $instructions = [
            'order_status'      => "1: Accepted, 2: Ongoing, 3: Delivered, 4: Cancelled",
        ];
        $order_data = [
            'instructions' => $instructions,
            'order' => $order,
        ];
        return Response::success([__('User Order data fetched successfully!')],  $order_data, 200);
    }
}
