<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GlobalController;
use App\Http\Controllers\User\KycController;
use App\Http\Controllers\User\StatusController;
use App\Http\Controllers\User\WalletController;
use App\Http\Controllers\User\HistoryController;
use App\Http\Controllers\User\ProfileController;
use App\Http\Controllers\User\AddMoneyController;
use App\Http\Controllers\User\SecurityController;
use App\Http\Controllers\User\DashboardController;
use App\Http\Controllers\User\InvestmentController;
use App\Http\Controllers\User\WithdrawalController;
use App\Http\Controllers\User\SupportTicketController;
use App\Http\Controllers\User\TransferMoneyController;

Route::prefix("user")->name("user.")->group(function () {
    Route::controller(DashboardController::class)->group(function () {
        Route::get('dashboard', 'index')->name('dashboard');
        Route::post('logout', 'logout')->name('logout');
    });

    Route::controller(ProfileController::class)->prefix("profile")->name("profile.")->group(function () {
        Route::get('/', 'index')->name('index');
        Route::put('password/update', 'passwordUpdate')->name('password.update')->middleware(['app.mode']);
        Route::put('update', 'update')->name('update')->middleware(['app.mode']);
        Route::post('delete-account/{id}', 'deleteAccount')->name('delete')->middleware(['app.mode']);
    });

    Route::controller(SupportTicketController::class)->prefix("prefix")->name("support.ticket.")->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('create', 'create')->name('create');
        Route::post('store', 'store')->name('store');
        Route::get('conversation/{encrypt_id}', 'conversation')->name('conversation');
        Route::post('message/send', 'messageSend')->name('messaage.send');
        Route::post('solve', 'solve')->name('solve');
    });

    Route::get('/user/get-wallet-balance', [WalletController::class, 'getWalletBalance'])->name('get.wallet.balance');

    // Add Money
    Route::controller(AddMoneyController::class)->middleware(['kyc.verification.guard'])->prefix('add-money')->name('add.money.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('submit', 'submit')->name('submit');
        Route::get('success/response/{gateway}', 'success')->name('payment.success');
        Route::get("cancel/response/{gateway}", 'cancel')->name('payment.cancel');
        Route::post("callback/response/{gateway}", 'callback')->name('payment.callback')->withoutMiddleware(['web', 'auth', 'verification.guard', 'user.google.two.factor']);
        // POST Route For Unauthenticated Request
        Route::post('success/response/{gateway}', 'postSuccess')->name('payment.success')->withoutMiddleware(['auth', 'verification.guard', 'user.google.two.factor']);
        Route::post('cancel/response/{gateway}', 'postCancel')->name('payment.cancel')->withoutMiddleware(['auth', 'verification.guard', 'user.google.two.factor']);

        // redirect with HTML form route
        Route::get('redirect/form/{gateway}', 'redirectUsingHTMLForm')->name('payment.redirect.form')->withoutMiddleware(['auth', 'verification.guard', 'user.google.two.factor']);

        //redirect with Btn Pay
        Route::get('redirect/btn/checkout/{gateway}', 'redirectBtnPay')->name('payment.btn.pay')->withoutMiddleware(['auth', 'verification.guard', 'user.google.two.factor']);
        Route::get('manual/{token}', 'showManualForm')->name('manual.form');
        Route::post('manual/submit/{token}', 'manualSubmit')->name('manual.submit');

        Route::prefix('payment')->name('payment.')->group(function () {
            Route::get('crypto/address/{trx_id}', 'cryptoPaymentAddress')->name('crypto.address');
            Route::post('crypto/confirm/{trx_id}', 'cryptoPaymentConfirm')->name('crypto.confirm');
        });
    });
    //Transfer Money
    Route::controller(TransferMoneyController::class)->middleware(['kyc.verification.guard'])->prefix('transfer-money')->name('transfer.money.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('confirmed', 'confirmed')->name('confirmed');
    });
    //Withdraw Money
    Route::controller(WithdrawalController::class)->middleware(['kyc.verification.guard'])->prefix('withdraw-money')->name('withdraw.money.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('submit', 'submit')->name('submit');
        Route::get('instruction/{token}', 'instruction')->name('instruction');
        Route::post('instruction/submit/{token}', 'instructionSubmit')->name('instruction.submit');
    });

    Route::controller(InvestmentController::class)->middleware(['kyc.verification.guard'])->prefix('investment')->name('investment.')->group(function () {
        Route::get('gold-store', 'goldStoreIndex')->name('gold.store');
        Route::get('checkout/{slug}', 'checkout')->name('checkout');
        Route::post('gold/order/{slug}', 'order')->name('gold.order');
        Route::get('investment-plan', 'investmentPlanIndex')->name('plan');
        Route::post('purchase/{invest_plan?}', 'purchase')->name('purchase');
        Route::get('invest-history', 'investIndex')->name('invest');
    });
    //My Status
    Route::controller(StatusController::class)->prefix('status')->name('status.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('search', 'search')->name('search');
    });
    Route::controller(SecurityController::class)->prefix("security")->name('security.')->group(function () {
        Route::get('google/2fa', 'google2FA')->name('google.2fa');
        Route::post('google/2fa/status/update', 'google2FAStatusUpdate')->name('google.2fa.status.update')->middleware('app.mode');
    });

    Route::controller(KycController::class)->prefix('kyc')->name('kyc.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('submit', 'store')->name('submit');
    });
    Route::controller(HistoryController::class)->prefix("history")->name("history.")->group(function () {
        Route::get('/{slug?}', 'index')->name('transaction')->whereIn('slug', ['add-money-log', 'money-out-log', 'send-money-log']);
        Route::get('orders', 'order')->name('order');
        Route::get('profits', 'profit')->name('profit');
    });
    Route::post("info", [GlobalController::class, 'userInfo'])->middleware('auth')->name('info');
});
