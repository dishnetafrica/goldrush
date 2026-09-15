<?php

namespace App\Http\Controllers\Admin;

use App\Constants\GlobalConst;
use App\Http\Controllers\Controller;
use App\Models\Admin\ReferralSetting;
use App\Models\ReferralLevelPackage;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ReferralSettingController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $page_title = __("Referral Settings");
        $referral_settings = ReferralSetting::first();
        $referral_level_packages = ReferralLevelPackage::orderBy('id','asc')->get();
        return view('admin.sections.settings.referral.index',compact('page_title','referral_settings','referral_level_packages'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function packageStore(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'title'                 => 'required|string|max:250',
            'refers'                => 'required|numeric|gte:0',
            'invested_amount'       => 'required|numeric|gte:0',
            'commission'            => 'required|numeric|gte:0',
        ]);

        if($validator->fails()) return back()->withErrors($validator->errors())->withInput()->with('modal','package-add');

        $validated = $validator->validate();

        $default = true;
        if(ReferralLevelPackage::where('default', GlobalConst::ACTIVE)->exists()) {
            $default = false;
        }

        try{
            ReferralLevelPackage::create([
                'title'             => $validated['title'],
                'refer_user'        => $validated['refers'],
                'invested_amount'   => $validated['invested_amount'],
                'commission'        => $validated['commission'],
                'default'           => $default,
            ]);
        }catch(Exception $e) {
            return back()->with(['error' => [__('Something went wrong! Please try again.')]]);
        }

        return back()->with(['success' => [__('New Package Created Successfully!')]]);

    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function packageUpdate(Request $request) {
        $validator = Validator::make($request->all(),[
            'target'                => 'required|numeric|exists:referral_level_packages,id',
            'edit_title'            => 'required|string|max:250',
            'edit_commission'       => 'required|numeric',
            'edit_refers'           => 'required|numeric',
            'edit_invested_amount'  => 'required|numeric',
        ]);

        if($validator->fails()) return back()->withErrors($validator)->withInput()->with('modal','edit-package');

        $validated = $validator->validate();

        $package = ReferralLevelPackage::find($validated['target']);

        try{
            $package->update([
                'title'                 => $validated['edit_title'],
                'refer_user'            => $validated['edit_refers'],
                'invested_amount'       => $validated['edit_invested_amount'],
                'commission'            => $validated['edit_commission'],
            ]);
        }catch(Exception $e) {
            return back()->with(['error' =>  [__('Something went wrong! Please try again.')]]);
        }

        return back()->with(['success' => [__('Information Updated Successfully!')]]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'bonus'             => 'required_if:status,1|numeric|gt:0',
            'wallet_type'       => 'required|in:c_balance,p_balance',
            'mail'              => 'required|boolean',
            'status'            => 'required|boolean',
        ]);

        $wallet_type = [
            'c_balance'     => GlobalConst::CURRENT_BALANCE,
            'p_balance'     => GlobalConst::PROFIT_BALANCE,
        ];

        $validated['wallet_type']   = $wallet_type[$validated['wallet_type']];

        try{
            $settings = ReferralSetting::first();
            if($settings) {
                $settings->update($validated);
            }else {
                $settings = ReferralSetting::create($validated);
            }
        }catch(Exception $e) {
            return back()->with(['error' => [__('Something went wrong! Please try again.')]]);
        }

        return back()->with(['success' => [__('Settings updated successfully!')]]);

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
