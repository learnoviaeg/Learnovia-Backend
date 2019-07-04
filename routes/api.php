<?php

use Illuminate\Http\Request;
Route::post ('delete','AC_year_type@deleteType');

Route::post ('insert_users','UserController@insert_users');
Route::post ('Add','AC_year_type@Add_type_to_Year');
Route::get ('YWT','AC_year_type@List_Years_with_types');
Route::post ('update','AC_year_type@updateType');
Route::post ('assign','AC_year_type@Assign_to_anther_year');
Route::post('AddSegment',"segment_class_Controller@Add_Segment_with_class");
Route::post('deleteSegment',"segment_class_Controller@deleteSegment");
Route::post('AssignSegment',"segment_class_Controller@Assign_to_anther_Class");
Route::get('listClasses',"segment_class_Controller@List_Classes_with_all_segment");
#########################Message##################################
//if you want to send message please,write Send_message_of_users in yours
Route::post('Send_message_of_users',"MessageController@Send_message_of_all_user");

//if you want to delete message please,write deletemessage in yours
Route::post('deleteMessage',"MessageController@deleteMessage");

//if you want to all messages please,write List_All_Message in yours
Route::get('List_All_Message',"MessageController@List_All_Message");

//if you want to see messages please,write SeenMessage in yours
Route::get('SeeMessage',"MessageController@SeenMessage");
#########################Contacts##################################
//if you want to add friend in you contacts please,write addContact in yours
Route::post('addContact',"ContactController@addContact");

//if you want to see contatcs in you contacts please,write ViewMyContact in yours
Route::get('ViewMyContact',"ContactController@ViewMyContact");
#########################Message_Role##################################
Route::post('add_send_Permission_for_role',"RolePermissionController@add_send_Permission_for_role");




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

?>