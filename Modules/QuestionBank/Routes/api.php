<?php

use Illuminate\Http\Request;

Route::group(['prefix' => 'quiz', 'middleware' => 'auth:api'], function () {

    //Install Question Bank
    Route::get('install', 'QuestionBankController@install_question_bank');

    //Add/Update Question and Quiz
    Route::group(['middleware' => 'Restrict'], function () {
        Route::post('storeQuestions', 'QuestionBankController@store')->middleware('permission:question/add');
        Route::post('updateQuestion', 'QuestionBankController@update')->middleware('permission:question/update');
        Route::post('storeQuiz', 'QuizController@store')->middleware('permission:quiz/add');
        Route::post('updateQuiz', 'QuizController@update')->middleware('permission:quiz/update');
    });

    //Question Routes
    Route::get('getQuestions', 'QuestionBankController@index')->middleware('permission:question/get');
    Route::post('deleteQuestion', 'QuestionBankController@destroy')->middleware('permission:question/delete');
    Route::get('getRandomQuestion', 'QuestionBankController@getRandomQuestion')->middleware('permission:question/random');
    Route::post('deleteAnswer', 'QuestionBankController@deleteAnswer')->middleware('permission:question/delete-answer');
    Route::post('addAnswer', 'QuestionBankController@addAnswer')->middleware('permission:question/add-answer');

    //Quiz Routes
    Route::post('deleteQuiz', 'QuizController@destroy')->middleware('permission:quiz/delete');
    Route::get('getQuiz', 'QuizController@get')->name('getquiz')->middleware('permission:quiz/get');
    Route::get('sort', 'QuizController@sort')->middleware('permission:quiz/sort');
    Route::get('SortUp', 'QuizController@SortUp')->middleware('permission:quiz/sortup');

    //Quiz Lesson Routes
    Route::post('addQuizLesson', 'QuizLessonController@store')->middleware('permission:quiz/add-quiz-lesson');
    Route::post('updateQuizLesson', 'QuizLessonController@update')->middleware('permission:quiz/update-quiz-lesson');
    Route::post('deleteQuizLesson', 'QuizLessonController@destroy')->middleware('permission:quiz/destroy-quiz-lesson');
    Route::get('types', 'QuestionBankController@getAllTypes')->middleware('permission:quiz/get-all-types');
    Route::get('categories', 'QuestionBankController@getAllCategories')->middleware('permission:quiz/get-all-categories');
    Route::get('getQuizInLesson', 'QuizLessonController@getQuizInLesson')->middleware('permission:quiz/get-quiz-lesson');

    //User Quiz
    Route::post('storeUserQuiz', 'UserQuizController@store_user_quiz')->middleware('permission:quiz/sort-user-quiz');
    Route::post('storeUserQuizAnswer', 'UserQuizController@quiz_answer')->middleware('permission:quiz/sort-user-quiz-answer');

    Route::get('getAllQuizes', 'QuizController@getAllQuizes')->middleware('permission:quiz/get-all-quizes');
    Route::get('getStudentinQuiz', 'QuizController@getStudentinQuiz')->middleware('permission:quiz/get-student-in-quiz');
    Route::get('getStudentAnswerinQuiz', 'QuizController@getStudentAnswerinQuiz')->middleware('permission:quiz/get-student-answer-quiz');
    Route::get('getAllStudentsAnswerinQuiz', 'QuizController@getAllStudentsAnswerinQuiz')->middleware('permission:quiz/get-all-students-answer');
    Route::get('getSingleQuiz', 'QuizController@getSingleQuiz')->middleware('permission:quiz/get-single-quiz');
    Route::post('toggleQuizVisibity', 'QuizController@toggleQuizVisibity')->middleware('permission:quiz/toggleQuizVisibity');

    Route::post('correctUserQuiz', 'UserQuizController@estimateEssayandAndWhy')->middleware('permission:quiz/correct-user-quiz');


});
