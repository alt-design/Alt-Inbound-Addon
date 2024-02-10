<?php
use Illuminate\Support\Facades\Route;

Route::group(['middleware' => ['statamic.cp.authenticated'], 'namespace' => 'AltDesign\AltBlocker\Http\Controllers'], function() {
    // Settings
    Route::get('/alt-design/alt-blocker/', 'AltBlockerController@index')->name('alt-blocker.index');
    Route::post('/alt-design/alt-blocker/', 'AltBlockerController@create')->name('alt-blocker.create');

    Route::post('/alt-design/alt-blocker/blacklist', 'AltBlockerController@blacklist')->name('alt-blocker.blacklist');
    Route::post('/alt-design/alt-blocker/custom-view', 'AltBlockerController@customView')->name('alt-blocker.custom-view');
    Route::post('/alt-design/alt-blocker/delete', 'AltBlockerController@delete')->name('alt-blocker.delete');
    Route::get('/alt-design/alt-blocker/export', 'AltBlockerController@export')->name('alt-blocker.export');
    Route::post('/alt-design/alt-blocker/import', 'AltBlockerController@import')->name('alt-blocker.import');

});

