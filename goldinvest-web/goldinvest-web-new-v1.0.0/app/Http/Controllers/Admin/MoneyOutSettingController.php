<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\MoneyOutSetting;
use Exception;
use Illuminate\Http\Request;

class MoneyOutSettingController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $page_title = __("Money Out Setting");
        $money_out_settings = MoneyOutSetting::first();
        return view('admin.sections.settings.money-out.index',compact('page_title','money_out_settings'));
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
            'c_balance'     => 'nullable|string|boolean',
            'p_balance'     => 'nullable|string|boolean',
        ]);

        try{
            MoneyOutSetting::firstOrFail()->update($validated);

        }catch(Exception $e) {
            return back()->with(['error' => [__('Something went wrong! Please try again')]]);
        }

        return back()->with(['success' => [__('Settings updated successfully!')]]);
    }

}
