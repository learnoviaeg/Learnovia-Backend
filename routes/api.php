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


Route::post('create' , 'CourseController@store');
Route::get('courses' , 'CourseController@index');
Route::get('course/{id}' , 'CourseController@show');
Route::put('course/update/{id}' , 'CourseController@update');
Route::delete('course/delete/{id}' , 'CourseController@destroy');


Route::post('import', 'ExcelController@import')->name('import');
// Route::get('export', 'ExcelController@export')->name('export');

?>