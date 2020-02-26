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

Route::group(['prefix' => 'survey', 'middleware' => 'auth:api'], function () {
    //install survey
    Route::get('install', 'SurveyController@install_survey');

    Route::post('add', 'SurveyController@store')->middleware('permission:survey/add');
});