<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Frontend\IndexController;

Route::name('frontend.')->group(function() {
    Route::controller(IndexController::class)->group(function() {
        Route::get('/', 'index')->name('index')->middleware('check_page:home');
        Route::get('/about', 'aboutView')->name('about')->middleware('check_page:about');
        Route::get('/contact', 'contactView')->name('contact')->middleware('check_page:contact');
        Route::get('/plan', 'planView')->name('plan')->middleware('check_page:plan'); 
        Route::get('/services', 'servicesView')->name('services')->middleware('check_page:services');
        Route::get('/web/journal', 'webJournalView')->name('web.journal')->middleware('check_page:web-journal');
        Route::get('/journal-details/{id}/{slug}','journalDetailsView')->name('journal.details');
        Route::get('gold/store', 'goldStore')->name('gold.store');
        Route::post("subscribe","subscribe")->name("subscribe");
        Route::post("contact/message/send","contactMessageSend")->name("contact.message.send");
        Route::get('link/{slug}','usefulLink')->name('useful.links');
        Route::post('languages/switch','languageSwitch')->name('languages.switch');
    });
});
