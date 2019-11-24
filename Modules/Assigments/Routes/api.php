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
    Route::post('add', 'AssigmentsController@createAssigment')->middleware('permission:assignment/add');
    Route::post('assign', 'AssigmentsController@AssignAssignmentToLesson')->middleware('permission:assignment/assign');
    Route::post('update', 'AssigmentsController@updateAssigment')->middleware('permission:assignment/update');
    Route::post('submit', 'AssigmentsController@submitAssigment')->middleware('permission:assignment/submit');
    Route::post('grade', 'AssigmentsController@gradeAssigment')->middleware('permission:assignment/grade');
    Route::post('editgrade', 'AssigmentsController@editGradeAssignment')->middleware('permission:assignment/editgrade');
    Route::post('override', 'AssigmentsController@override')->middleware('permission:assignment/override');
    Route::post('delete', 'AssigmentsController@deleteAssigment')->middleware('permission:assignment/delete');
    Route::get('get','AssigmentsController@GetAssignment')->name('getAssignment')->middleware('permission:assignment/get');
    Route::post('toggle', 'AssigmentsController@toggleAssignmentVisibity')->middleware('permission:assignment/toggle');
    Route::get('get-all', 'AssigmentsController@getAllAssigment')->middleware('permission:assignment/get-all');

});
