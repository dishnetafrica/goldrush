<?php

namespace App\Http\Controllers\Admin;

use Exception;
use App\Models\User;
use App\Models\UserWallet;
use App\Models\Transaction;
use App\Models\UserMailLog;
use Illuminate\Support\Arr;
use App\Models\UserLoginLog;
use Illuminate\Http\Request;
use App\Constants\GlobalConst;
use App\Http\Helpers\Response;
use App\Models\UserSupportTicket;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\ReferralLevelPackage;
use App\Notifications\User\SendMail;
use Illuminate\Support\Facades\Auth;
use App\Constants\PaymentGatewayConst;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Notification;
use App\Notifications\User\MessageNotification;

class UserCareController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $page_title = __("All Users");
        $users = User::orderBy('id', 'desc')->paginate(12);
        return view('admin.sections.user-care.index', compact(
            'page_title',
            'users'
        ));
    }

    /**
     * Display Active Users
     * @return view
     */
    public function active()
    {
        $page_title = __("Active Users");
        $users = User::active()->orderBy('id', 'desc')->paginate(12);
        return view('admin.sections.user-care.index', compact(
            'page_title',
            'users'
        ));
    }


    /**
     * Display Banned Users
     * @return view
     */
    public function banned()
    {
        $page_title = __("Banned Users");
        $users = User::banned()->orderBy('id', 'desc')->paginate(12);
        return view('admin.sections.user-care.index', compact(
            'page_title',
            'users',
        ));
    }

    /**
     * Display Email Unverified Users
     * @return view
     */
    public function emailUnverified()
    {
        $page_title = __("Email Unverified Users");
        $users = User::active()->orderBy('id', 'desc')->emailUnverified()->paginate(12);
        return view('admin.sections.user-care.index', compact(
            'page_title',
            'users'
        ));
    }



    /**
     * Display KYC Unverified Users
     * @return view
     */
    public function KycUnverified()
    {
        $page_title = __("KYC Unverified Users");
        $users = User::kycUnverified()->orderBy('id', 'desc')->paginate(8);
        return view('admin.sections.user-care.index', compact(
            'page_title',
            'users'
        ));
    }

    /**
     * Display Send Email to All Users View
     * @return view
     */
    public function emailAllUsers()
    {
        $page_title = __("Email To Users");
        return view('admin.sections.user-care.email-to-users', compact(
            'page_title',
        ));
    }

    /**
     * Display Specific User Information
     * @return view
     */
    public function userDetails($username)
    {
        $page_title = __("User Details");
        $user = User::where('username', $username)->first();
        if(!$user) return back()->with(['error' => [__('Oops! User does not exists')]]);
        $balance = UserWallet::where('user_id', $user->id)->first()->balance;
        $total_transactions =  Transaction::where('user_id', $user->id)->sum('request_amount');
        $pending_transactions =  Transaction::where('user_id', $user->id)->where('status', 2)->sum('request_amount');
        $money_out_amount = Transaction::where('user_id', $user->id)->where('type', PaymentGatewayConst::TYPEWITHDRAW)->sum('request_amount');
        $cancel_money_out = Transaction::where('user_id', $user->id)->where('type', PaymentGatewayConst::TYPEWITHDRAW)->where('status', 4)->sum('request_amount');
        $pending_money_out = Transaction::where('user_id', $user->id)->where('type', PaymentGatewayConst::TYPEWITHDRAW)->where('status', 2)->sum('request_amount');
        $add_money_amount = Transaction::where('user_id', $user->id)->where('type', PaymentGatewayConst::TYPEADDMONEY)->sum('request_amount');
        $cancel_add_money = Transaction::where('user_id', $user->id)->where('type', PaymentGatewayConst::TYPEADDMONEY)->where('status', 4)->sum('request_amount');
        $pending_add_money = Transaction::where('user_id', $user->id)->where('type', PaymentGatewayConst::TYPEADDMONEY)->where('status', 2)->sum('request_amount');
        $transfer_money_amount = Transaction::where('user_id', $user->id)->where('type', PaymentGatewayConst::TYPETRANSFERMONEY)->sum('request_amount');
        $cancel_transfer_money = Transaction::where('user_id', $user->id)->where('type', PaymentGatewayConst::TYPETRANSFERMONEY)->where('status', 4)->sum('request_amount');
        $pending_transfer_money = Transaction::where('user_id', $user->id)->where('type', PaymentGatewayConst::TYPETRANSFERMONEY)->where('status', 2)->sum('request_amount');
        $tickets = UserSupportTicket::count();
        $active_tickets = UserSupportTicket::where('status', 2)->count();
        $solved_tickets = UserSupportTicket::where('status', 1)->count();
        $pending_tickets = UserSupportTicket::where('status', 3)->count();
        $refer_users = $user->referUsers()->count();
        $refer_level = ReferralLevelPackage::where('id',$user->current_referral_level_id)->first();


        return view('admin.sections.user-care.details', compact(
            'page_title',
            'user',
            'balance',
            'total_transactions',
            'active_tickets',
            'money_out_amount',
            'solved_tickets',
            'pending_tickets',
            'tickets',
            'pending_transactions',
            'cancel_money_out',
            'pending_money_out',
            'add_money_amount',
            'cancel_add_money',
            'pending_add_money',
            'transfer_money_amount',
            'cancel_transfer_money',
            'pending_transfer_money',
            'refer_level',
            'refer_users',
        ));
    }

    public function sendMailUsers(Request $request) {
        $request->validate([
            'user_type'     => "required|string|max:30",
            'subject'       => "required|string|max:250",
            'message'       => "required|string|max:2000",
        ]);

        $users = [];
        switch($request->user_type) {
            case "active";
                $users = User::active()->get();
                break;
            case "all";
                $users = User::get();
                break;
            case "email_verified";
                $users = User::emailVerified()->get();
                break;
            case "kyc_verified";
                $users = User::kycVerified()->get();
                break;
            case "banned";
                $users = User::banned()->get();
                break;
        }

        try{
            Notification::send($users,new SendMail((object) $request->all()));
        }catch(Exception $e) {
            return back()->with(['error' => [__('Something went wrong! Please try again')]]);
        }

        return back()->with(['success' =>  [__('Email successfully sended')]]);

    }

    public function sendMail(Request $request, $username)
    {
        $request->merge(['username' => $username]);
        $validator = Validator::make($request->all(),[
            'subject'       => 'required|string|max:200',
            'message'       => 'required|string|max:2000',
            'username'      => 'required|string|exists:users,username',
        ]);
        if($validator->fails()) {
            return back()->withErrors($validator)->withInput()->with("modal","email-send");
        }
        $validated = $validator->validate();
        $user = User::where("username",$username)->first();
        $validated['user_id'] = $user->id;
        $validated = Arr::except($validated,['username']);
        $validated['method']   = "SMTP";
        try{
            UserMailLog::create($validated);
            $user->notify(new SendMail((object) $validated));
        }catch(Exception $e) {
            return back()->with(['error' => [__('Something went wrong! Please try again')]]);
        }
        return back()->with(['success' => [__('Mail successfully sended')]]);
    }

    public function userDetailsUpdate(Request $request, $username)
    {
        $request->merge(['username' => $username]);
        $validator = Validator::make($request->all(),[
            'username'              => "required|exists:users,username",
            'firstname'             => "required|string|max:60",
            'lastname'              => "required|string|max:60",
            'mobile_code'           => "nullable|string|max:10",
            'mobile'                => "nullable|string|max:20",
            'address'               => "nullable|string|max:250",
            'country'               => "nullable|string|max:50",
            'state'                 => "nullable|string|max:50",
            'city'                  => "nullable|string|max:50",
            'zip_code'              => "nullable|numeric|max_digits:8",
            'email_verified'        => 'required|boolean',
            'two_factor_status'     => 'required|boolean',
            'kyc_verified'          => 'required|boolean',
            'status'                => 'required|boolean',
        ]);
        $validated = $validator->validate();
        $validated['address']  = [
            'country'       => $validated['country'] ?? "",
            'state'         => $validated['state'] ?? "",
            'city'          => $validated['city'] ?? "",
            'zip'           => $validated['zip_code'] ?? "",
            'address'       => $validated['address'] ?? "",
        ];
        if($validated['mobile'] &&  $validated['mobile_code'] != null){
            $validated['mobile_code']       = remove_speacial_char($validated['mobile_code']);
            $validated['mobile']            = remove_speacial_char($validated['mobile']);
            $validated['full_mobile']       = $validated['mobile_code'] . $validated['mobile'];
        }

        $user = User::where('username', $username)->first();
        if(!$user) return back()->with(['error' => [__('Oops! User does not exists')]]);

        try {
            $user->update($validated);
        } catch (Exception $e) {
            return back()->with(['error' =>[__('Something went wrong! Please try again')]]);
        }

        return back()->with(['success' => [__('Profile Information Updated Successfully!')]]);
    }

    public function loginLogs($username)
    {
        $page_title = __("Login Logs");
        $user = User::where("username",$username)->first();
        if(!$user) return back()->with(['error' => [__("Oops! User doesn't exists")]]);
        $logs = UserLoginLog::where('user_id',$user->id)->paginate(12);
        return view('admin.sections.user-care.login-logs', compact(
            'logs',
            'page_title',
        ));
    }
    public function refers($username)
    {
        $page_title = __("Refer Users");
        $user = User::where("username",$username)->first();
        if(!$user) return back()->with(['error' =>  [__("Oops! User doesn't exists")]]);
        $refer_users = $user->referUsers()->paginate(10);
        return view('admin.sections.user-care.refer-users', compact(
            'refer_users',
            'page_title',
        ));
    }
    public function mailLogs($username) {
        $page_title = __("User Email Logs");
        $user = User::where("username",$username)->first();
        if(!$user) return back()->with(['error' => [__("Oops! User doesn't exists")]]);
        $logs = UserMailLog::where("user_id",$user->id)->paginate(12);
        return view('admin.sections.user-care.mail-logs',compact(
            'page_title',
            'logs',
        ));
    }

    public function loginAsMember(Request $request,$username) {
        $request->merge(['username' => $username]);
        $request->validate([
            'target'            => 'required|string|exists:users,username',
            'username'          => 'required_without:target|string|exists:users',
        ]);

        try{
            $user = User::where("username",$request->username)->first();
            Auth::guard("web")->login($user);
        }catch(Exception $e) {
            return back()->with(['error' => [$e->getMessage()]]);
        }
        return redirect()->intended(route('user.dashboard'));
    }

    public function kycDetails($username) {
        $user = User::where("username",$username)->first();
        if(!$user) return back()->with(['error' =>  [__("Oops! User doesn't exists")]]);

        $page_title = __("KYC Profile");
        return view('admin.sections.user-care.kyc-details',compact("page_title","user"));
    }

    public function kycApprove(Request $request, $username) {
        $request->merge(['username' => $username]);
        $request->validate([
            'target'        => "required|exists:users,username",
            'username'      => "required_without:target|exists:users,username",
        ]);
        $user = User::where('username',$request->target)->orWhere('username',$request->username)->first();
        if($user->kyc_verified == GlobalConst::VERIFIED) return back()->with(['warning' =>  [__('User already KYC verified')]]);
        if($user->kyc == null) return back()->with(['error' => [__('User KYC information not found')]]);

        try{
            $user->update([
                'kyc_verified'  => GlobalConst::APPROVED,
            ]);
        }catch(Exception $e) {
            $user->update([
                'kyc_verified'  => GlobalConst::PENDING,
            ]);
            return back()->with(['error' => [__('Something went wrong! Please try again')]]);
        }
        return back()->with(['success' => [__('User KYC successfully approved')]]);
    }

    public function kycReject(Request $request, $username) {
        $request->validate([
            'target'        => "required|exists:users,username",
            'reason'        => "required|string|max:500"
        ]);
        $user = User::where("username",$request->target)->first();
        if(!$user) return back()->with(['error' => [__("Oops! User doesn't exists")]]);
        if($user->kyc == null) return back()->with(['error' => [__('User KYC information not found')]]);

        try{
            $user->update([
                'kyc_verified'  => GlobalConst::REJECTED,
            ]);
            $user->kyc->update([
                'reject_reason' => $request->reason,
            ]);
        }catch(Exception $e) {
            $user->update([
                'kyc_verified'  => GlobalConst::PENDING,
            ]);
            $user->kyc->update([
                'reject_reason' => null,
            ]);

            return back()->with(['error' =>  [__('Something went wrong! Please try again')]]);
        }

        return back()->with(['success' => [__('User KYC information is rejected')]]);
    }


    public function search(Request $request) {
        $validator = Validator::make($request->all(),[
            'text'  => 'required|string',
        ]);

        if($validator->fails()) {
            $error = ['error' => $validator->errors()];
            return Response::error($error,null,400);
        }

        $validated = $validator->validate();
        $users = User::search($validated['text'])->limit(10)->get();
        return view('admin.components.search.user-search',compact(
            'users',
        ));
    }
    public function walletBalanceUpdate(Request $request,$username) {
        $validator = Validator::make($request->all(),[
            'type'      => "required|string|in:add,subtract",
            'wallet'    => "required|numeric|exists:user_wallets,id",
            'amount'    => "required|numeric",
            'remark'    => "nullable|string|max:200",
        ]);

        if($validator->fails()) {
            return back()->withErrors($validator)->withInput()->with('modal','wallet-balance-update-modal');
        }
        $validated = $validator->validate();
        $user_wallet = UserWallet::whereHas('user',function($q) use ($username){
            $q->where('username',$username);
        })->find($validated['wallet']);
        if(!$user_wallet) return back()->with(['error' => [__('User wallet not found!')]]);
        DB::beginTransaction();
        try{
            $user_wallet_balance = 0;
            $action_type = "";
            switch($validated['type']){
                case "add":
                    $action_type = "Added";
                    $user_wallet_balance = $user_wallet->balance + $validated['amount'];
                    DB::table($user_wallet->getTable())->where('id',$user_wallet->id)->increment('balance',$validated['amount']);
                    break;
                case "subtract":
                    $action_type = "Subtract";
                    if($user_wallet->balance >= $validated['amount']) {
                        $user_wallet_balance = $user_wallet->balance - $validated['amount'];
                        DB::table($user_wallet->getTable())->where('id',$user_wallet->id)->decrement('balance',$validated['amount']);
                    }else {
                        return back()->with(['error' => [__('User do not have sufficient balance')]]);
                    }
                    break;
            }
            DB::table("transactions")->insertGetId([
                'type'              => PaymentGatewayConst::TYPEADDSUBTRACTBALANCE,
                'trx_id'            => generate_unique_string("transactions","trx_id",16),
                'user_type'         => GlobalConst::USER,
                'user_id'           => $user_wallet->user->id,
                'wallet_id'         => $user_wallet->id,
                'request_amount'    => $validated['amount'],
                'request_currency'  => $user_wallet->currency->code,
                'exchange_rate'     => 1,
                'percent_charge'    => 0,
                'fixed_charge'      => 0,
                'total_charge'      => 0,
                'total_payable'     => $validated['amount'],
                'receive_amount'    => $validated['amount'],
                'receiver_type'     => GlobalConst::USER,
                'receiver_id'       => $user_wallet->user->id,
                'available_balance' => $user_wallet_balance,
                'payment_currency'  => $user_wallet->currency->code,
                'remark'            => $validated['remark'],
                'status'            => PaymentGatewayConst::STATUSSUCCESS,
                'created_at'        => now(),
            ]);
            // Send Mail to User
            $from_or_to = ($action_type == "Added") ? "to" : "from";
            $data['message']  = __("Your wallet balance updated by ") . auth()->user()->getRolesString() .". " . $action_type . " (" . $validated['amount'] . $user_wallet->currency->code . ")  ". $from_or_to . " " . $user_wallet->currency->code . __(" Wallet Balance");
            $user_wallet->user->notify(new MessageNotification($data));
            DB::commit();
        }catch(Exception $e) {
            DB::rollBack();
            return back()->with(['error' =>[__('Transaction failed! '). $e->getMessage()]]);
        }
        return back()->with(['success' => [__('Transaction success')]]);
    }
}
