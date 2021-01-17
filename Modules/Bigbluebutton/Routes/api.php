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
Route::post('callback_function', 'BigbluebuttonController@callback_function');

Route::group(['prefix' => 'bigbluebutton', 'middleware' => ['auth:api','LastAction']], function () {

    //install Bigbluebutton Routes
    Route::get('install','BigbluebuttonController@install');

    //Bigbluebutton Routes
    Route::post('create', 'BigbluebuttonController@create')->middleware('permission:bigbluebutton/create');
    Route::get('join', 'BigbluebuttonController@join')->middleware('permission:bigbluebutton/join');
    Route::get('get', 'BigbluebuttonController@get')->name('getmeeting')->middleware(['permission:bigbluebutton/get','ParentCheck']);
    Route::get('get-all', 'BigbluebuttonController@get_meetings')->name('getmeetings')->middleware('permission:bigbluebutton/get-all');
    Route::get('getRecord', 'BigbluebuttonController@getRecord')->name('getRecord')->middleware('permission:bigbluebutton/getRecord');
    Route::post('delete', 'BigbluebuttonController@destroy')->name('delete')->middleware('permission:bigbluebutton/delete');
    Route::get('toggle', 'BigbluebuttonController@toggle')->name('toggle')->middleware('permission:bigbluebutton/toggle');
    Route::get('attendance', 'BigbluebuttonController@takeattendance')->name('takeattendance')->middleware('permission:bigbluebutton/attendance');
    Route::get('get-attendance', 'BigbluebuttonController@viewAttendence')->name('get-attendance')->middleware('permission:bigbluebutton/get-attendance');
    Route::get('export', 'BigbluebuttonController@export')->name('exportbbbattendence')->middleware('permission:bigbluebutton/export');
    Route::get('start_meeting', 'BigbluebuttonController@start_meeting')->name('startmeeting')->middleware('permission:bigbluebutton/session-moderator');
    Route::get('meetinginfo', 'BigbluebuttonController@getmeetingInfo')->name('getmeetingInfo');
    Route::get('clear', 'BigbluebuttonController@clear')->name('clear');
    Route::get('create_hook', 'BigbluebuttonController@create_hook');
    Route::get('destroy_hook', 'BigbluebuttonController@destroy_hook');
    Route::get('list_hook', 'BigbluebuttonController@list_hook');
    Route::get('refresh', 'BigbluebuttonController@refresh_meetings')->middleware('permission:site/show-all-courses');
    Route::get('refresh_records', 'BigbluebuttonController@refresh_records')->middleware('permission:site/show-all-courses');
    Route::get('close_meetings', 'BigbluebuttonController@close_meetings')->middleware('permission:site/show-all-courses');
    Route::get('logs_meetings', 'BigbluebuttonController@logs_meetings')->middleware('permission:site/show-all-courses');
    Route::get('general_report', 'BigbluebuttonController@general_report')->middleware('permission:site/show-all-courses');
    Route::get('export_general', 'BigbluebuttonController@export_general_report')->name('exportgeneral')->middleware('permission:site/show-all-courses');
    Route::get('{count}', 'BigbluebuttonController@get')->middleware(['permission:bigbluebutton/get','ParentCheck']);
});
