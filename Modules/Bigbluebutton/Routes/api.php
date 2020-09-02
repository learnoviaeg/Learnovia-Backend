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

Route::group(['prefix' => 'bigbluebutton', 'middleware' => 'auth:api'], function () {

    //install Bigbluebutton Routes
    Route::get('install','BigbluebuttonController@install');

    //Bigbluebutton Routes
    Route::post('create', 'BigbluebuttonController@create')->middleware('permission:bigbluebutton/create');
    Route::get('join', 'BigbluebuttonController@join')->middleware('permission:bigbluebutton/join');
    Route::get('get', 'BigbluebuttonController@get')->name('getmeeting')->middleware('permission:bigbluebutton/get');
    Route::get('get-all', 'BigbluebuttonController@get_meetings')->name('getmeetings')->middleware('permission:bigbluebutton/get-all');
    Route::get('getRecord', 'BigbluebuttonController@getRecord')->name('getRecord')->middleware('permission:bigbluebutton/getRecord');
    Route::get('delete', 'BigbluebuttonController@destroy')->name('delete')->middleware('permission:bigbluebutton/delete');
    Route::get('toggle', 'BigbluebuttonController@toggle')->name('toggle')->middleware('permission:bigbluebutton/toggle');
    Route::get('attendance', 'BigbluebuttonController@takeattendance')->name('takeattendance')->middleware('permission:bigbluebutton/attendance');
    Route::get('get-attendance', 'BigbluebuttonController@viewAttendence')->name('get-attendance')->middleware('permission:bigbluebutton/get-attendance');
    Route::get('export', 'BigbluebuttonController@export')->name('exportbbbattendence')->middleware('permission:bigbluebutton/export');
    Route::get('meetinginfo', 'BigbluebuttonController@getmeetingInfo')->name('getmeetingInfo');
    Route::get('clear', 'BigbluebuttonController@clear')->name('clear');

});
