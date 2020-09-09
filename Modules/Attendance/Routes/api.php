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
    Route::get('install','AttendanceSessionController@install');
    Route::post('add-session', 'AttendanceSessionController@createSession')->name('addsession')->middleware('permission:attendance/add-session');
    Route::get('get-all-users', 'AttendanceSessionController@get_users_in_sessions')->name('getusersinsession')->middleware('permission:attendance/get-users-in-session');
    Route::post('take-attendnace', 'AttendanceSessionController@take_attendnace')->name('takeattendnace')->middleware('permission:attendance/add-log');
    Route::get('get', 'AttendanceSessionController@get_sessions')->name('getsessions')->middleware('permission:attendance/get-attendance');
    Route::get('delete', 'AttendanceSessionController@delete_session')->name('deletesessions')->middleware('permission:attendance/delete-attendance');
    Route::post('update', 'AttendanceSessionController@update_session')->name('editsessions')->middleware('permission:attendance/edit-attendance');
});
