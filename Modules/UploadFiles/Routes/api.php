<?php

use Illuminate\Http\Request;

/* File Routes */

Route::group(['prefix' => 'file', 'middleware' => ['auth:api','LastAction']], function () {

    //install Files permissions
    Route::get('install' ,'FilesController@install_file');

    Route::get('/media/get', 'FilesController@show')->name('getFileMedia')->middleware('permission:file/media/get');

    Route::post('add', 'FilesController@store')->name('uploadFile')->middleware('permission:file/add');
    Route::post('update', 'FilesController@update')->name('updateFile')->middleware('permission:file/update');
    Route::post('delete', 'FilesController@destroy')->name('deleteFile')->middleware('permission:file/delete');
    Route::post('toggle', 'FilesController@toggleVisibility')->name('toggleFileVisibility')->middleware('permission:file/toggle');
    Route::post('sort', 'FilesController@sortLessonFile')->name('sortLessonFile')->middleware('permission:file/sort');
    Route::get('get-all','FilesController@getAllFiles')->name('getAllFiles')->middleware('permission:file/get-all');
    Route::get('get','FilesController@GetFileByID')->name('GetFileByID')->middleware(['permission:file/get' , 'ParentCheck']);
    Route::post('assign','FilesController@AssignFileToLesson')->name('assignfiletolesson')->middleware('permission:file/assign');
    Route::post('assign-in-material','FilesController@AssignFileMediaPAgeLesson');//->name('assignfiletolesson')->middleware('permission:file/assign');


});

/* Media Routes */
Route::group(['prefix' => 'media', 'middleware' => ['auth:api','LastAction']], function () {
    Route::post('add', 'MediaController@store')->name('uploadMedia')->middleware('permission:media/add');
    Route::post('update', 'MediaController@update')->name('updateMedia')->middleware('permission:media/update');
    Route::post('delete', 'MediaController@destroy')->name('deleteMedia')->middleware('permission:media/delete');
    Route::post('toggle', 'MediaController@toggleVisibility')->name('toggleMediaVisibility')->middleware('permission:media/toggle');
    Route::post('sort', 'MediaController@sortLessonMedia')->name('sortLessonMedia')->middleware('permission:media/sort');
    Route::get('get-all','MediaController@getAllMedia')->name('getAllMedia')->middleware('permission:media/get-all');
    Route::get('get','MediaController@GetMediaByID')->name('GetMediaByID')->middleware(['permission:media/get' , 'ParentCheck']);
    Route::post('assign','MediaController@AssignMediaToLesson')->name('assigntolesson')->middleware('permission:media/assign');
});
