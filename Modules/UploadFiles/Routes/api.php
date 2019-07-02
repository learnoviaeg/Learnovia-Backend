<?php

use Illuminate\Http\Request;

Route::group([
    'prefix' => 'auth'
], function () {

    Route::group([
      'middleware' => 'auth:api'
    ], function() {
        Route::post('uploadFile', 'FilesController@store')->name('uploadFile');
        Route::post('uploadMedia', 'MediaController@store')->name('uploadMedia');
        Route::get('getFileMedia', 'FilesController@show')->name('getFileMedia');

    });
});


?>
