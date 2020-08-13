<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Route::prefix('h5p')->group(function() {
//     // Route::get('/', 'H5pController@index');
// });

// Route::get('library', "LibraryController@index")->name('h5p.library.index');
// Route::post('content/create', "H5pController@h5p_lesson");




// // <?php
// // Route::group(, function () 
//     // if (config('laravel-h5p.use_router') == 'EDITOR' || config('laravel-h5p.use_router') == 'ALL') {
//         Route::prefix('h5p')->group(function() {

//         Route::resource('h5p', "H5pController");
//         // Route::group(['middleware' => ['auth']], function () {
// //            Route::get('h5p/export', 'Djoudi\LaravelH5p\Http\Controllers\H5pController@export')->name("h5p.export");

//             Route::get('library', "LibraryController@index")->name('h5p.library.index');
//             Route::get('library/show/{id}', "LibraryController@show")->name('h5p.library.show');
//             Route::post('library/store', "LibraryController@store")->name('h5p.library.store');
//             Route::delete('library/destroy', "LibraryController@destroy")->name('h5p.library.destroy');
//             Route::get('library/restrict', "LibraryController@restrict")->name('h5p.library.restrict');
//             Route::post('library/clear', "LibraryController@clear")->name('h5p.library.clear');

//         // });

//         // ajax
//         // Route::match(['GET', 'POST'], 'ajax/libraries', 'AjaxController@libraries')->name('h5p.ajax.libraries');
//         // Route::get('ajax', 'AjaxController')->name('h5p.ajax');
//         Route::get('ajax/libraries', 'AjaxController@libraries')->name('h5p.ajax.libraries');
//         Route::get('ajax/single-libraries', 'AjaxController@singleLibrary')->name('h5p.ajax.single-libraries');
//         Route::post('ajax/content-type-cache', 'AjaxController@contentTypeCache')->name('h5p.ajax.content-type-cache');
//         Route::post('ajax/library-install', 'AjaxController@libraryInstall')->name('h5p.ajax.library-install');
//         Route::post('ajax/library-upload', 'AjaxController@libraryUpload')->name('h5p.ajax.library-upload');
//         Route::post('ajax/rebuild-cache', 'AjaxController@rebuildCache')->name('h5p.ajax.rebuild-cache');
//         Route::post('ajax/files', 'AjaxController@files')->name('h5p.ajax.files');
//         Route::get('ajax/finish', 'AjaxController@finish')->name('h5p.ajax.finish');
//         Route::post('ajax/content-user-data', 'AjaxController@contentUserData')->name('h5p.ajax.content-user-data');
//     // }

//     // export
//     //    if (config('laravel-h5p.use_router') == 'EXPORT' || config('laravel-h5p.use_router') == 'ALL') {
//     // Route::get('h5p/embed/{id}', 'Djoudi\LaravelH5p\Http\Controllers\EmbedController')->name('h5p.embed');
//     // Route::get('h5p/export/{id}', 'Djoudi\LaravelH5p\Http\Controllers\DownloadController')->name('h5p.export');
// //    }
// // );
// });