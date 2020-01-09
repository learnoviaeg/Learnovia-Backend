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
    Route::post('get-users-in-attendence', 'AttendanceController@get_all_users_in_attendence')->name('getusersinattendence')->middleware('permission:attendance/get-users-in-attendence');
    Route::get('get-attendance', 'AttendanceController@getAttendance')->name('getattendence')->middleware('permission:attendance/get-attendence');
    Route::get('delete-attendance', 'AttendanceController@deleteAttendance')->name('deleteattendence')->middleware('permission:attendance/delete-attendance');
    Route::get('edit-attendance', 'AttendanceController@editAttendance')->name('editattendence')->middleware('permission:attendance/edit-attendence');
    Route::get('view-students-in-session', 'AttendanceController@viewstudentsinsessions')->name('getusersinsession')->middleware('permission:attendance/get-users-in-session');
    Route::post('get-users-taken-in-session', 'AttendanceController@get_all_taken_users_in_session')->name('getuserstakeninsession')->middleware('permission:attendance/get-users-taken-in-session');
    Route::post('add-session', 'AttendanceController@createSession')->name('addsession')->middleware('permission:attendance/add-session');
    Route::get('get-session', 'AttendanceController@GetAllSessionDay')->name('getsession')->middleware('permission:attendance/get-session');
    Route::get('attend-report', 'AttendanceController@Attendance_Report')->name('attendancereport')->middleware('permission:attendance/attend-report');
    Route::post('update-session', 'AttendanceController@update_session')->name('updatesession')->middleware('permission:attendance/update-session');
    Route::post('delete-session', 'AttendanceController@delete_session')->name('deletesession')->middleware('permission:attendance/delete-session');
    Route::get('get-session', 'AttendanceController@get_session_byID')->name('getsessionbyid')->middleware('permission:attendance/get-session-by-id');

    
    
    Route::group(['prefix' => 'status', 'middleware' => 'auth:api'], function () {
        Route::get('get','StatusController@get')->middleware('permission:attendance/status/get');
        Route::post('add','StatusController@Add')->middleware('permission:attendance/status/add');
        Route::post('update','StatusController@Update')->middleware('permission:attendance/status/update');
        Route::post('delete','StatusController@Delete')->middleware('permission:attendance/status/delete');
    });
});
