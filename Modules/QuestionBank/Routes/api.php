<?php

use Illuminate\Http\Request;

Route::group(['prefix' => 'quiz', 'middleware' =>[ 'auth:api','LastAction']], function () {

    //Install Question Bank
    Route::get('install', 'QuestionBankController@install_question_bank');

    //Add/Update Question and Quiz
    Route::post('add', 'QuizController@store')->middleware('permission:quiz/add');
    Route::post('grading-method', 'QuizController@gradeing_method')->middleware('permission:quiz/grading-method');
    Route::post('update', 'QuizController@update')->middleware('permission:quiz/update');
    Route::get('script-shuffle', 'QuizController@ScriptShuffle');

    //Quiz Routes
    Route::post('delete', 'QuizController@destroy')->middleware('permission:quiz/delete');
    Route::get('get', 'QuizController@get')->name('getquiz')->middleware(['permission:quiz/get' , 'ParentCheck']);
    Route::get('sort', 'QuizController@sort')->middleware('permission:quiz/sort');
    // Route::get('sortup', 'QuizCon-troller@SortUp')>middleware('permission:quiz/sortup');

    //Quiz Lesson Routes
    Route::post('add-quiz-lesson', 'QuizLessonController@store')->middleware('permission:quiz/add-quiz-lesson');
    Route::post('update-quiz-lesson', 'QuizLessonController@update')->middleware('permission:quiz/update-quiz-lesson');
    Route::post('destroy-quiz-lesson', 'QuizLessonController@destroy')->middleware('permission:quiz/destroy-quiz-lesson');
    Route::get('get-all-types', 'QuestionBankController@getAllTypes')->middleware('permission:quiz/get-all-types');
    Route::get('get-all-categories', 'QuestionBankController@getAllCategories')->middleware('permission:quiz/get-all-categories');
    Route::get('get-quiz-lesson', 'QuizLessonController@getQuizInLesson')->middleware('permission:quiz/get-quiz-lesson');
    Route::get('get-grade-category', 'QuizLessonController@getGradeCategory')->middleware('permission:quiz/get-grade-category');
    Route::post('override', 'QuizLessonController@overrideQuiz')->middleware('permission:quiz/override');

    //User Quiz
    Route::post('store-user-quiz', 'UserQuizController@store_user_quiz')->middleware('permission:quiz/answer');
    Route::post('store-user-quiz-answer', 'UserQuizController@quiz_answer')->middleware('permission:quiz/answer');
    Route::post('feedback', 'UserQuizController@feedback');
    Route::get('get-all-quizes', 'QuizController@getAllQuizes')->middleware('permission:quiz/get-all-quizes');
    Route::get('get-student-in-quiz', 'QuizController@getStudentinQuiz')->middleware('permission:quiz/get-student-in-quiz');
    Route::get('get-student-answer-quiz', 'QuizController@getStudentAnswerinQuiz')->middleware('permission:quiz/get-student-answer-quiz');
    Route::get('get-all-students-answer', 'QuizController@getAllStudentsAnswerinQuiz')->middleware('permission:quiz/get-all-students-answer');
    Route::get('get-single-quiz', 'QuizController@getSingleQuiz')->middleware('permission:quiz/answer|quiz/detailes');
    Route::post('toggle', 'QuizController@toggleQuizVisibity')->middleware('permission:quiz/toggle');
    Route::post('correct-user-quiz', 'UserQuizController@estimateEssayandAndWhy')->middleware('permission:quiz/correct-user-quiz');
    Route::post('get-attempts', 'QuizController@get_user_quiz')->middleware(['permission:quiz/get-attempts', 'ParentCheck']);
    // Route::post('grade-user-quiz', 'UserQuizController@gradeUserQuiz')->middleware('permission:quiz/grade-user-quiz');
    Route::post('get-all-attempts', 'UserQuizController@get_all_users_quiz_attempts')->middleware('permission:quiz/detailes');
    // Route::post('get-fully-detailed-attempt', 'UserQuizController@get_fully_detailed_attempt')->middleware('permission:quiz/get-fully-detailed-attempt');
});

Route::group(['prefix' => 'question', 'middleware' => ['auth:api','LastAction']], function () {
    //Add/Update Question
    Route::post('add', 'QuestionBankController@store')->middleware('permission:question/add');
    Route::post('update', 'QuestionBankController@update')->middleware('permission:question/update');

    Route::group(['prefix' => 'category'], function () {
        Route::post('add', 'QuestionCategoryController@create')->middleware('permission:question/category/add');
        Route::get('get', 'QuestionCategoryController@show')->middleware('permission:question/category/get');
        Route::post('update', 'QuestionCategoryController@update')->middleware('permission:question/category/update');
        Route::post('delete', 'QuestionCategoryController@destroy')->middleware('permission:question/category/delete');
        Route::get('migration', 'QuestionCategoryController@MigrationScript')->middleware('permission:site/show-all-courses');
    });

    Route::get('get', 'QuestionBankController@index')->middleware(['permission:question/get' , 'ParentCheck']);
    Route::post('delete', 'QuestionBankController@destroy')->middleware('permission:question/delete');
    Route::get('random', 'QuestionBankController@getRandomQuestion')->middleware('permission:question/random');
    Route::post('delete-answer', 'QuestionBankController@deleteAnswer')->middleware('permission:question/delete-answer');
    Route::post('add-answer', 'QuestionBankController@addAnswer')->middleware('permission:question/add-answer');
});
