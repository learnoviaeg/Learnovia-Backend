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
    Route::post('get-users-in-attendance', 'AttendanceController@get_all_users_in_attendence')->name('getusersinattendance')->middleware('permission:attendance/get-users-in-attendance');
    Route::get('get-attendance', 'AttendanceController@getAttendance')->name('getattendance')->middleware('permission:attendance/get-attendance');
    Route::post('delete-attendance', 'AttendanceController@deleteAttendance')->name('deleteattendance')->middleware('permission:attendance/delete-attendance');
    Route::post('edit-attendance', 'AttendanceController@editAttendance')->name('editattendance')->middleware('permission:attendance/edit-attendance');
    Route::get('view-students-in-session', 'AttendanceController@viewstudentsinsessions')->name('getusersinsession')->middleware('permission:attendance/get-users-in-session');
    Route::post('get-users-taken-in-session', 'AttendanceController@get_all_taken_users_in_session')->name('getuserstakeninsession')->middleware('permission:attendance/get-users-taken-in-session');
    Route::post('add-session', 'AttendanceController@createSession')->name('addsession')->middleware('permission:attendance/add-session');
    Route::get('get-sessions', 'AttendanceController@GetAllSessionDay')->name('getsession')->middleware('permission:attendance/get-sessions');
    Route::get('attend-report', 'AttendanceController@Attendance_Report')->name('attendancereport');//->middleware('permission:attendance/attend-report');
    Route::post('update-session', 'AttendanceController@update_session')->name('updatesession')->middleware('permission:attendance/update-session');
    Route::post('delete-session', 'AttendanceController@delete_session')->name('deletesession')->middleware('permission:attendance/delete-session');
    Route::get('get-session', 'AttendanceController@get_session_byID')->name('getsessionbyid')->middleware('permission:attendance/get-session');
    Route::get('get-all-sessions', 'AttendanceController@getAllSessions')->name('getallsession')->middleware('permission:attendance/get-all-sessions');


    Route::group(['prefix' => 'status', 'middleware' => 'auth:api'], function () {
        Route::get('get','StatusController@get')->middleware('permission:attendance/status/get');
        Route::post('add','StatusController@Add')->middleware('permission:attendance/status/add');
        Route::post('update','StatusController@Update')->middleware('permission:attendance/status/update');
        Route::post('delete','StatusController@Delete')->middleware('permission:attendance/status/delete');
    });
});
