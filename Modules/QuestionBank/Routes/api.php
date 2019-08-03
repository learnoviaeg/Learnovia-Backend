<?php

use Illuminate\Http\Request;


Route::group(['prefix' => 'quiz', 'middleware' => 'auth:api'], function () {
    Route::post('storeQuestions', 'QuestionBankController@store');

    Route::get('install', function () {
        if (\Spatie\Permission\Models\Permission::whereName('question/add')->first() != null) {
            return \App\Http\Controllers\HelperController::api_response_format(400, null, 'This Component is installed before');
        }
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'question/add']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'question/update']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'question/get']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'question/delete']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'question/random']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'question/add-answer']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'question/delete-answer']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'quiz/add']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'quiz/update']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'quiz/delete']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'quiz/get-quiz-with-random-questions']);

        $role = \Spatie\Permission\Models\Role::find(1);
        $role->givePermissionTo('question/add');
        $role->givePermissionTo('question/update');
        $role->givePermissionTo('question/delete');
        $role->givePermissionTo('quiz/get-quiz-with-random-questions');
        $role->givePermissionTo('question/random');
        $role->givePermissionTo('question/add-answer');
        $role->givePermissionTo('question/delete-answer');
        $role->givePermissionTo('quiz/add');
        $role->givePermissionTo('quiz/update');
        $role->givePermissionTo('quiz/delete');
        return \App\Http\Controllers\HelperController::api_response_format(200, null, 'Component Installed Successfully');

    });
    Route::group(['middleware' => ['store_question']], function () {
        Route::post('storeQuestions', 'QuestionBankController@store')->middleware('permission:question/add');
        Route::post('updateQuestion', 'QuestionBankController@update')->middleware('permission:question/update');
        Route::post('storeQuiz', 'QuizController@store')->middleware('permission:quiz/add');
        Route::post('updateQuiz', 'QuizController@update')->middleware('permission:quiz/update');
    });

    Route::get('getQuestions', 'QuestionBankController@index')->middleware('permission:question/get');
    Route::post('deleteQuestion', 'QuestionBankController@destroy')->middleware('permission:question/delete');
    Route::get('getRandomQuestion', 'QuestionBankController@getRandomQuestion')->middleware('permission:question/random');
    Route::post('deleteAnswer', 'QuestionBankController@deleteAnswer')->middleware('permission:question/delete-answer');
    Route::post('addAnswer', 'QuestionBankController@addAnswer')->middleware('permission:question/add-answer');

    Route::post('deleteQuiz', 'QuizController@destroy')->middleware('permission:quiz/delete');
    //Route::get('getQuiz', 'QuizController@getQuizwithRandomQuestion')->middleware('permission:quiz/get-quiz-with-random-questions');

    Route::post('addQuizLesson', 'QuizLessonController@store');
    Route::post('updateQuizLesson', 'QuizLessonController@update');
    Route::post('deleteQuizLesson', 'QuizLessonController@destroy');

    Route::get('getQuiz', 'QuizController@getQuizwithRandomQuestion');

    Route::post('storeUserQuiz', 'UserQuizController@store_user_quiz');

    Route::post('storeUserQuizAnswer', 'UserQuizController@quiz_answer');

});


