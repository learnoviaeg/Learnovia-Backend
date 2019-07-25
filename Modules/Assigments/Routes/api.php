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

Route::middleware('auth:api')->get('/assigments', function (Request $request) {
    return $request->user();
});
Route::post('createAssigment','AssigmentsController@createAssigment');
Route::post('ubdateAssigment','AssigmentsController@ubdateAssigment');
Route::post('submitAssigment','AssigmentsController@submitAssigment');
Route::post('gradeAssigment','AssigmentsController@gradeAssigment');
Route::post('override','AssigmentsController@override');
Route::post('deleteAssigment','AssigmentsController@deleteAssigment');






