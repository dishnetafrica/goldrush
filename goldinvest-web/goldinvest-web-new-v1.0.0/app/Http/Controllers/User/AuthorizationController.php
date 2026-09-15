<?php

namespace App\Http\Controllers\User;

use Exception;
use App\Models\UserKycData;
use Illuminate\Http\Request;
use App\Constants\GlobalConst;
use App\Models\Admin\SetupKyc;
use App\Models\UserAuthorization;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Traits\ControlDynamicInputFields;
use Illuminate\Support\Facades\Validator;
use App\Providers\Admin\BasicSettingsProvider;
use Illuminate\Validation\ValidationException;
use App\Notifications\User\Auth\SendAuthorizationCode;

class AuthorizationController extends Controller
{
    use ControlDynamicInputFields;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function showMailFrom($token)
    {
        $page_title = setPageTitle(__("Mail Authorization"));
        return view('user.auth.authorize.verify-mail',compact("page_title","token"));
    }

    /**
     * Verify authorization code.
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function mailVerify(Request $request,$token)
    {
        $request->merge(['token' => $token]);
        $request->validate([
            'token'     => "required|string|exists:user_authorizations,token",
            'code'      => "required",
        ]);
        $code = implode($request->code);
        $otp_exp_sec = BasicSettingsProvider::get()->otp_exp_seconds ?? GlobalConst::DEFAULT_TOKEN_EXP_SEC;
        $auth_column = UserAuthorization::where("token",$request->token)->where("code",$code)->first();
        if (!$auth_column) {
            return redirect()->back()->with(['error' => [__('Invalid otp code')]]);
        }
        if($auth_column->created_at->addSeconds($otp_exp_sec) < now()) {
            $this->authLogout($request);
            return redirect()->route('user.login')->with(['error' => [__('Session expired. Please try again')]]);
        }

        try{
            $auth_column->user->update([
                'email_verified'    => true,
            ]);
            $auth_column->delete();
        }catch(Exception $e) {
            $this->authLogout($request);
            return redirect()->route('user.login')->with(['error' => [__('Something went wrong! Please try again')]]);
        }

        return redirect()->intended(route("user.dashboard"))->with(['success' =>[__('Account successfully verified')]]);
    }

    public function authLogout(Request $request) {
        auth()->guard("web")->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }
    public function mailResendToken($token)
    {
        $user_authorize = UserAuthorization::where("token", $token)->first();
        $resend_code = generate_random_code();
        try {
            $user_authorize->update([
                'code'          => $resend_code,
                'created_at'    => now(),
            ]);
            $data = $user_authorize->toArray();
            $user_authorize->user->notify(new SendAuthorizationCode((object) $data));
        } catch (Exception $e) {
            throw ValidationException::withMessages([
                'code'      =>__("Something went wrong! Please try again."),
            ]);
        }
        return redirect()->route('user.authorize.mail', $token)->with(['success' =>  [__('Mail OTP Resend Success!')]]);
    }
    public function showGoogle2FAForm() {
        $page_title =  __("Authorize Google Two Factor");
        return view('user.auth.authorize.verify-google-2fa',compact('page_title'));
    }

    public function google2FASubmit(Request $request) {

        $request->validate([
            'code'    => "required|numeric",
        ]);
        $code = $request->code;

        $user = auth()->user();

        if(!$user->two_factor_secret) {
            return back()->with(['warning' => [__('Your secret key not stored properly. Please contact with system administrator')]]);
        }
        if(google_2fa_verify($user->two_factor_secret,$code)) {

            $user->update([
                'two_factor_verified'   => true,
            ]);

            return redirect()->intended(route('user.dashboard'));
        }

        return back()->with(['warning' => [__('Failed to login. Please try again')]]);
    }
}
