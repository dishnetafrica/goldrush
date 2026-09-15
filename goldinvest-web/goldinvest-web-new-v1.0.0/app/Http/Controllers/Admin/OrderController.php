<?php

namespace App\Http\Controllers\Admin;

use Exception;
use App\Models\User\Order;
use App\Models\UserWallet;
use Illuminate\Http\Request;
use App\Constants\GlobalConst;
use App\Models\Admin\GoldStock;
use App\Constants\LanguageConst;
use App\Http\Controllers\Controller;
use App\Models\Admin\UserNotification;
use App\Notifications\orderNotification;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Notification;

class OrderController extends Controller
{
    public function index()
    {
        $page_title = __("Order log");
        $lang = selectedLang();
        $orders = Order::with('user')->with('gold')->where('status', 1)->latest('id')->paginate(20);
        $default = LanguageConst::NOT_REMOVABLE;
        return view('admin.sections.orders.index', compact(
            'page_title',
            'orders',
            'lang',
            'default'
        ));
    }

    public function orderDetails($id)
    {
        $lang = selectedLang();
        $order = Order::with('user')->with('gold')->where('id', $id)->where('status', 1)->latest('id')->first();
        $page_title = __('Order log');
        $default = LanguageConst::NOT_REMOVABLE;

        return view('admin.sections.orders.details', compact(
            'page_title',
            'order',
            'lang',
            'default'
        ));
    }
    public function updateStatus(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'target'       => "required|integer|exists:orders,id",
            'order_status'       => "required",
            'cancel_reason'      => "nullable|string",
        ]);
        if ($validator->fails()) return back()->withErrors($validator)->withInput()->with('modal', 'status-change');
        $validated = $validator->validate();
        $order = Order::findOrFail($validated['target']);
        $order_info = Order::where('id',$validated['target'])->with('user')->with('gold')->first();

        $lang = selectedLang();
        $mail_data = ([
            'item'  => $order_info->gold->title->language->$lang->title,
            'quantity' => $order_info->quantity,
            'total_amount' => $order_info->total_amount,
            'payment_type' => $order_info->payment_type,
            'order_status' => $validated['order_status'],
            'mobile' => $order_info->full_mobile,
            'address' => $order_info->address->address,
            'city' => $order_info->address->city,
            'zip_code' => $order_info->address->zip_code,
            'state' => $order_info->address->state,
            'country' => $order_info->address->country,
            'price' => $order_info->gold->price,
            'charge' => $order_info->gold->charge,

        ]);

        try {
            $order->update($validated);
            if($validated['order_status'] == GlobalConst::CANCELLED && $order->payment_type == GlobalConst::PAYMENT_TYPE_USER_WALLET){
                $userWallet = UserWallet::where('user_id',$order->user_id)->first();
                $userWallet->balance +=  $order->total_amount;
                $userWallet->save();
            }
            Notification::route("mail", $order_info->user->email)->notify(new orderNotification($mail_data));
        } catch (Exception $e) {
            return back()->with(['error' => [__('Something went wrong! Please try again')]]);
        }
        return redirect()->back()->with(['success' => [__('Order Status updated successfully!')]]);
    }
}
