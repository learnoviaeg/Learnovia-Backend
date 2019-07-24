<?php

use Illuminate\Http\Request;
Route::get('sort', 'QuizController@sort');
Route::get('SortUp', 'QuizController@SortUp');
Route::get('installQuestionBank', function () {
        \Spatie\Permission\Models\Permission::create(['name' => 'Get All Questions']);
        \Spatie\Permission\Models\Permission::create(['name' => 'Delete Question']);
        \Spatie\Permission\Models\Permission::create(['name' => 'Get Random Questions For Course']);
        \Spatie\Permission\Models\Permission::create(['name' => 'Update Question']);
        \Spatie\Permission\Models\Permission::create(['name' => 'Delete Question Answer']);
        \Spatie\Permission\Models\Permission::create(['name' => 'Add New Answer']);
        \Spatie\Permission\Models\Permission::create(['name' => 'Add New Questions']);
});

Route::group([
    'prefix' => 'auth'
], function () {

    Route::group([
      'middleware' => 'auth:api'
    ], function() {
        Route::get('getQuestions', 'QuestionBankController@index')->middleware('Get All Questions');
        Route::post('deleteQuestion', 'QuestionBankController@destroy')->middleware('Delete Question');
        Route::get('getRandomQuestion', 'QuestionBankController@getRandomQuestion')->middleware('Get Random Questions For Course');
        Route::post('updateQuestion', 'QuestionBankController@update')->middleware('Update Question');
        Route::post('deleteAnswer', 'QuestionBankController@deleteAnswer')->middleware('Delete Question Answer');
        Route::post('addAnswer', 'QuestionBankController@addAnswer')->middleware('Add New Answer');
        Route::post('storeQuestions', 'QuestionBankController@store')->middleware('Add New Questions');

        
        Route::post('deleteQuiz', 'QuizController@destroy');
        Route::post('updateQuiz', 'QuizController@update');
        Route::post('storeQuiz', 'QuizController@store');
        Route::get('show', 'QuizController@quiz');

    });
});
Route::post('updateQuiz', 'QuizController@update');


?>

