<?php

use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'schools-report', 'middleware' => ['auth:api']], function () {
    Route::post('template1', 'TemplateCards\OneCourseTemplateController@oneCourseGrades'); //course mogma3
    Route::post('template1All', 'TemplateCards\OneCourseTemplateController@oneCourseGradesAll'); //printAll
});