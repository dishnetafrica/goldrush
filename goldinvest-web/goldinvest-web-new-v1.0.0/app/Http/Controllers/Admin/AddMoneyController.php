<?php

namespace App\Http\Controllers\Admin;

use Exception;
use App\Models\UserWallet;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Http\Helpers\Response;
use App\Http\Controllers\Controller;
use App\Constants\PaymentGatewayConst;
use Illuminate\Support\Facades\Validator;

class AddMoneyController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $page_title = __("All Logs");
        $transactions = Transaction::with(
            'user:id,email,username,full_mobile,image,firstname,lastname',
            'payment_gateway:id,name',
        )->where('type', PaymentGatewayConst::TYPEADDMONEY)->paginate(20);
        return view('admin.sections.add-money.index', compact(
            'page_title',
            'transactions'
        ));
    }


    /**
     * Pending Add Money Logs View.
     * @return view $pending-add-money-logs
     */
    public function pending()
    {
        $page_title = __("Pending Logs");
        $transactions = Transaction::with(
            'user:id,email,username,full_mobile,image,firstname,lastname',
            'payment_gateway:id,name',
        )->where('type', PaymentGatewayConst::TYPEADDMONEY)->where('status', 2)->paginate(20);
        return view('admin.sections.add-money.index', compact(
            'page_title',
            'transactions'
        ));
    }


    /**
     * Complete Add Money Logs View.
     * @return view $complete-add-money-logs
     */
    public function complete()
    {
        $page_title = __("Complete Logs");
        $transactions = Transaction::with(
            'user:id,email,username,full_mobile,image,firstname,lastname',
            'payment_gateway:id,name',
        )->where('type', PaymentGatewayConst::TYPEADDMONEY)->where('status', 1)->paginate(20);
        return view('admin.sections.add-money.index', compact(
            'page_title',
            'transactions'
        ));
    }

    /**
     * Canceled Add Money Logs View.
     * @return view $canceled-add-money-logs
     */
    public function canceled()
    {
        $page_title = __("Canceled Logs");
        $transactions = Transaction::with(
            'user:id,email,username,full_mobile,image,firstname,lastname',
            'payment_gateway:id,name',
        )->where('type', PaymentGatewayConst::TYPEADDMONEY)->where('status', 4)->paginate(20);
        return view('admin.sections.add-money.index', compact(
            'page_title',
            'transactions'
        ));
    }
     /**
     * This method for show details of add money
     * @return view $details-add-money-logs
     */
    public function addMoneyDetails($id){
        $transaction = Transaction::where('id',$id)->with(
            'user:id,firstname,lastname,image,email,username,full_mobile',
            'payment_gateway:id,name',
        )->where('type', PaymentGatewayConst::TYPEADDMONEY)->first();
        $page_title = __('Transaction Details');
        return view('admin.sections.add-money.details', compact(
            'page_title',
            'transaction'
        ));
    }
    /**
     * This method for approved add money
     * @method PUT
     * @param Illuminate\Http\Request $request
     * @return Illuminate\Http\Request Response
     */
    public function approved(Request $request){

        $validator = Validator::make($request->all(), [
            'target' => 'required',
        ]);

        if($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $data = Transaction::where('trx_id',$request->target)->where('status',2)->where('type', PaymentGatewayConst::TYPEADDMONEY)->first();

        try{
            //update wallet
            $userWallet = UserWallet::where('user_id',$data->user_id)->first();
            $userWallet->balance +=  $data->request_amount;
            $userWallet->save();
            //update transaction
            $data->status = 1;
            $data->available_balance =  $userWallet->balance;
            $data->save();

            return redirect()->back()->with(['success' => [__('Add Money request approved successfully')]]);
        }catch(Exception $e){
            return back()->with(['error' => [$e->getMessage()]]);
        }
    }

    /**
     * This method for reject add money
     * @method PUT
     * @param Illuminate\Http\Request $request
     * @return Illuminate\Http\Request Response
     */
    public function rejected(Request $request){
        $validator = Validator::make($request->all(),[
            'target' => 'required',
            'reason' => 'required|string',
        ]);
        if($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }
        $data = Transaction::where('trx_id',$request->target)->where('status',2)->where('type', 'add-money')->first();
        $reject['status'] = 4;
        $reject['reject_reason'] = $request->reason;
        try{
            $data->fill($reject)->save();
            return redirect()->back()->with(['success' =>  [__('Add Money request rejected successfully')]]);
        }catch(Exception $e){
            return back()->with(['error' => [$e->getMessage()]]);
        }
    }
    public function search(Request $request){
        $validator = Validator::make($request->all(),[
            'text'  => 'required|string',
        ]);

        if($validator->fails()) {
            $error = ['error' => $validator->errors()];
            return Response::error($error,null,400);
        }

        $validated = $validator->validate();
        $transactions = Transaction::addMoney()->search($validated['text'])->limit(10)->get();
        return view('admin.components.search.add-money.transaction_search',compact(
            'transactions',
        ));
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
