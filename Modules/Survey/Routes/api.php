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

Route::group(['prefix' => 'survey', 'middleware' => ['auth:api','LastAction']], function () {
    //install survey
    Route::get('install', 'SurveyController@install_survey');
    Route::post('add', 'SurveyController@store')->middleware('permission:survey/add');
    Route::post('submit', 'UserSurveyController@submitSurvey')->middleware('permission:survey/submit');
    Route::get('my-surveys', 'UserSurveyController@get_my_surveys')->middleware('permission:survey/my-surveys');
    Route::get('view-all-submissions', 'UserSurveyController@Review_all_Submissions_of_survey')->middleware('permission:survey/view-all-submissions');
    
    Route::group(['prefix' => 'template'], function () {
        Route::get('get', 'SurveyController@get_template')->middleware('permission:template/get');
    });
});
