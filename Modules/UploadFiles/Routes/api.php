<?php

use Illuminate\Http\Request;

Route::group([
    'prefix' => 'auth'
], function () {

    Route::group([
      'middleware' => 'auth:api'
    ], function() {
        Route::post('uploadFile', 'FilesController@store')->name('uploadFile');
        Route::post('updateFile', 'FilesController@update')->name('updateFile');
        Route::post('deleteFile', 'FilesController@destroy')->name('deleteFile');
        Route::post('toggleFileVisibility', 'FilesController@toggleVisibility')->name('toggleFileVisibility');

        Route::post('uploadMedia', 'MediaController@store')->name('uploadMedia');
        Route::post('updateMedia', 'MediaController@update')->name('updateMedia');
        Route::post('deleteMedia', 'MediaController@destroy')->name('deleteMedia');
        Route::post('deleteFile', 'MediaController@destroy')->name('deleteFile');
        Route::post('toggleMediaVisibility', 'MediaController@toggleVisibility')->name('toggleMediaVisibility');

        Route::get('getFileMedia', 'FilesController@show')->name('getFileMedia');

    });
});


?>
