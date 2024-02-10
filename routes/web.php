<?php
use Illuminate\Support\Facades\Route;

Route::group(['middleware' => ['web'], 'namespace' => 'AltDesign\AltBlocker\Http\Controllers'], function() {
    Route::get('/alt-design/alt-blocker/blocked', 'AltBlockerController@block')->name('alt-blocker.block');
});
