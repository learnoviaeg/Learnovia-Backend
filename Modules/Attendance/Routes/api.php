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
Route::group(['prefix' => 'attendance', 'middleware' => 'auth:api'] , function(){


    Route::post('add' , 'AttendanceController@create');//->name('addattendance')->middleware('permission:attendance/add');
    Route::post('log/add' , 'AttendanceLogController@create');//->name('addattendance')->middleware('permission:attendance/add');


});