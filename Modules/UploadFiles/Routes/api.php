<?php

use Illuminate\Http\Request;

Route::group(['prefix' => 'attach', 'middleware' => 'auth:api'], function () {

    //install Files permissions
    Route::get('install' ,'FilesController@install_file');

    /* File Routes */
    /* Upload array of files to specific course segment */
    Route::post('uploadFile', 'FilesController@store');//->name('uploadFile')->middleware('permission:file/add');

    /* Update specific file */
    Route::post('updateFile', 'FilesController@update')->name('updateFile')->middleware('permission:file/update');

    /* Delete specific file */
    Route::post('deleteFile', 'FilesController@destroy')->name('deleteFile')->middleware('permission:file/delete');

    /* Toggle Visibility of specific file */
    Route::post('toggleFileVisibility', 'FilesController@toggleVisibility')->name('toggleFileVisibility')->middleware('permission:file/toggle');
    /* Media Routes */
    /* Upload array of Media to specific course segment */
    Route::post('uploadMedia', 'MediaController@store')->name('uploadMedia')->middleware('permission:media/add');
    /* Update specific Media */
    Route::post('updateMedia', 'MediaController@update')->name('updateMedia')->middleware('permission:media/update');

    /* Delete specific Media */
    Route::post('deleteMedia', 'MediaController@destroy')->name('deleteMedia')->middleware('permission:media/delete');

    /* Toggle Visibility of specific Media */
    Route::post('toggleMediaVisibility', 'MediaController@toggleVisibility')->name('toggleMediaVisibility')->middleware('permission:media/toggle');

    /* Attach link as media */
    Route::post('storeMediaLink', 'MediaController@storeMediaLink')->name('storeMediaLink')->middleware('permission:link/add');

    /* update link as media */
    Route::post('updateMediaLink', 'MediaController@updateMediaLink')->name('updateMediaLink')->middleware('permission:link/update');

    /* Get All files and Media assigned to specific course segment */
    Route::get('getFileMedia', 'FilesController@show')->name('getFileMedia')->middleware('permission:file-media/get');

    /* sortLessonFile */
    Route::post('sortLessonFile', 'FilesController@sortLessonFile')->name('sortLessonFile')->middleware('permission:file/sort');

    /* sortLessonMedia */
    Route::post('sortLessonMedia', 'MediaController@sortLessonMedia')->name('sortLessonMedia')->middleware('permission:media/sort');
});
