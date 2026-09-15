<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\User\SettingController;

// Settings
Route::controller(SettingController::class)->prefix("settings")->group(function(){
    Route::get("basic-settings","basicSettings");
    Route::get("splash-screen","splashScreen");
    Route::get("onboard-screens","onboardScreens");
    Route::get("languages","getLanguages");
});