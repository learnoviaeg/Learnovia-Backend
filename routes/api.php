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
        Route::post('addrole', 'SpatieController@Add_Role')->name('addrole')->middleware('permission:Add Role');
        Route::post('deleterole', 'SpatieController@Delete_Role')->name('deleterole')->middleware('permission:Delete Role');
        Route::post('assignrole', 'SpatieController@Assign_Role_to_user')->name('assignroletouser')->middleware('permission:Assign Role to User');
        Route::post('assigpertorole', 'SpatieController@Assign_Permission_Role')->name('assignpertorole')->middleware('permission:Assign Permission To Role');
        Route::post('revokerole', 'SpatieController@Revoke_Role_from_user')->name('revokerolefromuser')->middleware('permission:Revoke Role from User');
        Route::post('revokepermissionfromrole', 'SpatieController@Revoke_Permission_from_Role')->name('revokepermissionfromrole')->middleware('permission:Revoke Permission from role');
        Route::get('listrandp', 'SpatieController@List_Roles_Permissions')->name('listpermissionandrole')->middleware('permission:List Permissions and Roles');
        Route::Post('InsertBulkofUsers','UserController@insert_users')->name('AddBulkofUsers')->middleware('permission:Add Bulk of Users');;
        Route::post('addpertouser', 'SpatieController@Assign_Permission_User')->name('addpertouser')->middleware('permission:Add Permission To User');
        Route::get('listrolewithper', 'SpatieController@List_Roles_With_Permission')->name('listRolewithPer')->middleware('permission:Add Permission To User');
        Route::post('getRoleWithPermission', 'SpatieController@Get_Individual_Role')->name('getRoleWithPermission')->middleware('permission:Add Permission To User');
        Route::post('addRolewithPer', 'SpatieController@Add_Role_With_Permissions')->name('addRolewithPer')->middleware('permission:Add Permission To User');
        Route::get('exportroleswithper', 'SpatieController@Export_Role_with_Permission')->name('exportroleswithper')->middleware('permission:Add Permission To User');
        Route::post('importroleswithper', 'SpatieController@Import_Role_with_Permission')->name('importroleswithper')->middleware('permission:Add Permission To User');

        
    });
});

?>