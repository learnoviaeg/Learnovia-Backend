<?php

header("Access-Control-Allow-Origin:http://localhost:4200");
header("Access-Control-Allow-Methods:POST, GET, OPTIONS, PUT, DELETE");
header("Access-Control-Allow-Headers:Accept, Authorization, Content-Type");
header("Access-Control-Allow-Credentials:true");

use App\User;

Route::get('install', function () {
    $user = User::whereEmail('admin@learnovia.com')->first();
    if ($user) {
        return "This Site is Installed before go and ask admin";
    } else {
        //Message Permissiosns
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'messages/send']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'messages/delete-for-all']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'messages/delete-for-me']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'messages/seen']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'messages/get-from-to']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'messages/add-send-permission-for-role']);

        //Notifications Permissiosns
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'notifications/get-all']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'notifications/get-unread']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'notifications/mark-as-read']);

        //Spatie Permissions
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'roles/add']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'roles/delete']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'roles/get']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'roles/update']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'roles/assign-to-user']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'permissions/assign-to-role']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'roles/revoke-from-user']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'permissions/revoke-from-role']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'spatie/list-permissions-and-roles']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'permissions/assign-to-user']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'spatie/list-role-with-permissions']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'spatie/get-role-with-permissions']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'spatie/add-role-with-permissions']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'spatie/export']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'spatie/import']);

        //Year Permissions
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'year/add']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'year/get-all']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'year/get']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'year/update']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'year/delete']);

        //Type Permissions
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'type/delete']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'type/add']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'type/get-all']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'type/update']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'type/assign']);

        //Level Permissions
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'level/add']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'level/update']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'level/get-all']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'level/delete']);

        //Class Permissions
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'class/add']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'class/get-all']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'class/get']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'class/update']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'class/delete']);

        //Segment Permissions
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'segment/add']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'segment/delete']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'segment/assign']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'segment/update']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'segment/get-all']);

        //Cetegory Permissions
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'category/add']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'category/update']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'category/delete']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'category/get-all']);

        //Course Permissions
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'course/add']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'course/update']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'course/delete']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'course/get-all']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'course/my-courses']);

        //Enroll Permissions
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'enroll/enroll-single-user']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'enroll/un-enroll-single-user']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'enroll/get-enrolled-courses']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'enroll/mandatory-course']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'enroll/bulk-of-exist-users']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'enroll/add-and-enroll-bulk-of-new-users']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'enroll/enrolled-users']);

        //Contact Permissions
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'contact/add']);

        //USER CRUD Permissions
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'user/add']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'user/update']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'user/delete']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'user/get-all']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'user/suspend']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'user/un-suspend']);

        //Components Permissions
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'component/get']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'component/install']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'component/uninstall']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'component/toggle']);

        //Add Roles
        $super = \Spatie\Permission\Models\Role::create(['guard_name' => 'api', 'name' => 'Super Admin']);
        \Spatie\Permission\Models\Role::create(['guard_name' => 'api', 'name' => 'System Admin']);
        \Spatie\Permission\Models\Role::create(['guard_name' => 'api', 'name' => 'Student']);
        \Spatie\Permission\Models\Role::create(['guard_name' => 'api', 'name' => 'Teacher']);
        \Spatie\Permission\Models\Role::create(['guard_name' => 'api', 'name' => 'Manager']);
        \Spatie\Permission\Models\Role::create(['guard_name' => 'api', 'name' => 'Supervisor']);
        \Spatie\Permission\Models\Role::create(['guard_name' => 'api', 'name' => 'Parent']);

        $super->givePermissionTo(\Spatie\Permission\Models\Permission::all());

        $user = new User([
            'firstname' => 'Learnovia',
            'lastname' => 'Company',
            'username' => 'Admin',
            'email' => 'admin@learnovia.com',
            'password' => bcrypt('LeaRnovia_H_M_A'),
            'real_password' => 'LeaRnovia_H_M_A'
        ]);
        $user->save();
        $user->assignRole($super);
        return "System Installed Your User is $user->email and Password is LeaRnovia_H_M_A";
    }

});

//Import Excel Route
Route::post('import', 'ExcelController@import')->name('import');

//Login and Signup
Route::group(['prefix' => 'auth'], function () {
    Route::post('login', 'AuthController@login')->name('login');
    Route::post('signup', 'AuthController@signup')->name('signup');
});


Route::group(['middleware' => 'auth:api'], function () {
    //user main routes
    Route::get('userRole', 'AuthController@userRole')->name('userRole');
    Route::get('logout', 'AuthController@logout')->name('logout');
    Route::get('user', 'AuthController@user')->name('user');
    Route::post('CheckPermission' , 'SpatieController@checkPermessionOnCourse')->name('checkPermessionOnCourse');

    //Spatie Routes
    Route::get('spatie', 'SpatieController@index')->name('spatie');
    Route::post('getperforrole', 'SpatieController@Get_permission_of_user');
    Route::post('addrole', 'SpatieController@Add_Role')->name('addrole')->middleware('permission:roles/add');
    Route::post('deleterole', 'SpatieController@Delete_Role')->name('deleterole')->middleware('permission:roles/delete');
    Route::post('assignrole', 'SpatieController@Assign_Role_to_user')->name('assignroletouser')->middleware('permission:roles/assign-to-user');
    Route::post('assigpertorole', 'SpatieController@Assign_Permission_Role')->name('assignpertorole')->middleware('permission:permissions/assign-to-role');
    Route::post('revokerole', 'SpatieController@Revoke_Role_from_user')->name('revokerolefromuser')->middleware('permission:roles/revoke-from-user');
    Route::get('haspermession', 'SpatieController@checkUserHavePermession')->name('haspermession');
    Route::post('getrolebyid', 'SpatieController@Get_Role')->name('getrolebyid')->middleware('permission:roles/get');
    Route::post('updaterolebyid', 'SpatieController@Update_Role')->name('updaterolebyid')->middleware('permission:roles/update');
    Route::post('revokepermissionfromrole', 'SpatieController@Revoke_Permission_from_Role')->name('revokepermissionfromrole')->middleware('permission:permissions/revoke-from-role');
    Route::get('listrandp', 'SpatieController@List_Roles_Permissions')->name('listpermissionandrole')->middleware('permission:spatie/list-permissions-and-roles');
    Route::post('addpertouser', 'SpatieController@Assign_Permission_User')->name('addpertouser')->middleware('permission:permissions/assign-to-user');
    Route::get('listrolewithper', 'SpatieController@List_Roles_With_Permission')->name('listRolewithPer')->middleware('permission:spatie/list-role-with-permissions');
    Route::post('getRoleWithPermission', 'SpatieController@Get_Individual_Role')->name('getRoleWithPermission')->middleware('permission:spatie/get-role-with-permissions');
    Route::post('addRolewithPer', 'SpatieController@Add_Role_With_Permissions')->name('addRolewithPer')->middleware('permission:spatie/add-role-with-permissions');
    Route::get('exportroleswithper', 'SpatieController@Export_Role_with_Permission')->name('exportroleswithper')->middleware('permission:spatie/export');
    Route::post('importroleswithper', 'SpatieController@Import_Role_with_Permission')->name('importroleswithper')->middleware('permission:spatie/import');

    //Notifications Routes
    Route::group(['prefix' => 'notification'], function () {
        Route::get('getall', 'NotificationController@getallnotifications')->name('getallnotifications')->middleware('permission:notifications/get-all');
        Route::get('unread', 'NotificationController@unreadnotifications')->name('getunreadnotifications')->middleware('permission:notifications/get-unread');
        Route::get('read', 'NotificationController@markasread')->name('readnotification')->middleware('permission:notifications/mark-as-read');
    });
    Route::post('deleteannounce', 'AnnouncementController@delete_announcement')->name('deleteannounce')->middleware('permission:announcements/delete');
    Route::post('announce', 'AnnouncementController@announcement')->name('announce')->middleware('permission:announcements/send');
});

//Year Routes
Route::group(['prefix' => 'year', 'middleware' => 'auth:api'], function () {
    Route::post('add', 'AcademicYearController@store')->name('addyear')->middleware('permission:year/add');
    Route::get('get', 'AcademicYearController@index')->name('getallyears')->middleware('permission:year/get-all');
    Route::post('getById', 'AcademicYearController@show')->name('getyearbyid')->middleware('permission:year/get');
    Route::put('update', 'AcademicYearController@update')->name('updateyear')->middleware('permission:year/update');
    Route::delete('delete', 'AcademicYearController@destroy')->name('deleteyear')->middleware('permission:year/delete');
});

//Type Routes
Route::group(['prefix' => 'type', 'middleware' => 'auth:api'], function () {
    //if you want to delete an Academic year type please,write deletetype in yours
    Route::post('delete', 'AC_year_type@deleteType')->name('deletetype')->middleware('permission:type/delete');

    //if you want to add an Academic year type to year please,write addtype in yours
    Route::post('add', 'AC_year_type@Add_type_to_Year')->name('addtype')->middleware('permission:type/add');

    //if you want to get all Academic year with all types please,write getyearswithtype in yours
    Route::get('get', 'AC_year_type@List_Years_with_types')->name('getyearswithtype')->middleware('permission:type/get-all');

    //if you want to update type please,write updatetype in yours
    Route::post('update', 'AC_year_type@updateType')->name('updatetype')->middleware('permission:type/update');

    //if you want to assign type to another year please,write assigntype in yours
    Route::post('assign', 'AC_year_type@Assign_to_anther_year')->name('assigntype')->middleware('permission:type/assign');
});

//Level Routes
Route::group(['prefix' => 'level', 'middleware' => 'auth:api'], function () {
    Route::post('add', 'LevelsController@AddLevelWithYear')->name('addlevel')->middleware('permission:level/add');
    Route::get('get', 'LevelsController@GetAllLevelsInYear')->name('getlevels')->middleware('permission:level/get-all');
    Route::post('delete', 'LevelsController@Delete')->name('deletelevel')->middleware('permission:level/delete');
    Route::post('update', 'LevelsController@UpdateLevel')->name('updatelevel')->middleware('permission:level/update');
});

//Class Routes
Route::group(['prefix' => 'class', 'middleware' => 'auth:api'], function () {
    Route::post('add', 'ClassController@AddClassWithYear')->name('addclass')->middleware('permission:class/add');
    Route::get('get', 'ClassController@index')->name('getallclasses')->middleware('permission:class/get-all');
    Route::get('get/{id}', 'ClassController@show')->name('getclassbyid')->middleware('permission:class/get');
    Route::put('update', 'ClassController@update')->name('updateclass')->middleware('permission:class/update');
    Route::delete('delete', 'ClassController@destroy')->name('deleteclass')->middleware('permission:class/delete');
});

//Segment Routes
Route::group(['prefix' => 'segment', 'middleware' => 'auth:api'], function () {
    //if you want to add segment to class please,write addsegment in yours
    Route::post('add', "segment_class_Controller@Add_Segment_with_class")->name('addsegment')->middleware('permission:segment/add');

    //if you want to delete segment please,write deletesegment in yours
    Route::post('delete', "segment_class_Controller@deleteSegment")->name('deletesegment')->middleware('permission:segment/delete');

    //if you want to assign segment to another class please,write assignsegment in yours
    Route::post('assign', "segment_class_Controller@Assign_to_anther_Class")->name('assignsegment')->middleware('permission:segment/assign');

    //if you want to get classes with all segments please,write getclasses in yours
    Route::get('get', "segment_class_Controller@List_Classes_with_all_segment")->name('getclasses')->middleware('permission:segment/get-all');

    Route::post('update', "segment_class_Controller@update")->name('updatesegment')->middleware('permission:segment/update');
});

//Category Routes
Route::group(['prefix' => 'category', 'middleware' => 'auth:api'], function () {
    Route::post('add', 'CategoryController@add')->name('addcategory')->middleware('permission:category/add');
    Route::post('update', 'CategoryController@edit')->name('editcategory')->middleware('permission:category/update');
    Route::post('delete', 'CategoryController@delete')->name('deletecategory')->middleware('permission:category/delete');
    Route::get('get', 'CategoryController@get')->name('getcategory')->middleware('permission:category/get-all');
});

//Course Routes
Route::group(['prefix' => 'course', 'middleware' => 'auth:api'], function () {
    Route::post('add', 'CourseController@add')->name('addcourse')->middleware('permission:course/add');
    Route::post('update', 'CourseController@update')->name('editcourse')->middleware('permission:course/update');
    Route::post('delete', 'CourseController@delete')->name('deletecourse')->middleware('permission:course/delete');
    Route::get('get', 'CourseController@get')->name('getcourse')->middleware('permission:course/get-all');
    Route::get('my', 'CourseController@MyCourses')->name('mycourses')->middleware('permission:course/my-courses');
    Route::post('layout','CourseController@GetUserCourseLessons')->name('layout')->middleware('permission:course/layout');
});

//USER CRUD ROUTES
Route::group(['prefix' => 'user', 'middleware' => 'auth:api'], function () {
    Route::post('add', 'UserController@create')->name('adduser')->middleware('permission:user/add');
    Route::post('update', 'UserController@update')->name('updateuser')->middleware('permission:user/update');
    Route::post('delete', 'UserController@delete')->name('deleteuser')->middleware('permission:user/delete');
    Route::get('get', 'UserController@list')->name('listAll')->middleware('permission:user/get-all');
    Route::post('suspend', 'UserController@suspend_user')->name('suspenduser')->middleware('permission:user/suspend');
    Route::post('unsuspend', 'UserController@unsuspend_user')->name('unsuspenduser')->middleware('permission:user/un-suspend');
});

//Enroll Routes
Route::group(['prefix' => 'enroll' , 'middleware' => 'auth:api'], function () {
    Route::post('/', 'EnrollUserToCourseController@EnrollCourses')->name('EnrollCourses')->middleware('permission:enroll/enroll-single-user');
    Route::post('unenroll', 'EnrollUserToCourseController@UnEnroll')->name('UnEnrollUsers')->middleware('permission:enroll/un-enroll-single-user');
    Route::get('getAll', 'EnrollUserToCourseController@ViewAllCoursesThatUserErollment')->name('EnrolledCourse')->middleware('permission:enroll/get-enrolled-courses');
    Route::post('mandatory', 'EnrollUserToCourseController@EnrollInAllMandatoryCourses')->name('EnrollMandatory')->middleware('permission:enroll/mandatory-course');
    Route::post('enrollexcel', 'EnrollUserToCourseController@EnrollExistUsersFromExcel')->name('enrollexcel')->middleware('permission:enroll/bulk-of-exist-users');
    Route::post('usertech', 'EnrollUserToCourseController@AddAndEnrollBulkOfNewUsers')->name('usertech')->middleware('permission:enroll/add-and-enroll-bulk-of-new-users');
    Route::get('GetEnrolledStudent', 'EnrollUserToCourseController@GetEnrolledStudents')->name('enrolledusers')->middleware('permission:enroll/enrolled-users');
});


//Messages Routes
Route::group(['prefix' => 'Messages', 'middleware' => 'auth:api'], function () {
    Route::post('send', 'MessageController@Send_message_of_all_user')->name('sendMessage')->middleware('permission:messages/send');
    Route::post('deleteForall', 'MessageController@deleteMessageForAll')->name('deleteMessageforall')->middleware('permission:messages/delete-for-all');
    Route::post('deleteForMe', 'MessageController@deleteMessageforMe')->name('deleteMessageforMe')->middleware('permission:messages/delete-for-me');
    Route::post('Seen', 'MessageController@SeenMessage')->name('SeenMessage')->middleware('permission:messages/seen');
    Route::get('ViewFromTo', 'MessageController@ViewAllMSG_from_to')->name('ViewFromTo')->middleware('permission:messages/get-from-to');
    Route::post('Message_add_send_Permission_for_role', 'MessageController@add_send_Permission_for_role')->name('Message_add_send_Permission_for_role')->middleware('permission:messages/add-send-permission-for-role');

    //Contact Route
    Route::post('addContact', 'ContactController@addContact')->name('addContact')->middleware('permission:contact/add');

    // Route::post('update', 'MessageController@edit')->name('editcategory')->middleware('permission:Update Category');
    // Route::post('delete', 'MessageController@delete')->name('deletecategory')->middleware('permission:Delete Category');
    // Route::get('get', 'MessageController@get')->name('getcategory')->middleware('permission:Get Categories');
});


Route::group(['prefix' => 'Component', 'middleware' => 'auth:api'], function () {
    Route::get('get', 'ComponentController@GetInstalledComponents')->name('getcomponent')->middleware('permission:component/get');
    Route::post('install', 'ComponentController@Install')->name('installcomponenet')->middleware('permission:component/install');
    Route::post('uninstall', 'ComponentController@Uninstall')->name('uninstallcomponenet')->middleware('permission:component/uninstall');
    Route::put('toggle', 'ComponentController@ToggleActive')->name('togglecomponenet')->middleware('permission:component/toggle');
});

Route::group(['prefix' => 'lesson', 'middleware' => 'auth:api'], function () {
    Route::post('add', 'LessonController@AddLesson')->name('addLesson')->middleware('permission:lesson/add');
    Route::get('get', 'LessonController@ShowLesson')->name('showlesson')->middleware('permission:lesson/get');
    Route::post('delete', 'LessonController@deleteLesson')->name('deleteLesson')->middleware('permission:lesson/delete');
    Route::post('update', 'LessonController@updateLesson')->name('updateLesson')->middleware('permission:lesson/update');
    Route::post('sort', 'LessonController@Sorting')->name('sortlesson')->middleware('permission:lesson/sort');
});
