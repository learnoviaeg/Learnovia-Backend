<?php
Route::get('/' , 'AuthController@site');
//install all permissions and roles of system Route
Route::get('install', 'SpatieController@install');
//Login and Signup
Route::group(['prefix' => 'auth'], function () {
    Route::post('login', 'AuthController@login')->name('login');
    Route::post('signup', 'AuthController@signup')->name('signup');
});

Route::group(['middleware' => ['auth:api']], function () {
    //user main routes without permissions
    Route::get('userRole', 'AuthController@userRole')->name('userRole');
    Route::get('logout', 'AuthController@logout')->name('logout');
    Route::get('getuserPermession', 'AuthController@getuserPermession');
    Route::get('user', 'AuthController@user')->name('user');
    Route::post('CheckPermission', 'SpatieController@checkPermessionOnCourse')->name('checkPermessionOnCourse');
    Route::get('getMyLimits', 'AuthController@getuserPermessionFlags')->name('getuserPermessionFlags');
    Route::post('comparepermissions', 'SpatieController@comparepermissions')->name('comparepermissions');
    Route::get('dashboard', 'SpatieController@dashboard')->name('dashboard');
    Route::get('spatie', 'SpatieController@index')->name('spatie');
    Route::post('comparepermissions', 'SpatieController@comparepermissions');

    //dashboard routes
    Route::post('dashboard/toggle', 'SpatieController@Toggle_dashboard')->name('toggleDashboard')->middleware('permission:dashboard/toggle');
    Route::post('dashboard/dashboard', 'SpatieController@dashboard')->name('toggleDashboard')->middleware('permission:dashboard/toggle');

    //Spatie Routes
    Route::group(['prefix' => 'spatie'], function () {
        Route::get('list-role-with-permissions', 'SpatieController@List_Roles_With_Permission')->name('listRolewithPer')->middleware('permission:spatie/list-role-with-permissions');
        Route::post('get-role-with-permissions', 'SpatieController@Get_Individual_Role')->name('getRoleWithPermission')->middleware('permission:spatie/get-role-with-permissions');
        Route::post('add-role-with-permissions', 'SpatieController@Add_Role_With_Permissions')->name('addRolewithPer')->middleware('permission:spatie/add-role-with-permissions');
        Route::get('export', 'SpatieController@Export_Role_with_Permission')->name('exportroleswithper')->middleware('permission:spatie/export');
        Route::post('import', 'SpatieController@Import_Role_with_Permission')->name('importroleswithper')->middleware('permission:spatie/import');
        Route::get('list-permissions-and-roles', 'SpatieController@List_Roles_Permissions')->name('listpermissionandrole')->middleware('permission:spatie/list-permissions-and-roles');
    });

    //permissions routes
    Route::group(['prefix' => 'permissions'], function () {
        Route::post('get-permission-of-user', 'SpatieController@Get_permission_of_user')->name('getperforrole')->middleware('permission:permissions/get-permission-of-user');
        Route::post('assign-to-role', 'SpatieController@Assign_Permission_Role')->name('assignpertorole')->middleware('permission:permissions/assign-to-role');
        Route::post('revoke-from-role', 'SpatieController@Revoke_Permission_from_Role')->name('revokepermissionfromrole')->middleware('permission:permissions/revoke-from-role');
        Route::post('assign-to-user', 'SpatieController@Assign_Permission_User')->name('addpertouser')->middleware('permission:permissions/assign-to-user');
        Route::get('check-user-has-permission', 'SpatieController@checkUserHavePermession')->name('haspermession')->middleware('permission:permissions/check-user-has-permission');
    });

    //roles routes
    Route::group(['prefix' => 'roles'], function () {
        Route::post('add', 'SpatieController@Add_Role')->name('addrole')->middleware('permission:roles/add');
        Route::post('delete', 'SpatieController@Delete_Role')->name('deleterole')->middleware('permission:roles/delete');
        Route::post('assign-to-user', 'SpatieController@Assign_Role_to_user')->name('assignroletouser')->middleware('permission:roles/assign');
        Route::post('revoke-from-user', 'SpatieController@Revoke_Role_from_user')->name('revokerolefromuser')->middleware('permission:roles/revoke-from-user');
        Route::post('get', 'SpatieController@Get_Role')->name('getrolebyid')->middleware('permission:roles/get');
        Route::post('update', 'SpatieController@Update_Role')->name('updaterolebyid')->middleware('permission:roles/update');
    });

    //Notifications Routes
    Route::group(['prefix' => 'notifications'], function () {
        Route::get('get-all', 'NotificationController@getallnotifications')->name('getallnotifications')->middleware('permission:notifications/get-all');
        Route::get('get-unread', 'NotificationController@unreadnotifications')->name('getunreadnotifications')->middleware('permission:notifications/get-unread');
        Route::get('mark-as-read', 'NotificationController@markasread')->name('readnotification')->middleware('permission:notifications/mark-as-read');
        Route::get('get-for-user', 'NotificationController@GetNotifcations')->name('readnotification')->middleware('permission:notifications/get-for-user');
        Route::get('delete-duration', 'NotificationController@DeletewithTime')->name('readnotification')->middleware('permission:notifications/delete-duration');
        Route::post('seen', 'NotificationController@SeenNotifications')->name('seennotification')->middleware('permission:notifications/seen');
    });

    //Announcements Routes
    Route::group(['prefix' => 'announcements'], function () {
        Route::post('delete', 'AnnouncementController@delete_announcement')->name('deleteannounce')->middleware('permission:announcements/delete');
        Route::post('send', 'AnnouncementController@announcement')->name('announce')->middleware('permission:announcements/send');
        Route::get('get', 'AnnouncementController@get')->name('get')->middleware('permission:announcements/get');
        Route::post('update', 'AnnouncementController@update_announce')->name('updateannounce')->middleware('permission:announcements/update');
        Route::get('getbyid', 'AnnouncementController@getAnnounceByID')->name('getbyid')->middleware('permission:announcements/getbyid');
    });

    //languages routes
    Route::group(['prefix' => 'languages'], function () {
        Route::get('get-active', 'SystemSettingsController@GetActiveLanguages')->name('getActiveLanguages');
        Route::get('get-default', 'SystemSettingsController@GetDefaultLanguage')->name('getDefaultLanguages');
        Route::post('activate', 'SystemSettingsController@ActivateLanguage')->name('activateLanguage')->middleware('permission:languages/activate');
        Route::post('deactivate', 'SystemSettingsController@DeActivateLanguage')->name('deactivateLanguage')->middleware('permission:languages/deactivate');
        Route::post('add', 'SystemSettingsController@AddLanguage')->name('addLanguage')->middleware('permission:languages/add');
        Route::post('set-default', 'SystemSettingsController@SetDefaultLanguage')->name('setDefaultLanguage')->middleware('permission:languages/set-default');
    });

    //Calendar Route
    Route::group(['prefix' => 'calendar'] , function(){
        Route::get('get', 'CalendarController@Calendar')->name('calendar')->middleware('permission:calendar/get');
        Route::get('weekly', 'CalendarController@weeklyCalender')->name('calendar')->middleware('permission:calendar/weekly');
    });

    //Import Excel Route
    Route::post('import', 'ExcelController@import')->name('import')->middleware('permission:import');
});

//Year Routes
Route::group(['prefix' => 'year', 'middleware' => ['auth:api']], function () {
    Route::post('add', 'AcademicYearController@store')->name('addyear')->middleware('permission:year/add');
    Route::get('get', 'AcademicYearController@get')->name('getyear')->middleware('permission:year/get');
    Route::get('get-all', 'AcademicYearController@getall')->name('getallyear')->middleware('permission:year/get-all');
    Route::post('update', 'AcademicYearController@update')->name('updateyear')->middleware('permission:year/update');
    Route::post('delete', 'AcademicYearController@destroy')->name('deleteyear')->middleware('permission:year/delete');
    Route::post('set-current', 'AcademicYearController@setCurrent_year')->name('SetCurrentYear')->middleware('permission:year/set-current');
});

//Type Routes
Route::group(['prefix' => 'type', 'middleware' => ['auth:api']], function () {
    Route::post('delete', 'AC_year_type@deleteType')->name('deletetype')->middleware('permission:type/delete');
    Route::post('add', 'AC_year_type@Add_type_to_Year')->name('addtype')->middleware('permission:type/add');
    Route::get('get', 'AC_year_type@List_Years_with_types')->name('getyearswithtype')->middleware('permission:type/get');
    Route::get('get-all', 'AC_year_type@get')->name('gettypes')->middleware('permission:type/get-all');
    Route::post('update', 'AC_year_type@updateType')->name('updatetype')->middleware('permission:type/update');
    Route::post('assign', 'AC_year_type@Assign_to_anther_year')->name('assigntype')->middleware('permission:type/assign');
});

//Level Routes
Route::group(['prefix' => 'level', 'middleware' => ['auth:api']], function () {
    Route::post('add', 'LevelsController@AddLevelWithYear')->name('addlevel')->middleware('permission:level/add');
    Route::get('get', 'LevelsController@GetAllLevelsInYear')->name('getlevels')->middleware('permission:level/get');
    //without year or type request
    Route::get('get-all', 'LevelsController@get')->name('getlevels')->middleware('permission:level/get-all');
    Route::post('delete', 'LevelsController@Delete')->name('deletelevel')->middleware('permission:level/delete');
    Route::post('update', 'LevelsController@UpdateLevel')->name('updatelevel')->middleware('permission:level/update');
    Route::post('assign', 'LevelsController@Assign_level_to')->name('assignlevel')->middleware('permission:level/assign');
});

//Class Routes
Route::group(['prefix' => 'class', 'middleware' => ['auth:api']], function () {
    Route::post('add', 'ClassController@AddClassWithYear')->name('addclass')->middleware('permission:class/add');
    Route::get('get', 'ClassController@index')->name('getallclasses')->middleware('permission:class/get');
    //without any parameters
    Route::get('get-all', 'ClassController@show')->name('getallclass')->middleware('permission:class/get-all');
    Route::post('update', 'ClassController@update')->name('updateclass')->middleware('permission:class/update');
    Route::post('delete', 'ClassController@destroy')->name('deleteclass')->middleware('permission:class/delete');
    Route::post('assign', 'ClassController@Assign_class_to')->name('assignclass')->middleware('permission:class/assign');
});

//Segment Routes
Route::group(['prefix' => 'segment', 'middleware' => ['auth:api']], function () {
    Route::post('add', "segment_class_Controller@Add_Segment_with_class")->name('addsegment')->middleware('permission:segment/add');
    Route::post('delete', "segment_class_Controller@deleteSegment")->name('deletesegment')->middleware('permission:segment/delete');
    Route::post('assign', "segment_class_Controller@Assign_to_anther_Class")->name('assignsegment')->middleware('permission:segment/assign');
    Route::get('get', "segment_class_Controller@List_Classes_with_segments")->name('getclasses')->middleware('permission:segment/get');
    Route::get('get-all', "segment_class_Controller@get")->name('getclasses')->middleware('permission:segment/get-all');
    Route::post('update', "segment_class_Controller@update")->name('updatesegment')->middleware('permission:segment/update');
    Route::post('set-current', 'segment_class_Controller@setCurrent_segmant')->name('SetCurrentSegment')->middleware('permission:segment/set-current');
});

//Category Routes
Route::group(['prefix' => 'category', 'middleware' => ['auth:api']], function () {
    Route::post('add', 'CategoryController@add')->name('addcategory')->middleware('permission:category/add');
    Route::post('update', 'CategoryController@edit')->name('editcategory')->middleware('permission:category/update');
    Route::post('delete', 'CategoryController@delete')->name('deletecategory')->middleware('permission:category/delete');
    Route::get('get-all', 'CategoryController@get')->name('getcategory')->middleware('permission:category/get-all');
});

//Course Routes
Route::group(['prefix' => 'course', 'middleware' => ['auth:api']], function () {
    Route::post('add', 'CourseController@add')->name('addcourse')->middleware('permission:course/add');
    Route::post('update', 'CourseController@update')->name('editcourse')->middleware('permission:course/update');
    Route::post('delete', 'CourseController@delete')->name('deletecourse')->middleware('permission:course/delete');
    Route::get('get-all', 'CourseController@get')->name('getcourse')->middleware('permission:course/get-all');
    Route::get('my-courses', 'CourseController@CurrentCourses')->name('mycourses')->middleware(['permission:course/my-courses' , 'ParentCheck']);
    Route::get('all-courses', 'CourseController@EnrolledCourses')->name('enrolledcourses')->middleware(['permission:course/all-courses' , 'ParentCheck']);
    Route::get('past-courses', 'CourseController@PastCourses')->name('pastcourses')->middleware(['permission:course/past-courses' , 'ParentCheck']);
    Route::get('future-courses', 'CourseController@FutureCourses')->name('futurecourses')->middleware(['permission:course/future-courses' , 'ParentCheck']);
    Route::get('layout', 'CourseController@GetUserCourseLessons')->name('layout')->middleware(['permission:course/layout' , 'ParentCheck']);
    Route::get('optional', 'CourseController@getCoursesOptional')->name('optional')->middleware('permission:course/optional');
    Route::post('assgin-course-to', 'CourseController@Assgin_course_to')->name('assgincourseto')->middleware('permission:course/assgin-course-to');
    Route::get('course-with-teacher', 'CourseController@course_with_teacher')->name('coursewithteacher')->middleware('permission:course/course-with-teacher');
    Route::get('sorted-componenets', 'CourseController@GetUserCourseLessonsSorted')->middleware(['permission:course/sorted-componenets' , 'ParentCheck']);
    Route::post('toggle/letter', 'CourseController@ToggleCourseLetter')->middleware('permission:course/toggle/letter');
    Route::get('count-components', 'CourseController@Count_Components')->middleware(['permission:course/count-components' , 'ParentCheck']);
    Route::get('chain', 'CourseController@getAllCoursesWithChain')->middleware('permission:course/chain');
    Route::get('components', 'CourseController@getAllMyComponenets')->middleware(['permission:course/components' , 'ParentCheck']);
    Route::get('lessons', 'CourseController@getLessonsFromCourseAndClass')->middleware('permission:course/lessons');
});

//USER CRUD ROUTES
Route::group(['prefix' => 'user', 'middleware' => ['auth:api']], function () {
    Route::post('add', 'UserController@create')->name('adduser')->middleware('permission:user/add');
    Route::post('update', 'UserController@update')->name('updateuser')->middleware('permission:user/update');
    Route::post('delete', 'UserController@delete')->name('deleteuser')->middleware('permission:user/delete');
    Route::get('get-all', 'UserController@list')->name('listAll')->middleware('permission:user/get-all');
    Route::post('suspend', 'UserController@suspend_user')->name('suspenduser')->middleware('permission:user/suspend');
    Route::get('show-hide-real-pass', 'UserController@Show_and_hide_real_password_with_permission')->name('show/hiderealpass')->middleware('permission:user/show-hide-real-pass');
    Route::post('un-suspend', 'UserController@unsuspend_user')->name('unsuspenduser')->middleware('permission:user/un-suspend');
    Route::get('parent-child', 'UserController@parent_child')->name('parentchild')->middleware('permission:user/parent-child');
    Route::get('get-by-id', 'UserController@GetUserById')->name('getbyid')->middleware('permission:user/get-by-id');
    Route::get('get-with-role-cs', 'UserController@get_users_with_filter_role')->name('getbyroleid')->middleware('permission:user/get-with-role-cs');
    Route::get('filter-with-role', 'UserController@allUserFilterRole')->name('filterallbyrole')->middleware('permission:user/filter-with-role');
    Route::get('search-all-users', 'UserController@getAllUsersInCourseSegment')->name('searchusers')->middleware('permission:user/search-all-users');
    Route::get('overview-report', 'UserController@Overview_Report')->name('getusergrade')->middleware('permission:grade/user/get');

    Route::get('get-someone-children', 'UserController@getSomeoneChildren')->name('getsomeonechild')->middleware('permission:user/get-someone-child');

    Route::get('get-someone-parent', 'UserController@getSomeoneParent')->name('getsomeoneparent')->middleware('permission:user/get-someone-parent');

    Route::get('get-my-children', 'UserController@getMyChildren')->name('getmychild')->middleware('permission:user/get-my-child');

    Route::post('current-child', 'UserController@SetCurrentChild')->name('currentchild')->middleware('permission:user/current-child');

});
//Enroll Routes
Route::group(['prefix' => 'enroll', 'middleware' => ['auth:api']], function () {
    Route::post('enroll-single-user', 'EnrollUserToCourseController@EnrollCourses')->name('EnrollCourses')->middleware('permission:enroll/single');
    Route::post('un-enroll-single-user', 'EnrollUserToCourseController@UnEnroll')->name('UnEnrollUsers')->middleware('permission:enroll/un-enroll-single-user');
    Route::get('get-enrolled-courses', 'EnrollUserToCourseController@ViewAllCoursesThatUserEnrollment')->name('EnrolledCourse')->middleware('permission:enroll/get-enrolled-courses');
    Route::post('mandatory-course', 'EnrollUserToCourseController@EnrollInAllMandatoryCourses')->name('EnrollMandatory')->middleware('permission:enroll/mandatory');
    Route::post('bulk-of-exist-users', 'EnrollUserToCourseController@EnrollExistUsersFromExcel')->name('enrollexcel')->middleware('permission:enroll/bulk-of-exist-users');
    Route::post('add-and-enroll-bulk-of-new-users', 'EnrollUserToCourseController@AddAndEnrollBulkOfNewUsers')->name('usertech')->middleware('permission:enroll/add-and-enroll-bulk-of-new-users');
    Route::get('enrolled-users', 'EnrollUserToCourseController@GetEnrolledStudents')->name('enrolledusers')->middleware('permission:enroll/enrolled-users');
    Route::get('get-unenroll-users', 'EnrollUserToCourseController@getUnEnroll')->name('getUnEnroll')->middleware('permission:enroll/get-unenroll-users');
    Route::get('get-unenrolled-users-Bulk', 'EnrollUserToCourseController@unEnrolledUsersBulk')->name('getUnEnrolleduser')->middleware('permission:enroll/get-unenrolled-users-Bulk');
    Route::post('users', 'EnrollUserToCourseController@enrollWithChain')->name('Enrollusers')->middleware('permission:enroll/users');
    Route::post('migrate-user', 'EnrollUserToCourseController@Migration')->name('migrateuser')->middleware('permission:enroll/migrate-user');
});

//Messages Routes
Route::group(['prefix' => 'messages', 'middleware' => ['auth:api']], function () {
    Route::post('send', 'MessageController@Send_message_of_all_user')->name('sendMessage')->middleware('permission:messages/send');
    Route::post('delete-for-all', 'MessageController@deleteMessageForAll')->name('deleteMessageforall')->middleware('permission:messages/delete-for-all');
    Route::post('delete-for-me', 'MessageController@deleteMessageforMe')->name('deleteMessageforMe')->middleware('permission:messages/delete-for-me');
    Route::post('seen', 'MessageController@SeenMessage')->name('SeenMessage')->middleware('permission:messages/seen');
    Route::get('get-from-to', 'MessageController@ViewAllMSG_from_to')->name('ViewFromTo')->middleware('permission:messages/get-from-to');
    Route::post('add-send-permission-for-role', 'MessageController@add_send_Permission_for_role')->name('Message_add_send_Permission_for_role')->middleware('permission:messages/add-send-permission-for-role');
    Route::get('mythreads', 'MessageController@GetMyThreads')->name('mythreads')->middleware('permission:messages/mythreads');
    Route::get('users-assosiated-roles', 'MessageController@RolesWithAssiocatedUsers')->name('users-assosiated-roles')->middleware('permission:messages/users-assosiated-roles');
});

//Contact Route
Route::group(['prefix' => 'contact', 'middleware' => ['auth:api']], function () {
    Route::post('add', 'ContactController@addContact')->name('addContact')->middleware('permission:contact/add');
    Route::get('get', 'ContactController@ViewMyContact')->name('ViewMyContact')->middleware('permission:contact/get');
});

//component routes
Route::group(['prefix' => 'component', 'middleware' => ['auth:api']], function () {
    Route::get('get', 'ComponentController@GetInstalledComponents')->name('getcomponent')->middleware('permission:component/get');
    Route::post('install', 'ComponentController@Install')->name('installcomponenet')->middleware('permission:component/install');
    Route::post('uninstall', 'ComponentController@Uninstall')->name('uninstallcomponenet')->middleware('permission:component/uninstall');
    Route::put('toggle', 'ComponentController@ToggleActive')->name('togglecomponenet')->middleware('permission:component/toggle');
    Route::post('sort', 'ComponentController@sort')->name('sortcomponenet')->middleware('permission:component/sort');
    Route::post('change-color', 'ComponentController@ChangeColor')->name('changecolor')->middleware('permission:component/change-color');
});

//lesson routes
Route::group(['prefix' => 'lesson', 'middleware' => ['auth:api']], function () {
    Route::post('add', 'LessonController@AddLesson')->name('addLesson')->middleware('permission:lesson/add');
    Route::get('get', 'LessonController@ShowLesson')->name('showlesson')->middleware('permission:lesson/get');
    Route::post('delete', 'LessonController@deleteLesson')->name('deleteLesson')->middleware('permission:lesson/delete');
    Route::post('update', 'LessonController@updateLesson')->name('updateLesson')->middleware('permission:lesson/update');
    Route::post('sort', 'LessonController@Sorting')->name('sortlesson')->middleware('permission:lesson/sort');
    Route::post('sort', 'LessonController@Sorting')->name('sortlesson')->middleware('permission:lesson/sort');
    Route::post('addLessons', 'LessonController@AddNumberOfLessons')->name('addnumberoflessons')->middleware('permission:lesson/addBulk');
});

//grade routes
Route::group(['prefix' => 'grade', 'middleware' => ['auth:api']], function () {

    Route::group(['prefix' => 'category', 'middleware' => ['auth:api']], function () {
        Route::post('add', 'GradeCategoryController@AddGradeCategory')->middleware('permission:grade/category/add');
        Route::post('assign-bulk', 'GradeCategoryController@AssignBulkGradeCategory')->middleware('permission:grade/category/assign-bulk');
        Route::post('bulk', 'GradeCategoryController@addBulkGradeCategories')->middleware('permission:grade/category/bulk');
        Route::post('bulk-delete', 'GradeCategoryController@deleteBulkGradeCategories')->middleware('permission:grade/category/bulk-delete');
        Route::get('get', 'GradeCategoryController@GetGradeCategory')->middleware('permission:grade/category/get');
        Route::post('delete', 'GradeCategoryController@deleteGradeCategory')->middleware('permission:grade/category/delete');
        Route::post('update', 'GradeCategoryController@UpdateGradeCategory')->middleware('permission:grade/category/update');
        Route::post('move', 'GradeCategoryController@MoveToParentCategory')->middleware('permission:grade/category/move');
        Route::get('tree', 'GradeCategoryController@Get_Tree')->middleware('permission:grade/category/tree');
        Route::post('bulk-update', 'GradeCategoryController@bulkupdate')->middleware('permission:grade/category/bulk-update');
        Route::get('bulk-get', 'GradeCategoryController@GetAllGradeCategory')->middleware('permission:grade/category/bulk-get');
        Route::get('bulk-all-get', 'GradeCategoryController@GetGradeCategoryTree')->middleware('permission:grade/category/bulk-all-get');
    });

    Route::group(['prefix' => 'item', 'middleware' => ['auth:api']], function () {
        Route::post('add', 'GradeItemController@create')->name('addgrade')->middleware('permission:grade/item/add');
        Route::get('get', 'GradeItemController@list')->name('getgrade')->middleware('permission:grade/item/get');
        Route::get('get-bulk', 'GradeItemController@GetAllGradeItems')->name('getbulkgradeitem')->middleware('permission:grade/item/get-bulk');
        Route::post('delete', 'GradeItemController@delete')->name('deletegrade')->middleware('permission:grade/item/delete');
        Route::post('update', 'GradeItemController@update')->name('updategrade')->middleware('permission:grade/item/update');
        Route::get('grading-method', 'GradeItemController@gradeing_method')->name('gradingmethod')->middleware('permission:grade/item/grading-method');
        Route::post('move-category', 'GradeItemController@Move_Category')->name('movecategory')->middleware('permission:grade/item/move-category');
        Route::post('override', 'GradeItemController@override')->name('overridegradeitem')->middleware('permission:grade/item/override');
        Route::post('AddBulk', 'GradeItemController@AddBulk')->name('AddBulkgradeitem')->middleware('permission:grade/item/AddBulk');
        Route::post('bulk-delete', 'GradeItemController@deleteBulkGradeitems')->middleware('permission:grade/item/bulk-delete');
        Route::post('bulk-update', 'GradeItemController@bulkupdate')->middleware('permission:grade/item/bulk-update');
        Route::post('bulk-assign', 'GradeItemController@AssignBulk')->middleware('permission:grade/item/bulk-assign');
        Route::get('get-allowed-functions', 'GradeItemController@get_allowed_functions')->middleware('permission:grade/item/get-allowed-functions');

    });

    Route::group(['prefix' => 'user', 'middleware' => ['auth:api']], function () {
        Route::post('add', 'UserGradeController@create')->name('addusergrade')->middleware('permission:grade/user/add');
        Route::get('get', 'UserGradeController@list')->name('getusergrade')->middleware(['permission:grade/user/get' , 'ParentCheck']);
        Route::post('delete', 'UserGradeController@delete')->name('deleteusergrade')->middleware('permission:grade/user/delete');
        Route::post('update', 'UserGradeController@update')->name('updateusergrade')->middleware('permission:grade/user/update');
    });

    Route::group(['prefix' => 'report'] , function(){
        Route::get('grader' ,'UserGradeController@graderReport')->name('graderReport')->middleware('permission:grade/report/grader');
        Route::get('user', 'UserGradeController@SingleUserInSingleCourse')->name('getallusergrades')->middleware(['permission:grade/report/user' , 'ParentCheck']);
        //Route::post('getallGrades', 'UserGradeController@AllUserInCourse')->name('getallusersgrades')->middleware('permission:grade/report/overview');
        Route::post('over-all', 'UserGradeController@AllUserInAllCourses')->name('getalluserscoursesgrades')->middleware(['permission:grade/report/over-all' , 'ParentCheck']);
    });
});
Route::group(['prefix' => 'scale', 'middleware' => ['auth:api']], function () {
    Route::post('add', 'ScaleController@AddScale')->name('addscale')->middleware('permission:scale/add');
    Route::post('update', 'ScaleController@UpdateScale')->name('updatescale')->middleware('permission:scale/update');
    Route::post('delete', 'ScaleController@DeleteScale')->name('deletescale')->middleware('permission:scale/delete');
    Route::get('get', 'ScaleController@GetScale')->name('getscale')->middleware('permission:scale/get');
});

Route::group(['prefix' => 'letter', 'middleware' => ['auth:api']], function () {
    Route::post('add', 'LetterController@add')->name('addletter')->middleware('permission:letter/add');
    Route::post('update', 'LetterController@update')->name('updateletter')->middleware('permission:letter/update');
    Route::post('delete', 'LetterController@delete')->name('deleteletter')->middleware('permission:letter/delete');
    Route::get('get', 'LetterController@get')->name('getletter')->middleware('permission:letter/get');
    Route::post('assign', 'LetterController@assignLetterToCourse')->name('assignletter')->middleware('permission:letter/assign');
});

Route::group(['prefix' => 'event', 'middleware' => 'auth:api'], function () {
    Route::post('add', 'EventController@create')->name('addevent')->middleware('permission:event/add');
    Route::post('delete', 'EventController@delete')->name('deleteevent')->middleware('permission:event/delete');
    Route::post('update', 'EventController@update')->name('updateevent')->middleware('permission:event/update');
    Route::get('my-events', 'EventController@get_my_events')->name('myevent')->middleware('permission:event/my-events');
    Route::get('all-events', 'EventController@GetAllEvents')->name('allevent')->middleware('permission:event/all-events');
});
Route::post('search-contacts', 'ContactController@SearchMyContacts');
Route::post('search-messages', 'MessageController@SearchMessage');
Route::post('change-color', 'ComponentController@ChangeColor');
Route::post('search-specific-thread', 'MessageController@SearchSpecificThread')->middleware(['auth:api']);
