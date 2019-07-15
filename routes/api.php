<?php

header("Access-Control-Allow-Origin:*");
header("Access-Control-Allow-Methods:*");
header("Access-Control-Allow-Headers:*");
use App\User;

Route::get('install', function () {
    $user = User::whereEmail('admin@learnovia.com')->first();
    if($user){
        return "This Site is Installed befpre go and ask admin";
    }else{
        \Spatie\Permission\Models\Permission::create(['name' => 'Send Message to users ']);
        \Spatie\Permission\Models\Permission::create(['name' => 'Notify']);
        \Spatie\Permission\Models\Permission::create(['name' => 'Get All Notifications']);
        \Spatie\Permission\Models\Permission::create(['name' => 'Get Unread']);
        \Spatie\Permission\Models\Permission::create(['name' => 'Mark as read']);

        \Spatie\Permission\Models\Permission::create(['name' => 'Add Role']);
        \Spatie\Permission\Models\Permission::create(['name' => 'Delete Role']);
        \Spatie\Permission\Models\Permission::create(['name' => 'Assign Role to User']);
        \Spatie\Permission\Models\Permission::create(['name' => 'Assign Permission To Role']);
        \Spatie\Permission\Models\Permission::create(['name' => 'Revoke Role from User']);
        \Spatie\Permission\Models\Permission::create(['name' => 'Revoke Permission from role']);
        \Spatie\Permission\Models\Permission::create(['name' => 'List Permissions and Roles']);
        \Spatie\Permission\Models\Permission::create(['name' => 'Add Bulk of Users']);

        \Spatie\Permission\Models\Permission::create(['name' => 'Add Permission To User']);
        \Spatie\Permission\Models\Permission::create(['name' => 'List Role with Permissions']);
        \Spatie\Permission\Models\Permission::create(['name' => 'Get Individual Role with Permissions']);
        \Spatie\Permission\Models\Permission::create(['name' => 'Add Role With Permissions']);
        \Spatie\Permission\Models\Permission::create(['name' => 'Export Roles with Permissions']);
        \Spatie\Permission\Models\Permission::create(['name' => 'Import Roles with Permissions']);

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

        //USER CRUD Permissions
        \Spatie\Permission\Models\Permission::create(['name' => 'Add User']);
        \Spatie\Permission\Models\Permission::create(['name' => 'Update User']);
        \Spatie\Permission\Models\Permission::create(['name' => 'Delete User']);
        \Spatie\Permission\Models\Permission::create(['name' => 'List All Users']);
        \Spatie\Permission\Models\Permission::create(['name' => 'Suspend User']);
        \Spatie\Permission\Models\Permission::create(['name' => 'Un Suspend User']);

        $super = \Spatie\Permission\Models\Role::create(['name' => 'Super Admin']);
        \Spatie\Permission\Models\Role::create(['name' => 'System Admin']);
        \Spatie\Permission\Models\Role::create(['name' => 'Student']);
        \Spatie\Permission\Models\Role::create(['name' => 'Teacher']);
        \Spatie\Permission\Models\Role::create(['name' => 'Manager']);
        \Spatie\Permission\Models\Role::create(['name' => 'Supervisor']);
        \Spatie\Permission\Models\Role::create(['name' => 'Parent']);

        $super->givePermissionTo(\Spatie\Permission\Models\Permission::all());

        $user = new User([
            'firstname' => 'Learnovia',
            'lastname' => 'Company',
            'username'=> 'Admin',
            'email' => 'admin@learnovia.com',
            'password' => bcrypt('LeaRnovia_H_M_A'),
            'real_password' =>'LeaRnovia_H_M_A'
        ]);
        $user->save();
        $user->assignRole($super);
        return "System Installed Your User is $user->email and Password is LeaRnovia_H_M_A";
    }

});

Route::post('import', 'ExcelController@import')->name('import');

Route::group(['prefix' => 'auth'], function () {
    Route::post('login', 'AuthController@login')->name('login');
    Route::post('signup', 'AuthController@signup')->name('signup');
});


Route::group(['middleware' => 'auth:api'], function () {
    Route::get('userRole' , 'AuthController@userRole');
    Route::get('logout', 'AuthController@logout')->name('logout');
    Route::get('user', 'AuthController@user')->name('user');
    Route::get('userRole' , 'AuthController@userRole')->name('userRole');
    Route::get('spatie', 'SpatieController@index')->name('spatie');
    Route::post('addrole', 'SpatieController@Add_Role')->name('addrole')->middleware('permission:Add Role');
    Route::post('deleterole', 'SpatieController@Delete_Role')->name('deleterole')->middleware('permission:Delete Role');
    Route::post('assignrole', 'SpatieController@Assign_Role_to_user')->name('assignroletouser');//->middleware('permission:Assign Role to User');
    Route::post('assigpertorole', 'SpatieController@Assign_Permission_Role')->name('assignpertorole');//->middleware('permission:Assign Permission To Role');
    Route::post('revokerole', 'SpatieController@Revoke_Role_from_user')->name('revokerolefromuser')->middleware('permission:Revoke Role from User');
    Route::post('revokepermissionfromrole', 'SpatieController@Revoke_Permission_from_Role')->name('revokepermissionfromrole')->middleware('permission:Revoke Permission from role');
    Route::get('listrandp', 'SpatieController@List_Roles_Permissions')->name('listpermissionandrole')->middleware('permission:List Permissions and Roles');
    Route::Post('InsertBulkofUsers','UserController@insert_users')->name('AddBulkofUsers')->middleware('permission:Add Bulk of Users');;
    Route::post('addpertouser', 'SpatieController@Assign_Permission_User')->name('addpertouser')->middleware('permission:Add Permission To User');
    Route::get('listrolewithper', 'SpatieController@List_Roles_With_Permission')->name('listRolewithPer')->middleware('permission:List Role with Permissions');
    Route::post('getRoleWithPermission', 'SpatieController@Get_Individual_Role')->name('getRoleWithPermission')->middleware('permission:Get Individual Role with Permissions');
    Route::post('addRolewithPer', 'SpatieController@Add_Role_With_Permissions')->name('addRolewithPer')->middleware('permission:Add Role With Permissions');
    Route::get('exportroleswithper', 'SpatieController@Export_Role_with_Permission')->name('exportroleswithper')->middleware('permission:Export Roles with Permissions');
    Route::post('importroleswithper', 'SpatieController@Import_Role_with_Permission')->name('importroleswithper')->middleware('permission:Import Roles with Permissions');
    Route::group(['prefix' => 'notification'] , function (){
        Route::get('getall', 'NotificationController@getallnotifications')->name('getallnotifications')->middleware('permission:Get All Notifications');
        Route::get('unread', 'NotificationController@unreadnotifications')->name('getunreadnotifications')->middleware('permission:Get Unread');
        Route::get('read', 'NotificationController@markasread')->name('readnotification')->middleware('permission:Make Notification Read');
    });
});

Route::group(['prefix' => 'year', 'middleware' => 'auth:api'], function () {
    Route::post('add', 'AcademicYearController@store')->name('addyear')->middleware('permission:Add year');
    Route::get('get', 'AcademicYearController@index')->name('getallyears')->middleware('permission:Get all years');
    Route::get('get/{id}', 'AcademicYearController@show')->name('getyearbyid')->middleware('permission:Get Year');
    Route::put('update', 'AcademicYearController@update')->name('updateyear')->middleware('permission:Update year');
    Route::delete('delete', 'AcademicYearController@destroy')->name('deleteyear')->middleware('permission:Delete Year');
});

Route::group(['prefix' => 'type', 'middleware' => 'auth:api'], function () {
    //if you want to delete an Academic year type please,write deletetype in yours
    Route::post('delete', 'AC_year_type@deleteType')->name('deletetype')->middleware('permission:Delete type');

    //if you want to add an Academic year type to year please,write addtype in yours
    Route::post('add', 'AC_year_type@Add_type_to_Year')->name('addtype')->middleware('permission:Add Type');

    //if you want to get all Academic year with all types please,write getyearswithtype in yours
    Route::get('get', 'AC_year_type@List_Years_with_types')->name('getyearswithtype')->middleware('permission:Get all Types');

    //if you want to update type please,write updatetype in yours
    Route::post('update', 'AC_year_type@updateType')->name('updatetype')->middleware('permission:Update Type');

    //if you want to assign type to another year please,write assigntype in yours
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
    //if you want to add segment to class please,write addsegment in yours
    Route::post('add', "segment_class_Controller@Add_Segment_with_class")->name('addsegment')->middleware('permission:Add Segment');

    //if you want to delete segment please,write deletesegment in yours
    Route::post('delete', "segment_class_Controller@deleteSegment")->name('deletesegment')->middleware('permission:Delete Segment');

    //if you want to assign segment to another class please,write assignsegment in yours
    Route::post('assign', "segment_class_Controller@Assign_to_anther_Class")->name('assignsegment')->middleware('permission:Assign Segment');

    //if you want to get classes with all segments please,write getclasses in yours
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

Route::group(['prefix' => 'user' , 'middleware' => 'auth:api'] , function (){
    //USER CRUD ROUTES
    Route::post('add','UserController@create')->name('adduser')->middleware('permission:Add User');
    Route::post('update','UserController@update')->name('updateuser')->middleware('permission:Update User');
    Route::post('delete','UserController@delete')->name('deleteuser')->middleware('permission:Delete User');
    Route::get('get','UserController@list')->name('listAll')->middleware('permission:List All Users');
    Route::post('suspend','UserController@suspend_user')->name('suspenduser')->middleware('permission:Suspend User');
    Route::post('unsuspend','UserController@unsuspend_user')->name('unsuspenduser')->middleware('permission:Un Suspend User');
});

Route::group(['prefix' => 'enroll'] , function (){
    // start  ... Enrollment of user to courses
    Route::post('/', 'EnrollUserToCourseController@EnrollCourses')->name('EnrollCourses');//->middleware('permission:Enroll User Single Course');;
    Route::post('unenroll', 'EnrollUserToCourseController@UnEnroll')->name('UnEnrollUsers');//->middleware('permission:Enroll User Single Course');;
    Route::post('getAll', 'EnrollUserToCourseController@ViewAllCoursesThatUserErollment')->name('EnrolledCourse');//->middleware('permission:View Enrolled Courses');;
    Route::post('mandatory', 'EnrollUserToCourseController@EnrollInAllMandatoryCourses')->name('EnrollMandatory');//->middleware('permission:Enroll Mandatory Courses');;
    Route::post('enrollexcel','EnrollUserToCourseController@EnrollExistUsersFromExcel');
    Route::post('usertech','EnrollUserToCourseController@AddAndEnrollBulkOfNewUsers');
});

Route::group(['prefix' => 'Messages', 'middleware' => 'auth:api'], function () {
    Route::post('send', 'MessageController@Send_message_of_all_user')->name('sendMessage')->middleware('permission:Send Message to users');
    Route::post('deleteForall', 'MessageController@deleteMessageForAll')->name('deleteMessageforall')->middleware('permission:Delete message for all');
    Route::post('deleteForMe', 'MessageController@deleteMessageforMe')->name('deleteMessageforMe')->middleware('permission:delete Message for Me');
    Route::post('Seen', 'MessageController@SeenMessage')->name('SeenMessage')->middleware('permission:Seen Message');
    Route::post('ViewFromTo', 'MessageController@ViewAllMSG_from_to')->name('ViewFromTo')->middleware('permission:View all Messages From To');
//    Route::post('update', 'MessageController@edit')->name('editcategory')->middleware('permission:Update Category');
//    Route::post('delete', 'MessageController@delete')->name('deletecategory')->middleware('permission:Delete Category');
//    Route::get('get', 'MessageController@get')->name('getcategory')->middleware('permission:Get Categories');
    Route::post('addContact', 'ContactController@addContact')->name('addContact')->middleware('permission:Get Categories');
    Route::post('Message_add_send_Permission_for_role', 'MessageController@add_send_Permission_for_role');
    Route::get('test', 'SpatieController@index');
});