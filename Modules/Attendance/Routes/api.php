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
Route::group(['prefix' => 'attendance', 'middleware' => 'auth:api'], function () {
    Route::get('install','AttendanceController@install');
    Route::post('add', 'AttendanceController@create')->name('addattendance')->middleware('permission:add');
    Route::post('add-log', 'AttendanceLogController@create')->name('addattendancelog')->middleware('permission:add-log');
    Route::post('get-users-in-attendence', 'AttendanceController@get_all_users_in_attendence')->name('getusersinattendence')->middleware('permission:get-users-in-attendence');
    Route::post('get-users-in-session', 'AttendanceController@get_all_users_in_session')->name('getusersinsession')->middleware('permission:get-users-in-session');
    Route::post('get-users-taken-in-session', 'AttendanceController@get_all_taken_users_in_session')->name('getuserstakeninsession')->middleware('permission:get-users-taken-in-session');
    Route::post('add-session', 'AttendanceController@createSession');//->name('getuserstakeninsession')->middleware('permission:get-users-taken-in-session');
});
Route::group(['prefix' => 'status', 'middleware' => 'auth:api'], function () {
    Route::post('AddStatus','StatusController@Add');
    Route::post('UpdateStatus','StatusController@Update');
    Route::post('DeleteStatus','StatusController@Delete');
});