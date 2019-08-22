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

Route::group(['prefix' => 'assignment', 'middleware' => 'auth:api'], function () {

    //install Assignments Routes
    Route::get('install','AssigmentsController@install_Assignment');

    //Assignment Routes
    Route::post('create', 'AssigmentsController@createAssigment')->middleware('permission:assignment/add');
    Route::post('update', 'AssigmentsController@updateAssigment')->middleware('permission:assignment/update');
    Route::post('submit', 'AssigmentsController@submitAssigment')->middleware('permission:assignment/submit');
    Route::post('grade', 'AssigmentsController@gradeAssigment')->middleware('permission:assignment/grade');
    Route::post('override', 'AssigmentsController@override')->middleware('permission:assignment/override');
    Route::post('delete', 'AssigmentsController@deleteAssigment')->middleware('permission:assignment/delete');
    Route::get('GetAssignment','AssigmentsController@GetAssignment')->name('getAssignment')->middleware('permission:assignment/get');
    Route::post('toggleVisiblity', 'AssigmentsController@toggleAssignmentVisibity');

    Route::get('getAllAssigment', 'AssigmentsController@getAllAssigment');

});
