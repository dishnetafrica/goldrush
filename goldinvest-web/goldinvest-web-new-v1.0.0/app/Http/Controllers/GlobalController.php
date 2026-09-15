<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Helpers\Response;
use Illuminate\Support\Carbon;
use Jenssegers\Agent\Facades\Agent;
use Illuminate\Support\Facades\Validator;

class GlobalController extends Controller
{

    /**
     * Funtion for get state under a country
     * @param country_id
     * @return json $state list
     */
    public function getStates(Request $request) {
        $request->validate([
            'country_id' => 'required|integer',
        ]);
        $country_id = $request->country_id;
        // Get All States From Country
        $country_states = get_country_states($country_id);
        return response()->json($country_states,200);
    }


    public function getCities(Request $request) {
        $request->validate([
            'state_id' => 'required|integer',
        ]);

        $state_id = $request->state_id;
        $state_cities = get_state_cities($state_id);

        return response()->json($state_cities,200);
        // return $state_id;
    }


    public function getCountries(Request $request) {
        $countries = get_all_countries();

        return response()->json($countries,200);
    }


    public function getTimezones(Request $request) {
        $timeZones = get_all_timezones();

        return response()->json($timeZones,200);
    }
    public function userInfo(Request $request) {
        $validator = Validator::make($request->all(),[
            'text'      => "required|string",
        ]);
        if($validator->fails()) {
            return Response::error($validator->errors(),null,400);
        }
        $validated = $validator->validate();
        $field_name = "username";
        if(check_email($validated['text'])) {
            $field_name = "email";
        }
        try{
            $user = User::notAuth()->where($field_name,$validated['text'])->first();
        }catch(Exception $e) {
            $error = ['error' =>  [__('Something went wrong!. Please try again.')]];
            return Response::error($error,null,500);
        }
        $success = ['success' =>  [__('User available')]];
        return Response::success($success,$user,200);
    }

    public function setCookie(Request $request){
        $userAgent = $request->header('User-Agent');
        $cookie_status = $request->type;
        if($cookie_status == 'allow'){
            $response_message = __("Cookie Allowed Success");
            $expirationTime = 2147483647; //Maximum Unix timestamp.
        }else{
            $response_message = __("Cookie Declined");
            $expirationTime = Carbon::now()->addHours(24)->timestamp;// Set the expiration time to 24 hours from now.
        }
        $browser = Agent::browser();
        $platform = Agent::platform();
        $ipAddress = $request->ip();
        return response($response_message)->cookie('approval_status', $cookie_status,$expirationTime)
                                            ->cookie('user_agent', $userAgent,$expirationTime)
                                            ->cookie('ip_address', $ipAddress,$expirationTime)
                                            ->cookie('browser', $browser,$expirationTime)
                                            ->cookie('platform', $platform,$expirationTime);
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
