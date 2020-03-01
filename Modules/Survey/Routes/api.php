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
    Route::post('add-question', 'QuestionSurveyController@QuestionSurvey')->middleware('permission:survey/add-question');
    Route::post('submit', 'UserSurveyController@submitSurvey');//->middleware('permission:survey/add-question');
    Route::post('my', 'UserSurveyController@get_my_surveys');//->middleware('permission:survey/my-serveys');
    Route::post('view-all-surveys', 'UserSurveyController@Review_all_Submissions_of_survey');//->middleware('permission:survey/add-question');
});
