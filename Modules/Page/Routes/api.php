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

Route::middleware('auth:api')->get('/page', function (Request $request) {
    return $request->user();
});

Route::group([
    'middleware' => 'auth:api'
  ], function() {

Route::get('getallpages','PageController@show_Page')->name('getallpages')->middleware('permission:Get all Pages');
Route::post('createPage','PageController@Page')->name('createPage')->middleware('permission:Create Page');
Route::get('getallclass','PageController@get_classes')->name('getallclass')->middleware('permission:Get all Classes');
Route::get('getsegment','PageController@get_segments')->name('getsegment')->middleware('permission:Get all Segments');
Route::post('pagewithclass','PageController@Pages_with_classes')->name('pagewithclass')->middleware('permission:Get Classess Assigned to Specific Page');


});







