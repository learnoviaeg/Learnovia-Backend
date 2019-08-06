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

Route::group(['prefix' => 'page', 'middleware' => 'auth:api'] , function(){
    Route::get('install' , 'PageController@install')->name('installPage');
    Route::get('add' , 'PageController@store')->name('addPage')->middleware('permission:page/add');
});
