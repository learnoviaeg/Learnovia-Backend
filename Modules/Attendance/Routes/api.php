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
    Route::post('add-session', 'AttendanceSessionController@createSession')->name('addsession')->middleware('permission:attendance/add-session');
    Route::get('get-sessions', 'AttendanceController@GetAllSessionDay')->name('getsession')->middleware('permission:attendance/get-sessions');
    Route::get('attend-report', 'AttendanceController@Attendance_Report')->name('attendancereport')->middleware('permission:attendance/attend-report');
    Route::post('update-session', 'AttendanceController@update_session')->name('updatesession')->middleware('permission:attendance/update-session');
    Route::post('delete-session', 'AttendanceController@delete_session')->name('deletesession')->middleware('permission:attendance/delete-session');
    Route::get('get-session', 'AttendanceController@get_session_byID')->name('getsessionbyid')->middleware('permission:attendance/get-session');
    Route::get('get-all-sessions', 'AttendanceController@getAllSessions')->name('getallsession')->middleware('permission:attendance/get-all-sessions');
});
