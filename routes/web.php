<?php
use Illuminate\Support\Facades\Route;

Route::group(['middleware' => ['web'], 'namespace' => 'AltDesign\AltBlocker\Http\Controllers'], function() {
    Route::get('/alt-design/alt-inbound/blocked', 'AltInboundController@block')->name('alt-inbound.block');
});
