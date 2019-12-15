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
    Route::post('add', 'AttendanceController@create')->name('addattendance')->middleware('permission:attendance/add');
    Route::post('add-log', 'AttendanceLogController@create')->name('addattendancelog')->middleware('permission:attendance/add-log');
    Route::post('get-users-in-attendence', 'AttendanceController@get_all_users_in_attendence')->name('getusersinattendence')->middleware('attendance/permission:get-users-in-attendence');
    Route::get('view-students-in-session', 'AttendanceController@viewstudentsinsessions')->name('getusersinsession')->middleware('permission:attendance/view-students-in-session');
    Route::post('get-users-taken-in-session', 'AttendanceController@get_all_taken_users_in_session')->name('getuserstakeninsession')->middleware('permission:attendance/get-users-taken-in-session');
    Route::post('add-session', 'AttendanceController@createSession')->name('getuserstakeninsession')->middleware('permission:attendance/get-users-taken-in-session');
    Route::group(['prefix' => 'status', 'middleware' => 'auth:api'], function () {
        Route::post('add','StatusController@Add')->middleware('attendance/status/add');
        Route::post('update','StatusController@Update')->middleware('attendance/status/update');
        Route::post('delete','StatusController@Delete')->middleware('attendance/status/delete');
    });
});
