<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\User\ProfitLogController;
use App\Http\Controllers\Api\V1\User\StatusController;
use App\Http\Controllers\Api\V1\User\ProfileController;
use App\Http\Controllers\Api\V1\User\AddMoneyController;
use App\Http\Controllers\Api\V1\User\WithdrawController;
use App\Http\Controllers\Api\V1\User\DashboardController;
use App\Http\Controllers\Api\V1\User\InvestPlanController;
use App\Http\Controllers\Api\V1\User\TransactionController;
use App\Http\Controllers\Api\V1\User\MoneyTransferController;

Route::prefix("user")->name("api.user.")->group(function(){

    Route::controller(ProfileController::class)->prefix('profile')->group(function(){
        Route::get('info','profileInfo');
        Route::get('states','getStates');
        Route::get('cities','getCities');
        Route::post('info/update','profileInfoUpdate')->middleware(['app.mode']);
        Route::post('password/update','profilePasswordUpdate')->middleware(['app.mode']);
        Route::post('delete-account','deleteProfile')->middleware('app.mode');
    });

    // Logout Route
    Route::post('logout',[ProfileController::class,'logout']);

    // // Add Money Routes
    Route::controller(AddMoneyController::class)->prefix("add-money")->name('add.money.')->group(function(){
        Route::get("payment-gateways","getPaymentGateways");

        // Submit with automatic gateway
        Route::post("automatic/submit","automaticSubmit")->middleware(['kyc.verification.guard']);

        // Automatic Gateway Response Routes
        Route::get('success/response/{gateway}','success')->withoutMiddleware(['auth:api'])->name("payment.success");
        Route::get("cancel/response/{gateway}",'cancel')->withoutMiddleware(['auth:api'])->name("payment.cancel");
        // POST Route For Unauthenticated Request
        Route::post('success/response/{gateway}', 'postSuccess')->name('payment.success')->withoutMiddleware(['auth:api']);
        Route::post('cancel/response/{gateway}', 'postCancel')->name('payment.cancel')->withoutMiddleware(['auth:api']);

        //redirect with Btn Pay
        Route::get('redirect/btn/checkout/{gateway}', 'redirectBtnPay')->name('payment.btn.pay')->withoutMiddleware(['auth:api']);
        Route::get('manual/input-fields','manualInputFields');

        // Submit with manual gateway
        Route::post("manual/submit","manualSubmit");

        // Automatic gateway additional fields
        Route::get('payment-gateway/additional-fields','gatewayAdditionalFields');

        Route::prefix('payment')->name('payment.')->group(function() {
            Route::post('crypto/confirm/{trx_id}','cryptoPaymentConfirm')->name('crypto.confirm');
        });

    });
    //Transfer Money Routes
    Route::controller(MoneyTransferController::class)->prefix("money-transfer")->name('money.transfer.')->group(function(){
        Route::get("wallets", "getWallets");
        Route::post("submit", "submit")->middleware(['kyc.verification.guard']);
    });
     //Withdraw Money Routes
     Route::controller(WithdrawController::class)->prefix("withdraw")->name('withdraw.')->group(function(){
        Route::get("wallet-gateways", "walletGateways");
        Route::get("gateway/input-fields", "gatewayInputFields");
        Route::post("submit", "submit")->middleware(['kyc.verification.guard']);
    });
    // // Dashboard, Notification,
    Route::controller(DashboardController::class)->group(function(){
        Route::get("dashboard","dashboard");
        Route::get("notifications","notifications");
    });
    Route::controller(InvestPlanController::class)->prefix("invest-plan")->name('invest.plan.')->group(function(){
        Route::get("getPlans", "getPlans");
        Route::post("purchase", "purchase")->middleware(['kyc.verification.guard']);
        Route::get("my-investments", "myInvestments");
    });
    Route::controller(OrderController::class)->prefix("order")->name('order.')->group(function(){
        Route::get("getGolds", "getGolds");
        Route::get("checkout", "checkout");
        Route::post("submit", "order")->middleware(['kyc.verification.guard']);
        Route::get("log", "orderLog");
    });
    // Transaction
    Route::controller(TransactionController::class)->prefix("transaction")->group(function(){
        Route::get("log","log");
    });

    //My Status
    Route::controller(StatusController::class)->prefix("status")->group(function () {
        Route::get("info", "statusInfo");
    });

    //profit log
    Route::controller(ProfitLogController::class)->prefix("profit")->group(function(){
        Route::get("log","profitLog");
    });

});
