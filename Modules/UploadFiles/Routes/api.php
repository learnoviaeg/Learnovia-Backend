<?php

use Illuminate\Http\Request;

Route::group([
    'prefix' => 'auth'
], function () {

    Route::group([
      'middleware' => 'auth:api'
    ], function() {

        /* File Routes */
        /* Upload array of files to specific course segment */
        Route::post('uploadFile', 'FilesController@store')->name('uploadFile');

        /* Update specific file */
        Route::post('updateFile', 'FilesController@update')->name('updateFile');

        /* Delete specific file */
        Route::post('deleteFile', 'FilesController@destroy')->name('deleteFile');

        /* Toggle Visibility of specific file */
        Route::post('toggleFileVisibility', 'FilesController@toggleVisibility')->name('toggleFileVisibility');

        /* Media Routes */
        /* Upload array of Media to specific course segment */
        Route::post('uploadMedia', 'MediaController@store')->name('uploadMedia');
        /* Update specific Media */
        Route::post('updateMedia', 'MediaController@update')->name('updateMedia');

        /* Delete specific Media */
        Route::post('deleteMedia', 'MediaController@destroy')->name('deleteMedia');

        /* Attach link as media */
        Route::post('storeMediaLink', 'MediaController@storeMediaLink')->name('storeMediaLink');

        /* update link as media */
        Route::post('updateMediaLink', 'MediaController@updateMediaLink')->name('updateMediaLink');

        /* Toggle Visibility of specific Media */
        Route::post('toggleMediaVisibility', 'MediaController@toggleVisibility')->name('toggleMediaVisibility');

        /* Get All files and Media assigned to specific course segment */
        Route::get('getFileMedia', 'FilesController@show')->name('getFileMedia');

    });
});


?>
