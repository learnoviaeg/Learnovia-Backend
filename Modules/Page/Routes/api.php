<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::group(['prefix' => 'page', 'middleware' => ['auth:api','LastAction']] , function(){

    Route::get('install' , 'PageController@install')->name('installPage');

    Route::post('add' , 'PageController@add')->name('addPage')->middleware('permission:page/add');
    Route::post('update' , 'PageController@update')->name('updatePage')->middleware('permission:page/update');
    Route::post('delete' , 'PageController@destroy')->name('deletePage')->middleware('permission:page/delete');
    Route::post('toggle' , 'PageController@togglePageVisibity')->name('togglePage')->middleware('permission:page/toggle');
    Route::post('link-lesson' , 'PageController@linkpagelesson')->name('linklessonPage')->middleware('permission:page/link-lesson');
    Route::get('get' , 'PageController@get')->name('getPage')->middleware(['permission:page/get' , 'ParentCheck']);
});
