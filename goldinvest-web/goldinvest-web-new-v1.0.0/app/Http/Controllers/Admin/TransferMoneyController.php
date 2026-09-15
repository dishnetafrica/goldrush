<?php

namespace App\Http\Controllers\Admin;

use App\Models\Transaction;

use Illuminate\Http\Request;
use App\Http\Helpers\Response;
use App\Http\Controllers\Controller;
use App\Constants\PaymentGatewayConst;
use Illuminate\Support\Facades\Validator;

class TransferMoneyController extends Controller
{
    public function index()
    {
        $page_title = __("Transfer Money");
        $transactions = Transaction::with(
            'user:id,email,username,full_mobile,image,firstname,lastname',
            'receiver_info:id,email,username'
        )->where('type', PaymentGatewayConst::TYPETRANSFERMONEY)->paginate(20);
        return view('admin.sections.transfer-money.index',compact(
            'page_title',
            'transactions'
        ));
    }

    public function transferMoneyDetails($id){
        $transaction = Transaction::where('id',$id)->with(
            'user:id,firstname,lastname,image,email,username,full_mobile',
            'receiver_info:id,email,username'
        )->where('type', PaymentGatewayConst::TYPETRANSFERMONEY)->first();
        $page_title = __('Transaction Details');
        return view('admin.sections.transfer-money.details', compact(
            'page_title',
            'transaction'
        ));
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
        $transactions = Transaction::moneyTransfer()->search($validated['text'])->limit(10)->get();
        return view('admin.components.search.transfer-money.transaction-search',compact(
            'transactions',
        ));
    }
}
