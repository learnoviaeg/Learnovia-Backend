<?php

use Illuminate\Http\Request;


Route::group(['prefix' => 'quiz', 'middleware' => 'auth:api'], function () {

    //Install Question Bank
    Route::get('install','QuestionBankController@install_question_bank');
    Route::post('storeQuestions', 'QuestionBankController@store');
    Route::post('updateQuestion', 'updateQuestion@update');
    //Add/Update Question and Quiz
    Route::group(['middleware' => ['store_question']], function () {
     //   Route::post('storeQuestions', 'QuestionBankController@store');//->middleware('permission:question/add');
        //Route::post('updateQuestion', 'QuestionBankController@update');//->middleware('permission:question/update');
        Route::post('storeQuiz', 'QuizController@store')->middleware('permission:quiz/add');
        Route::post('updateQuiz', 'QuizController@update')->middleware('permission:quiz/update');
    });

    //Question Routes
    Route::get('getQuestions', 'QuestionBankController@index');//->middleware('permission:question/get');
    Route::post('deleteQuestion', 'QuestionBankController@destroy')->middleware('permission:question/delete');
    Route::get('getRandomQuestion', 'QuestionBankController@getRandomQuestion')->middleware('permission:question/random');
    Route::post('deleteAnswer', 'QuestionBankController@deleteAnswer')->middleware('permission:question/delete-answer');
    Route::post('addAnswer', 'QuestionBankController@addAnswer')->middleware('permission:question/add-answer');

    //Quiz Routes
    Route::post('deleteQuiz', 'QuizController@destroy')->middleware('permission:quiz/delete');
    Route::get('getQuiz', 'QuizController@getQuizwithRandomQuestion')->middleware('permission:quiz/get-quiz-with-random-questions');

    //Quiz Lesson Routes
    Route::post('addQuizLesson', 'QuizLessonController@store')->middleware('permission:quiz/add-quiz-lesson');
    Route::post('updateQuizLesson', 'QuizLessonController@update')->middleware('permission:quiz/update-quiz-lesson');
    Route::post('deleteQuizLesson', 'QuizLessonController@destroy')->middleware('permission:quiz/destroy-quiz-lesson');
    Route::get('types' , 'QuestionBankController@getAllTypes');//->middleware('permission:quiz/get-all-types');
    Route::get('categories' , 'QuestionBankController@getAllCategories');//->middleware('permission:quiz/get-all-categories');
});

