<?php

use Illuminate\Http\Request;

Route::group([
    'prefix' => 'auth'
], function () {
    Route::post('login', 'AuthController@login')->name('login');
    Route::post('signup', 'AuthController@signup')->name('signup');
  
    Route::group([
      'middleware' => 'auth:api'
    ], function() {
        Route::get('logout', 'AuthController@logout')->name('logout');
        Route::get('user', 'AuthController@user')->name('user');
        Route::get('spatie', 'SpatieController@index')->name('spatie');
        Route::post('addrole/{name}', 'SpatieController@Add_Role')->name('addrole')->middleware('permission:Add Role');
        Route::post('deleterole/{id}', 'SpatieController@Delete_Role')->name('deleterole')->middleware('permission:Delete Role');
        Route::post('assignrole', 'SpatieController@Assign_Role_to_user')->name('assignroletouser')->middleware('permission:Assign Role to User');
        Route::post('assigpertorole', 'SpatieController@Assign_Permission_Role')->name('assignpertorole')->middleware('permission:Assign Permission To Role');
        Route::post('revokerole', 'SpatieController@Revoke_Role_from_user')->name('revokerolefromuser')->middleware('permission:Revoke Role from User');
        Route::post('revokepermissionfromrole', 'SpatieController@Revoke_Permission_from_Role')->name('revokepermissionfromrole')->middleware('permission:Revoke Permission from role');
        Route::get('listrandp', 'SpatieController@List_Roles_Permissions')->name('listpermissionandrole')->middleware('permission:List Permissions and Roles');
        Route::Post('InsertBulkofUsers','UserController@insert_users')->name('AddBulkofUsers')->middleware('permission:Add Bulk of Users');;
       
        
    });
});

                 // Academic Year //
Route::post('/createyear', 'AcademicYearController@store');
Route::get('/yearshowall', 'AcademicYearController@index');
Route::get('/yearshow/{id}', 'AcademicYearController@show');
Route::put('/year/update/{id}', 'AcademicYearController@update');
Route::delete('/yeardelete/{id}', 'AcademicYearController@destroy');

                 // Academic Type //
Route::post('/createyeartype', 'AcademicTypeController@store');
Route::get('/yearstypehowall', 'AcademicTypeController@index');
Route::get('/yeartypeshow/{id}', 'AcademicTypeController@show');
Route::put('/yeartype/update/{id}', 'AcademicTypeController@update');
Route::delete('/yeartypedelete/{id}', 'AcademicTypeController@destroy');

                 // Class //
Route::post('/createclass', 'ClassController@AddClassWithYear');
Route::get('/showallclasses', 'ClassController@index');
Route::get('/classshow/{id}', 'ClassController@show');
Route::put('/class/update/{id}', 'ClassController@update');
Route::delete('/classdelete/{id}', 'ClassController@destroy');


?>