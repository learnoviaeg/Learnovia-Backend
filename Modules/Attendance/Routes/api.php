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

Route::middleware('auth:api')->get('/attendance', function (Request $request) {
    return $request->user();
});
Route::post('get-users-in-attendence', 'AttendanceController@get_all_users_in_attendence')->middleware('auth:api');
Route::post('get-users-in-session', 'AttendanceController@get_all_users_in_session')->middleware('auth:api');
Route::post('get-users-taken-in-session', 'AttendanceController@get_all_taken_users_in_session')->middleware('auth:api');
