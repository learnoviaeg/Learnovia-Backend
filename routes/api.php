<?php

use App\User;

Route::get('install', function () {
    $user = User::whereEmail('admin@learnovia.com')->first();
    if($user){
        return "This Site is Installed befpre go and ask admin";
    }else{
        \Spatie\Permission\Models\Permission::create(['name' => 'Add Role']);
        \Spatie\Permission\Models\Permission::create(['name' => 'Delete Role']);

        \Spatie\Permission\Models\Permission::create(['name' => 'Assign Role to User']);
        \Spatie\Permission\Models\Permission::create(['name' => 'Assign Permission To Role']);
        \Spatie\Permission\Models\Permission::create(['name' => 'Revoke Role from User']);
        \Spatie\Permission\Models\Permission::create(['name' => 'Revoke Permission from role']);
        \Spatie\Permission\Models\Permission::create(['name' => 'List Permissions and Roles']);
        \Spatie\Permission\Models\Permission::create(['name' => 'Add Bulk of Users']);

        \Spatie\Permission\Models\Permission::create(['name' => 'Add year']);
        \Spatie\Permission\Models\Permission::create(['name' => 'Get all years']);
        \Spatie\Permission\Models\Permission::create(['name' => 'Get Year']);
        \Spatie\Permission\Models\Permission::create(['name' => 'Update year']);
        \Spatie\Permission\Models\Permission::create(['name' => 'Delete Year']);
        \Spatie\Permission\Models\Permission::create(['name' => 'Delete type']);
        \Spatie\Permission\Models\Permission::create(['name' => 'Add Type']);
        \Spatie\Permission\Models\Permission::create(['name' => 'Get all Types']);
        \Spatie\Permission\Models\Permission::create(['name' => 'Update Type']);
        \Spatie\Permission\Models\Permission::create(['name' => 'Assign Type']);
        \Spatie\Permission\Models\Permission::create(['name' => 'Add Level']);
        \Spatie\Permission\Models\Permission::create(['name' => 'Get all Levels']);
        \Spatie\Permission\Models\Permission::create(['name' => 'Delete Level']);
        \Spatie\Permission\Models\Permission::create(['name' => 'Add Class']);
        \Spatie\Permission\Models\Permission::create(['name' => 'Get all Classes']);
        \Spatie\Permission\Models\Permission::create(['name' => 'Get Class By id']);
        \Spatie\Permission\Models\Permission::create(['name' => 'Update Class']);
        \Spatie\Permission\Models\Permission::create(['name' => 'Delete Class']);
        \Spatie\Permission\Models\Permission::create(['name' => 'Add Segment']);
        \Spatie\Permission\Models\Permission::create(['name' => 'Delete Segment']);
        \Spatie\Permission\Models\Permission::create(['name' => 'Assign Segment']);
        \Spatie\Permission\Models\Permission::create(['name' => 'Get All Segments']);
        \Spatie\Permission\Models\Permission::create(['name' => 'Add Category']);
        \Spatie\Permission\Models\Permission::create(['name' => 'Update Category']);
        \Spatie\Permission\Models\Permission::create(['name' => 'Delete Category']);
        \Spatie\Permission\Models\Permission::create(['name' => 'Get Categories']);
        \Spatie\Permission\Models\Permission::create(['name' => 'Add Course']);
        \Spatie\Permission\Models\Permission::create(['name' => 'Update Course']);
        \Spatie\Permission\Models\Permission::create(['name' => 'Delete Course']);
        \Spatie\Permission\Models\Permission::create(['name' => 'Get Courses']);
        $super = \Spatie\Permission\Models\Role::create(['name' => 'Super Admin']);
        \Spatie\Permission\Models\Role::create(['name' => 'System Admin']);
        \Spatie\Permission\Models\Role::create(['name' => 'Student']);
        \Spatie\Permission\Models\Role::create(['name' => 'Teacher']);
        \Spatie\Permission\Models\Role::create(['name' => 'Manager']);
        \Spatie\Permission\Models\Role::create(['name' => 'Supervisor']);
        \Spatie\Permission\Models\Role::create(['name' => 'Parent']);

        $super->givePermissionTo(\Spatie\Permission\Models\Permission::all());

        $user = new User([
            'name' => 'Learnovia Company',
            'email' => 'admin@learnovia.com',
            'password' => bcrypt('LeaRnovia_H_M_A')
        ]);
        $user->save();
        $user->assignRole($super);
        return "System Installed Your User is $user->email and Password is LeaRnovia_H_M_A";
    }

});
Route::group(['prefix' => 'auth'], function () {
    Route::post('login', 'AuthController@login')->name('login');
    Route::post('signup', 'AuthController@signup')->name('signup');
});

Route::group(['middleware' => 'auth:api'], function () {
    Route::get('logout', 'AuthController@logout')->name('logout');
    Route::get('user', 'AuthController@user')->name('user');
    Route::post('addrole/{name}', 'SpatieController@Add_Role')->name('addrole')->middleware('permission:Add Role');
    Route::post('deleterole/{id}', 'SpatieController@Delete_Role')->name('deleterole')->middleware('permission:Delete Role');
    Route::post('assignrole', 'SpatieController@Assign_Role_to_user')->name('assignroletouser')->middleware('permission:Assign Role to User');
    Route::post('assigpertorole', 'SpatieController@Assign_Permission_Role')->name('assignpertorole')->middleware('permission:Assign Permission To Role');
    Route::post('revokerole', 'SpatieController@Revoke_Role_from_user')->name('revokerolefromuser')->middleware('permission:Revoke Role from User');
    Route::post('revokepermissionfromrole', 'SpatieController@Revoke_Permission_from_Role')->name('revokepermissionfromrole')->middleware('permission:Revoke Permission from role');
    Route::get('listrandp', 'SpatieController@List_Roles_Permissions')->name('listpermissionandrole')->middleware('permission:List Permissions and Roles');
    Route::Post('InsertBulkofUsers', 'UserController@insert_users')->name('AddBulkofUsers')->middleware('permission:Add Bulk of Users');
});

Route::group(['prefix' => 'year', 'middleware' => 'auth:api'], function () {
    Route::post('add', 'AcademicYearController@store')->name('addyear')->middleware('permission:Add year');
    Route::get('get', 'AcademicYearController@index')->name('getallyears')->middleware('permission:Get all years');
    Route::get('get/{id}', 'AcademicYearController@show')->name('getyearbyid')->middleware('permission:Get Year');
    Route::put('update', 'AcademicYearController@update')->name('updateyear')->middleware('permission:Update year');
    Route::delete('delete', 'AcademicYearController@destroy')->name('deleteyear')->middleware('permission:Delete Year');
});

Route::group(['prefix' => 'type', 'middleware' => 'auth:api'], function () {
    Route::post('delete', 'AC_year_type@deleteType')->name('deletetype')->middleware('permission:Delete type');
    Route::post('add', 'AC_year_type@Add_type_to_Year')->name('addtype')->middleware('permission:Add Type');
    Route::get('get', 'AC_year_type@List_Years_with_types')->name('getyearswithtype')->middleware('permission:Get all Types');
    Route::post('update', 'AC_year_type@updateType')->name('updatetype')->middleware('permission:Update Type');
    Route::post('assign', 'AC_year_type@Assign_to_anther_year')->name('assigntype')->middleware('permission:Assign Type');
});

Route::group(['prefix' => 'level', 'middleware' => 'auth:api'], function () {
    Route::post('add', 'LevelsController@AddLevelWithYear')->name('addlevel')->middleware('permission:Add Level');
    Route::get('get', 'LevelsController@GetAllLevelsInYear')->name('getlevels')->middleware('permission:Get all Levels');
    Route::post('delete', 'LevelsController@Delete')->name('deletelevel')->middleware('permission:Delete Level');
});

Route::group(['prefix' => 'class', 'middleware' => 'auth:api'], function () {
    Route::post('add', 'ClassController@AddClassWithYear')->name('addclass')->middleware('permission:Add Class');
    Route::get('get', 'ClassController@index')->name('getallclasses')->middleware('permission:Get all Classes');
    Route::get('get/{id}', 'ClassController@show')->name('getclassbyid')->middleware('permission:Get Class By id');
    Route::put('update', 'ClassController@update')->name('updateclass')->middleware('permission:Update Class');
    Route::delete('delete', 'ClassController@destroy')->name('deleteclass')->middleware('permission:Delete Class');
});

Route::group(['prefix' => 'segment', 'middleware' => 'auth:api'], function () {
    Route::post('add', "segment_class_Controller@Add_Segment_with_class")->name('addsegment')->middleware('permission:Add Segment');
    Route::post('delete', "segment_class_Controller@deleteSegment")->name('deletesegment')->middleware('permission:Delete Segment');
    Route::post('assign', "segment_class_Controller@Assign_to_anther_Class")->name('assignsegment')->middleware('permission:Assign Segment');
    Route::get('get', "segment_class_Controller@List_Classes_with_all_segment")->name('getclasses')->middleware('permission:Get All Segments');
});

Route::group(['prefix' => 'category', 'middleware' => 'auth:api'], function () {
    Route::post('add', 'CategoryController@add')->name('addcategory')->middleware('permission:Add Category');
    Route::post('update', 'CategoryController@edit')->name('editcategory')->middleware('permission:Update Category');
    Route::post('delete', 'CategoryController@delete')->name('deletecategory')->middleware('permission:Delete Category');
    Route::get('get', 'CategoryController@get')->name('getcategory')->middleware('permission:Get Categories');
});

Route::group(['prefix' => 'course', 'middleware' => 'auth:api'], function () {
    Route::post('add', 'CourseController@add')->name('addcourse')->middleware('permission:Add Course');
    Route::post('update', 'CourseController@update')->name('editcourse')->middleware('permission:Update Course');
    Route::post('delete', 'CourseController@delete')->name('deletecourse')->middleware('permission:Delete Course');
    Route::get('get', 'CourseController@get')->name('getcourse')->middleware('permission:Get Courses');
});
