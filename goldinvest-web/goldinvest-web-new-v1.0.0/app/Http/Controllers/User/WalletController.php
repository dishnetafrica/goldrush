<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function getWalletBalance(Request $request)
    {
        $balance_type = $request->input('wallet_type', 'c_balance');
        $balance = authWalletBalance($balance_type);
        return response()->json(['balance' => $balance]);
    }
}
