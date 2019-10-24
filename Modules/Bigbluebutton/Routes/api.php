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

Route::middleware('auth:api')->get('/bigbluebutton', function (Request $request) {
    return $request->user();
});

Route::group(['prefix' => 'bigbluebutton', 'middleware' => 'auth:api'], function () {

    //install Bigbluebutton Routes
    Route::get('install','BigbluebuttonController@install');

    //Bigbluebutton Routes
    Route::get('createmeeting', 'BigbluebuttonController@create')->middleware('permission:bigbluebutton/create');
    Route::get('joinmeeting', 'BigbluebuttonController@join')->middleware('permission:bigbluebutton/join');
    Route::get('getmeeting', 'BigbluebuttonController@get')->name('getmeeting')->middleware('permission:bigbluebutton/get');
});
