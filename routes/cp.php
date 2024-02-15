<?php
use Illuminate\Support\Facades\Route;

Route::group(['middleware' => ['statamic.cp.authenticated'], 'namespace' => 'AltDesign\AltInbound\Http\Controllers'], function() {
    // Settings
    Route::get('/alt-design/alt-inbound/', 'AltInboundController@index')->name('alt-inbound.index');
    Route::post('/alt-design/alt-inbound/', 'AltInboundController@create')->name('alt-inbound.create');

    Route::post('/alt-design/alt-inbound/blacklist', 'AltInboundController@blacklist')->name('alt-inbound.blacklist');
    Route::post('/alt-design/alt-inbound/custom-view', 'AltInboundController@customView')->name('alt-inbound.custom-view');
    Route::post('/alt-design/alt-inbound/delete', 'AltInboundController@delete')->name('alt-inbound.delete');
    Route::get('/alt-design/alt-inbound/export', 'AltInboundController@export')->name('alt-inbound.export');
    Route::post('/alt-design/alt-inbound/import', 'AltInboundController@import')->name('alt-inbound.import');

});

