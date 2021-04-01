<?php

namespace App\Http\Controllers;

use App\Course;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\User;
use App\CourseSegment;
use Validator;
use Auth;
use App\Enroll;
use App\ItemType;
use App\Letter;
use App\Language;
use App\Contract;
use App\scale;
use Carbon\Carbon;
use DB;
use Modules\QuestionBank\Entities\Quiz;
use App\Http\Controllers\ExcelController;
use Maatwebsite\Excel\Facades\Excel;
use Modules\QuestionBank\Http\Controllers\QuestionBankController;
use Modules\UploadFiles\Http\Controllers\FilesController;
use Modules\Page\Http\Controllers\PageController;
use Modules\Bigbluebutton\Http\Controllers\BigbluebuttonController;
use Modules\Attendance\Http\Controllers\AttendanceSessionController;
use App\Http\Controllers\H5PLessonController;
use Modules\Assigments\Http\Controllers\AssigmentsController;
use App\Exports\ExportRoleWithPermissions;
use Illuminate\Support\Facades\Storage;
use App\Settings;

class SpatieController extends Controller
{
    public function install()
    {

        (new BigbluebuttonController)->clear();
        $user = User::whereEmail('admin@learnovia.com')->first();
        if ($user) {
            return "This Site is Installed before go and ask admin";
        } else {
            // initial Contract
            Contract::create(['attachment_id' => null, 'start_date' => Carbon::now(), 'end_date' => Carbon::now()->addYear(), 'numbers_of_users' => 500000000, 'total' => null, 'allowance_period' => null]);

            // restrict
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'site/restrict', 'title' => 'restrict middleware']);

            //import
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'import/import', 'title' => 'import']);

            //Message Permissiosns
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'messages/send', 'title' => 'send messages']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'messages/delete-for-all', 'title' => 'delete messages for all']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'messages/delete-for-me', 'title' => 'delete messages for me']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'messages/seen', 'title' => 'seen messages']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'messages/get-from-to', 'title' => 'get messages from to']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'messages/add-send-permission-for-role', 'title' => 'add permission to send message from role to role']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'messages/mythreads', 'title' => 'my chat threads']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'messages/users-assosiated-roles', 'title' => 'get roles assosiated with users']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'messages/bulk-messages', 'title' => 'send message to bulk users']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'messages/search-messages', 'title' => 'search messages']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'messages/search-specific-thread', 'title' => 'search specific chat thread']);

            //Notifications Permissiosns
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'notifications/get-all', 'title' => 'get all notifications']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'notifications/get-unread', 'title' => 'get unread notifications']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'notifications/mark-as-read', 'title' => 'mark notification as read']);
            // \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'notifications/get-for-user', 'title' => 'get user notifications']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'notifications/delete-duration', 'title' => 'delete notifications within a period']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'notifications/seen', 'title' => 'seen notifications']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'notifications/send', 'title' => 'send notifications']);

            //Spatie Permissions
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'roles/add', 'title' => 'Add Role' , 'dashboard' => 1, 'icon' => 'Role']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'roles/delete', 'title' => 'delete role']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'roles/get', 'title' => 'Roles Management', 'dashboard' => 1,  'icon' => 'Role']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'roles/update', 'title' => 'update role']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'roles/assign', 'title' => 'assign role to user']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'permissions/assign-to-role', 'title' => 'assign permission to role']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'roles/revoke-from-user', 'title' => 'revoke role from user']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'permissions/revoke-from-role', 'title' => 'revoke permission from role']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'spatie/list-permissions-and-roles', 'title' => 'list all permissions and roles']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'permissions/assign-to-user', 'title' => 'assign permission to user']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'spatie/list-role-with-permissions', 'title' => 'list role with permissions']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'spatie/get-role-with-permissions', 'title' => 'get role with permissions']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'spatie/add-role-with-permissions', 'title' => 'add role with permissions']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'spatie/export', 'title' => 'export roles and permissions']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'spatie/import', 'title' => 'import roles and permissions']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'permissions/get-permission-of-user', 'title' => 'get user permission']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'permissions/check-user-has-permission', 'title' => 'check user has permission']);

            //Year Permissions
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'year/add', 'title' => 'add year']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'year/get', 'title' => 'get year']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'year/update', 'title' => 'update year']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'year/delete', 'title' => 'delete year']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'year/set-current', 'title' => 'set current year']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'year/get-all', 'title' => 'get all years']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'year/get-my-years', 'title' => 'get all my years']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'year/export', 'title' => 'export all years']);


            //Type Permissions
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'type/delete', 'title' => 'delete type']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'type/add', 'title' => 'add type']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'type/get-all', 'title' => 'get all types']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'type/get', 'title' => 'get type']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'type/get-my-types', 'title' => 'get my type']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'type/update', 'title' => 'update type']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'type/assign', 'title' => 'assign type']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'type/export', 'title' => 'export all types']);


            //Level Permissions
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'level/add', 'title' => 'add level']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'level/update', 'title' => 'update level']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'level/get-all', 'title' => 'get all levels']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'level/get', 'title' => 'get level']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'level/get-my-levels', 'title' => 'get my levels']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'level/delete', 'title' => 'delete level']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'level/assign', 'title' => 'assign level']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'level/export', 'title' => 'export all levels']);

            //Class Permissions
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'class/add', 'title' => 'add class']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'class/get-all', 'title' => 'get all classes']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'class/get-my-classes', 'title' => 'get my classes']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'class/get', 'title' => 'get class']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'class/update', 'title' => 'update class']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'class/delete', 'title' => 'delete class']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'class/assign', 'title' => 'assign class']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'class/get-lessons', 'title' => 'get class lessons']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'class/export', 'title' => 'export all classes']);


            //Segment Permissions
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'segment/add', 'title' => 'add segment']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'segment/delete', 'title' => 'delete segment']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'segment/assign', 'title' => 'assign segment']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'segment/update', 'title' => 'update segment']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'segment/get-all', 'title' => 'get all segment']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'segment/get', 'title' => 'get segment']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'segment/set-current', 'title' => 'set current segment']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'segment/get-my-segments', 'title' => 'get my segment']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'segment/export', 'title' => 'export all segments']);


            //Cetegory Permissions
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'category/add', 'title' => 'add category']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'category/update', 'title' => 'update category']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'category/delete', 'title' => 'delete category']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'category/get-all', 'title' => 'get all categories']);

            //management
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'management/get', 'title' => 'Course Management' , 'dashboard' => 1, 'icon' => 'Settings']);
            
            //Course Permissions
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'course/add', 'title' => 'add course' , 'dashboard' => 0, 'icon' => 'Course']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'course/update', 'title' => 'update course']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'course/delete', 'title' => 'delete course']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'course/get-all', 'title' => 'All Courses' , 'dashboard' => 1, 'icon' => 'Course']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'course/all-courses', 'title' => 'get all my courses']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'course/my-courses', 'title' => 'get current courses']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'course/past-courses', 'title' => 'get past courses']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'course/future-courses', 'title' => 'get future courses']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'course/layout', 'title' => 'course layout']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'course/optional', 'title' => 'get optional courses']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'course/assgin-course-to', 'title' => 'assign course']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'course/course-with-teacher', 'title' => 'get course with teacher']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'course/sorted-componenets', 'title' => 'get course with sorted components']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'course/toggle/letter', 'title' => 'toggle letter in course']);
            // \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'site/course/getUserCourseLessons', 'title' => 'course sorted components']);
            // \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'site/course/current_courses', 'title' => 'current courses']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'course/count-components', 'title' => 'get count of all component']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'course/chain', 'title' => 'get all courses with chain']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'course/components', 'title' => 'get all course with components']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'course/lessons', 'title' => 'get all course with lessons']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'course/get-classes-by-course', 'title' => 'get all classes by course']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'course/get-courses-by-classes', 'title' => 'get all courses by classes']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'course/export', 'title' => 'export courses']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'course/teachers', 'title' => 'view course teachers']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'course/participants', 'title' => 'view course participants']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'course/progress-bar', 'title' => 'view course progress bar']);

            //Enroll Permissions
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'enroll/user', 'title' => 'Staff Enrollment' , 'dashboard' => 1, 'icon' => 'Star']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'enroll/un-enroll-single-user', 'title' => 'un-enroll user']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'enroll/get-enrolled-courses', 'title' => 'get enrolled courses']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'enroll/mandatory', 'title' => 'Student Enrollment' , 'dashboard' => 1 , 'icon' => 'Star']);
            // \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'enroll/bulk-of-exist-users', 'title' => 'enroll bulk of exist users(file)']);
            // \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'enroll/add-and-enroll-bulk-of-new-users', 'title' => 'add and enroll bulk of new users(file)']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'enroll/enrolled-users', 'title' => 'get enrolled users']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'enroll/get-unenroll-users', 'title' => 'get unenrolled users']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'enroll/get-unenrolled-users-Bulk', 'title' => 'get bulk of unenrolled users']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'enroll/users', 'title' => 'enroll users with chain']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'enroll/migrate-user', 'title' => 'migrate user to another class']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'enroll/get-unenroll-users-role', 'title' => 'Get unenrolled users']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'enroll/get', 'title' => 'Get Chain']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'enroll/delete', 'title' => 'Destroy Chain']);


            //Events
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'event/add', 'title' => 'Add event to users']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'event/delete', 'title' => 'delete event']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'event/update', 'title' => 'update event']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'event/my-events', 'title' => 'get my event']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'event/all-events', 'title' => 'get all events']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'event/add-bulk', 'title' => 'add bulk events']);


            //Contact Permissions
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'contact/add', 'title' => 'add contact']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'contact/get', 'title' => 'get contact']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'contact/search', 'title' => 'search contact']);

            //USER CRUD Permissions
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'user/add', 'title' => 'add user']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'user/update', 'title' => 'update user']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'user/delete', 'title' => 'delete user']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'user/get-all', 'title' => 'Users' , 'dashboard' => 1, 'icon' => 'User']);
            // \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'site/user/list', 'title' => 'get all user']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'user/suspend', 'title' => 'suspend user']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'user/un-suspend', 'title' => 'un suspend user']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'user/show-hide-real-pass', 'title' => 'shor and hide real password']);
            // \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'site/user/Show-and-hide-real-password-with-permission', 'title' => 'Show and hide real password with permission']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'user/parent-child', 'title' => 'get family']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'user/get-by-id', 'title' => 'get user by id']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'user/get-with-role-cs', 'title' => 'get users in course with filter role_id']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'user/filter-with-role', 'title' => 'filter all users with role']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'user/current-child', 'title' => 'set current child']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'user/get-current-child', 'title' => 'get current child']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'user/get-someone-child', 'title' => 'get child by parent_id']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'user/get-someone-parent', 'title' => 'get parent by child_id']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'user/get-my-child', 'title' => 'get my child']);
            // \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'user/search-all-users', 'title' => 'search all users']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'user/get-my-users', 'title' => 'get all my users']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'user/generate-username-password', 'title' => 'generate username and password']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'user/GetAllCountries', 'title' => 'Get all countries']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'user/GetAllNationalities', 'title' => 'Get all nationalities']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'user/set-parent-child', 'title' => 'Assign Parent','dashboard' => 1]);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'user/export', 'title' => 'Export Users']);


            //Components Permissions
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'component/get', 'title' => 'get component']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'component/install', 'title' => 'install component']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'component/uninstall', 'title' => 'uninstall component']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'component/toggle', 'title' => 'toggle component']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'component/sort', 'title' => 'sort component']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'component/change-color', 'title' => 'change component color']);

            //Announcements Permissions
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'announcements/delete', 'title' => 'delete announcements']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'announcements/send', 'title' => 'send announcements']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'announcement/filter-chain', 'title' => 'Announcement Filter Chain']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'announcements/get', 'title' => 'View Announcements', 'dashboard' => 1 , 'icon'=> 'announcement']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'announcements/update', 'title' => 'update announcements']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'announcements/getbyid', 'title' => 'get announcements by id']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'announcements/get-unread', 'title' => 'get unread announcements']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'announcements/mark-as-read', 'title' => 'mark announcements as read']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'announcements/my', 'title' => 'My Announcements'  , 'dashboard' => 1, 'icon'=> 'announcement']);


            //Calendar Permission
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'calendar/get', 'title' => 'get calendar']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'calendar/weekly', 'title' => 'get weekly calendar']);

            //Import
            // \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'import', 'title' => 'import excel sheet']);

            //Language Permission
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'languages/get', 'title' => 'Get Languages']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'languages/add', 'title' => 'Add Language']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'languages/update', 'title' => 'Update Language']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'languages/delete', 'title' => 'Delete Language']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'languages/dictionary', 'title' => 'Get Dictionary']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'user/language', 'title' => 'change my language']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'languages/activate', 'title' => 'Activate language']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'languages/deactivate', 'title' => 'Dea-ctivate language']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'languages/set-default', 'title' => 'Set default language']);



            
            //Lesson Permissions
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'lesson/add', 'title' => 'add lesson']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'lesson/get', 'title' => 'get lesson']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'lesson/delete', 'title' => 'delete lesson']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'lesson/update', 'title' => 'update lesson']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'lesson/sort', 'title' => 'sort lesson']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'lesson/addBulk', 'title' => 'add bulk lesson']);

            //Grade Ctegory
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'grade/category/add', 'title' => 'add grade category']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'grade/category/get', 'title' => 'get grade category']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'grade/category/delete', 'title' => 'delete grade category']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'grade/category/update', 'title' => 'update grade category']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'grade/category/move', 'title' => 'change parent of grade category']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'grade/category/tree', 'title' => 'grade category tree']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'grade/category/bulk-update', 'title' => 'update bulk grade category']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'grade/category/bulk-delete', 'title' => 'delete bulk grade category']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'grade/category/bulk', 'title' => 'add bulk grade category']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'grade/category/assign-bulk', 'title' => 'assign bulk grade category']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'grade/category/bulk-get', 'title' => 'get bulk grade category']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'grade/category/bulk-get-level', 'title' => 'get bulk grade category by levels']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'grade/category/bulk-all-get', 'title' => 'get bulk grade category with chain']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'grade/category/chain-categories', 'title' => 'get all chain grade category']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'grade/grades', 'title' => 'Grades', 'icon' => 'grade']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'grade/category/get-gradecategories', 'title' => 'Get Grade Categories']);


            //Grade Item
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'grade/item/add', 'title' => 'add grade item']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'grade/item/get', 'title' => 'get grade item']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'grade/item/delete', 'title' => 'delete grade item']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'grade/item/update', 'title' => 'update grade item']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'grade/item/move-category', 'title' => 'change parent grade category of grade item']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'grade/item/AddBulk', 'title' => 'add grade items category']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'grade/item/bulk-delete', 'title' => 'delete bulk grade items']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'grade/item/bulk-update', 'title' => 'update bulk grade items']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'grade/item/bulk-assign', 'title' => 'assign bulk grade items']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'grade/item/get-bulk', 'title' => 'get bulk grade items']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'grade/item/grading-method', 'title' => 'get all grading methods']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'grade/item/get-allowed-functions', 'title' => 'get allowed mathematical functions']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'grade/item/override', 'title' => 'override grade item']);

            //User Grade
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'grade/user/add', 'title' => 'add user grade']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'grade/user/get', 'title' => 'get user grade']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'grade/user/update', 'title' => 'update user grade']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'grade/user/delete', 'title' => 'delete user grade']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'grade/user/course-grade', 'title' => 'course\'s user and grades']);

            //Grades Reports
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'grade/report/grader', 'title' => 'grader report']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'grade/report/overview', 'title' => 'overview report']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'grade/report/user', 'title' => 'get user grades']);
            //\Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'grade/user/getallusersgrades', 'title' => 'get all users grades']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'grade/report/over-all', 'title' => 'get all users grades in courses']);
            //dashboard
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'dashboard/toggle', 'title' => 'toggle dashboard']);
            // \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'dashboard/dashboard', 'title' => 'show dashboard']);

            //scale
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'scale/add', 'title' => 'Add Scale']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'scale/update', 'title' => 'Update Scale']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'scale/delete', 'title' => 'Delete Scale']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'scale/get', 'title' => 'Get Scale']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'scale/get-with-course', 'title' => 'Get Scale with course']);

            //scale
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'letter/add', 'title' => 'Add Letter']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'letter/update', 'title' => 'Update Letter']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'letter/delete', 'title' => 'Delete Letter']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'letter/get', 'title' => 'Get Letter']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'letter/assign', 'title' => 'assign Letter']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'letter/get-with-course', 'title' => 'get letter with course']);


            //contract
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'contract/add', 'title' => 'add contract']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'contract/update', 'title' => 'update contract']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'contract/restrict', 'title' => 'restrict contract']);

            //payment
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'payment/add', 'title' => 'add payment']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'payment/delete', 'title' => 'delete payment']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'payment/postponed-payment', 'title' => 'postpond payment']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'payment/pay-payment', 'title' => 'pay payment']);
            
            //chat
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'chat/add-room', 'title' => 'add room']);

            //report
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'reports/active_users', 'title' => 'Active users report', 'dashboard' => 1, 'icon'=> 'Report']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'reports/in_active_users', 'title' => 'In active users report', 'dashboard' => 1, 'icon'=> 'Report']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'reports/seen_report', 'title' => 'Seen report']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'reports/overall_seen_report', 'title' => 'Overall seen report','dashboard' => 1, 'icon'=> 'Report']);

            //Add Roles
            $super = \Spatie\Permission\Models\Role::create(['guard_name' => 'api', 'name' => 'Super Admin' , 'description' => 'System manager that can monitor everything.']);
            \Spatie\Permission\Models\Role::create(['guard_name' => 'api', 'name' => 'System Admin', 'description' => 'System admin.']);
            $student = \Spatie\Permission\Models\Role::create(['guard_name' => 'api', 'name' => 'Student', 'description' => 'System student.']);
            $tecaher = \Spatie\Permission\Models\Role::create(['guard_name' => 'api', 'name' => 'Teacher', 'description' => 'System teacher.']);
            \Spatie\Permission\Models\Role::create(['guard_name' => 'api', 'name' => 'Manager', 'description' => 'System Manager.']);
            \Spatie\Permission\Models\Role::create(['guard_name' => 'api', 'name' => 'Supervisor', 'description' => 'System Supervisor.']);
            $parent = \Spatie\Permission\Models\Role::create(['guard_name' => 'api', 'name' => 'Parent', 'description' => 'Students Parent.']);
            $Authenticated = \Spatie\Permission\Models\Role::create(['guard_name' => 'api', 'name' => 'Authenticated', 'description' => 'Allow user to only login untill has another permissions.']);

            //site internal permessions
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'site/user/search-all-users', 'title' => 'Search all users assigned to my course segments and search all site wide for users give permission to search site wide']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'site/show/real-password', 'title' => 'Show Real Password']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'site/course/teacher', 'title' => 'detect course\'s teacher']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'site/course/student', 'title' => 'detect course\'s student']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'site/show-all-courses', 'title' => 'admin permission']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'user/update-password', 'title' => 'update password']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'user/update-username', 'title' => 'update username']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'site/show/username', 'title' => 'show username']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'site/show/as-participant', 'title' => 'show as participant']);


            //Timeline Resources Permissions
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'timeline/store', 'title' => 'Store Timeline']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'timeline/get', 'title' => 'Get Timeline']);
                        
            //Materials Resources Permissions
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'material/get', 'title' => 'Get Materials']);

            //logs
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'user/logs', 'title' => 'Logs', 'dashboard' => 1 , 'icon'=> 'User']);

            //system settings
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'settings/general', 'title' => 'General Settings', 'dashboard' => 1 , 'icon'=> 'Settings']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'settings/create_assignment_extensions', 'title' => 'Manage create assignment extensions']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'settings/submit_assignment_extensions', 'title' => 'Manage submit assignment extensions']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'settings/upload_media_extensions', 'title' => 'Manage upload media extensions']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'settings/upload_file_extensions', 'title' => 'Manage upload file extensions']);



            // $super->givePermissionTo(\Spatie\Permission\Models\Permission::all());
            $teacher_permissions = [
                'site/restrict','notifications/get-all','notifications/get-unread','notifications/mark-as-read','notifications/seen','year/get','year/get-all',
                'year/get-my-years','type/get-all','type/get','type/get-my-types','level/get-all','level/get','level/get-my-levels','class/get-all','class/get-my-classes',
                'class/get','segment/get-all','segment/get','segment/get-my-segments','category/get-all','course/my-courses','course/layout','course/optional','course/course-with-teacher',
                'course/sorted-componenets','course/toggle/letter','course/count-components','course/chain','course/components','course/lessons','course/get-classes-by-course',
                'course/get-courses-by-classes','enroll/get-enrolled-courses','event/add','event/delete','event/update','event/my-events','contact/add','contact/get','contact/search',
                'user/get-my-users','component/get','announcements/delete','announcements/send','announcements/get','announcement/filter-chain','announcements/update','announcements/getbyid','announcements/get-unread',
                'announcements/mark-as-read','announcements/my','calendar/get','calendar/weekly','languages/get','languages/add','languages/update','languages/delete','languages/dictionary',
                'user/language','languages/activate','languages/deactivate','languages/set-default','lesson/add','lesson/get','lesson/delete','lesson/update','lesson/sort',
                'grade/category/add','grade/category/get','grade/category/delete','grade/category/update','grade/category/tree','grade/category/chain-categories','grade/grades',
                'grade/category/get-gradecategories','grade/item/add','grade/item/get','grade/item/delete','grade/item/update','grade/user/add','grade/user/get','grade/user/update',
                'grade/user/delete','grade/report/grader','grade/report/user','grade/report/over-all','scale/add','scale/update','scale/delete','scale/get','scale/get-with-course',
                'letter/add','letter/update','letter/delete','letter/get','letter/assign','site/user/search-all-users','site/course/teacher','chat/add-room','timeline/get','material/get',
                'course/teachers','course/participants','notifications/send','site/show/as-participant','course/progress-bar'
            ];
            $student_permissions=['notifications/get-all','notifications/get-unread','notifications/mark-as-read','notifications/seen','year/get-all','year/get-my-years',
            'type/get-all','type/get-my-types','level/get-my-levels','class/get-all','class/get-my-classes','class/get','class/get-lessons','segment/get-all','segment/get',
            'segment/get-my-segments','course/my-courses','course/layout','course/components','contact/add','contact/get','user/get-by-id','user/get-my-users',
            'component/get','announcements/get','announcements/getbyid','announcements/get-unread','announcements/mark-as-read','calendar/get','calendar/weekly',
            'languages/get','languages/update','languages/delete','languages/dictionary','user/language','languages/activate','languages/deactivate','languages/set-default',
            'grade/user/course-grade','grade/report/user','site/course/student','chat/add-room','timeline/get','material/get','course/teachers','site/show/as-participant'];

            $parent_permissions=['notifications/get-all','notifications/get-unread','notifications/mark-as-read','notifications/seen','year/get-all','year/get-my-years',
            'type/get-all','type/get-my-types','level/get-my-levels','class/get-all','class/get-my-classes','class/get','class/get-lessons','segment/get-all','segment/get',
            'segment/get-my-segments','course/my-courses','course/layout','course/components','contact/add','contact/get','user/get-by-id','user/get-my-users',
            'component/get','announcements/get','announcements/getbyid','announcements/get-unread','announcements/mark-as-read','calendar/get','calendar/weekly',
            'languages/get','languages/update','languages/delete','languages/dictionary','user/language','languages/activate','languages/deactivate','languages/set-default',
            'grade/user/course-grade','grade/report/user','site/course/student','user/parent-child','user/current-child','user/get-someone-child','user/get-my-child','user/get-current-child',
            'timeline/get','material/get','course/teachers','chat/add-room'];

            $super->givePermissionTo(\Spatie\Permission\Models\Permission::where('name', 'not like', '%user/parent-child%')->where('name','not like','%site/course/student%')->where('name','not like','user/get-my-child')->where('name','not like','%user/get-current-child%')->where('name','not like','%site/show/as-participant%')->get());
            $Authenticated->givePermissionTo(\Spatie\Permission\Models\Permission::where('name', 'not like', '%bulk%')->where('name', 'like', '%messages%')->get());
            $tecaher->givePermissionTo(\Spatie\Permission\Models\Permission::whereIn('name', $teacher_permissions)->get());
            $student->givePermissionTo(\Spatie\Permission\Models\Permission::whereIn('name', $student_permissions)->get());
            $parent->givePermissionTo(\Spatie\Permission\Models\Permission::whereIn('name', $parent_permissions)->get());

            $user = new User([
                'firstname' => 'Learnovia',
                'lastname' => 'Company',
                'username' => 'Admin',
                'email' => 'admin@learnovia.com',
                'password' => bcrypt('Learnovia123'),
                'real_password' => 'Learnovia123'
            ]);
            $user->save();
            $user->assignRole($super);

            $lan1=Language::create([
                'name' => 'English',
                'default' => 1,
            ]);

            $lan2=Language::create([
                'name' => 'Arabic',
                'default' => 0,
            ]);

            $item_type1=ItemType::create([
                'name' => 'Quiz',
            ]);
            $item_type2=ItemType::create([
                'name' => 'Assignment',
            ]);

            $formateLetter = [
                ['name' => 'A+','boundary' => 96],
                ['name' => 'A','boundary' => 93],
                ['name' => 'A-','boundary' => 89],
                ['name' => 'B+','boundary' => 86],
                ['name' => 'B','boundary' => 83],
                ['name' => 'B-','boundary' => 79],
                ['name' => 'C+','boundary' => 76],
                ['name' => 'C','boundary' => 73],
                ['name' => 'C-','boundary' => 69],
                ['name' => 'D+','boundary' => 66],
                ['name' => 'D','boundary' => 63],
                ['name' => 'D-','boundary' => 60],
                ['name' => 'F','boundary' => 0 ]
            ];
            $letter=Letter::create([
               'name' => 'Default Letter',
               'formate' => serialize($formateLetter),
            ]);

            $formateScale = [
                ['name' => 'Fair','grade' => 0 ],
                ['name' => 'Good','grade' => 1],
                ['name' => 'Very Good','grade' => 2],
                ['name' => 'Excellent','grade' => 3]
            ];
            $scale=scale::create([
               'name' => 'Default Scale',
               'formate' => serialize($formateScale),
            ]);

            eval('$importer = new App\Imports\\LanguageImport();');
            $check = Excel::import($importer, public_path('translation/EngTranslate.xlsx'));
            $check1 = Excel::import($importer, public_path('translation/ArabTranslate.xlsx'));
            
            //install components
            (new FilesController)->install_file();
            (new QuestionBankController)->install_question_bank();
            (new AttendanceSessionController)->install();
            (new AssigmentsController)->install_Assignment();
            (new PageController)->install();
            (new BigbluebuttonController)->install();
            (new H5PLessonController)->install();

            Settings::create([
                'key' => 'create_assignment_extensions',
                'title' => 'Create Assignment Supported Extensions',
                'value' => 'txt,pdf,docs,jpg,doc,docx,mp4,avi,flv,mpga,ogg,ogv,oga,jpg,jpeg,png,gif,csv,doc,docx,mp3,mpeg,ppt,pptx,rar,rtf,zip,xlsx,xls',
                'type' => 'file'
            ]);

            Settings::create([
                'key' => 'submit_assignment_extensions',
                'title' => 'Submit Assignment Supported Extensions',
                'value' => 'pdf,docs,doc,docx,xls,xlsx,ppt,pptx',
                'type' => 'file'
            ]);

            Settings::create([
                'key' => 'upload_file_extensions',
                'title' => 'Upload File Supported Extensions',
                'value' => 'pdf,docx,doc,xls,xlsx,ppt,pptx,zip,rar,txt,TXT,odt,rtf,tex,wpd,rpm,z,ods,xlsm,pps,odp,7z,bdoc,cdoc,ddoc,gtar,tgz,gz,gzip,hqx,sit,tar,epub,gdoc,ott,oth,vtt,gslides,otp,pptm,potx,potm,ppam,ppsx,ppsm,pub,sxi,sti,csv,gsheet,ots,css,html,xhtml,htm,js,scss',
                'type' => 'file'
            ]);

            Settings::create([
                'key' => 'upload_media_extensions',
                'title' => 'Upload Media Supported Extensions',
                'value' => 'mp4,avi,flv,mpga,ogg,ogv,oga,jpg,jpeg,png,gif,doc,mp3,wav,amr,mid,midi,mp2,aif,aiff,aifc,ram,rm,rpm,ra,rv,mpeg,mpe,qt,mov,movie,aac,au,flac,m3u,m4a,wma,ai,bmp,gdraw,ico,jpe,pct,pic,pict,svg,svgz,tif,tiff,3gp,dv,dif,f4v,m4v,mpg,rmvb,swf,swfl,webm,wmv,asf',
                'type' => 'file'
            ]);

            return "System Installed Your User is $user->email and Password is Learnovia123.";

        }
    }

    /*
      This function is to add new Role
      @param: name
      @output: Role Added!
    */
    public function Add_Role(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:roles,name',
            'descripion' => 'string',
        ]);
        $role = Role::create([
            'name' => $request->name,
            'description' => $request->description
        ]);
        return HelperController::api_response_format(201, $role, __('messages.role.add'));
    }
    
    /**
     *
     * @Description :get role by id
     * @param : id of role required parameters.
     * @return : string message which indicates if segment set to be current or not.
     */
    public function Get_Role(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:roles,id'
        ]);

        $role = Role::find($request->id);

        unset($role->guard_name);
        unset($role->created_at);
        unset($role->updated_at);

        return HelperController::api_response_format(201, $role);
    }

    /**
     *
     * @Description :update a role
     * @param : id, name and description  of role required parameters
     *          permissions is an optional parameter.
     * @return : the updated role
     */
    public function Update_Role(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:roles,id',
            'name' => 'required|string',
            'description' => 'string',
            'permissions' => 'nullable|array',
            'permissions.*' => 'nullable|string|exists:permissions,name',
        ]);
        $role = Role::find($request->id);

        $update = $role->update([
            'name' => $request->name,
            'description' => isset($request->description) ? $request->description: null,
        ]);

        if (isset($request->permissions)) {
            $role->syncPermissions($request->permissions);
        }

        unset($role->guard_name);
        unset($role->created_at);
        unset($role->updated_at);

        return HelperController::api_response_format(201, $role, __('messages.role.update'));
    }

    /*
     This function is to delete Role
     @param: id
     @output: 'if role exist' -> Role Deleted!
              'else' -> Error
   */
    public function Delete_Role(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:roles,id'
        ]);

        if(in_array($request->id,[1,2,3,4,5,6,7,8]))
            return HelperController::api_response_format(200, null, __('messages.error.cannot_delete'));
            
        $find = Role::find($request->id);
        if ($find) {
            $find->delete();
            return HelperController::api_response_format(200, $find, __('messages.role.delete'));
        }
        return HelperController::NOTFOUND();
    }

    /*
     This function is to assign role to specific user
     @param: userid,roleid
     @output: 'if role assigned' -> Role Assigned Successfully,
              'else' -> 'please try again,
   */
    public static function Assign_Role_to_user(Request $request)
    {
        $request->validate([
            'users' => 'required|array',
            'users.*' => 'required|integer|exists:users,id',
            'role' => 'required|integer|exists:roles,id',
        ]);
        $findrole = Role::find($request->role);
        foreach ($request->users as $user) {
            $finduser = User::find($user);
            $finduser->assignRole($findrole->name);
        }
        return HelperController::api_response_format(201, [], __('messages.role.assign'));
    }

    /*
     This function is to Assign Permission to role
     @param: permissionid,roleid
     @output: 'if permission assigned' -> Permission Assigned Successfully,
              'else' -> Please Try again
   */
    public function Assign_Permission_Role(Request $request)
    {
        $request->validate([
            'permissionid' => 'required|array',
            'permissionid.*' => 'required|integer|exists:permissions,id',
            'roleid' => 'required|integer|exists:roles,id'
        ]);

        $findrole = Role::find($request->roleid);

        foreach ($request->permissionid as $per) {
            $findPer = Permission::find($per);
            $findrole->givePermissionTo($findPer);
        }

        return HelperController::api_response_format(201, [], 'Permission/s Assigned to Role Successfully');
    }

    /*
      This function is to Revoke Role from user
      @param: userif,roleid
      @output: 'if role revoked' -> Role Revoked Successfully,
               'else' -> Please Try again
    */
    public function Revoke_Role_from_user(Request $request)
    {
        $request->validate([
            'userid' => 'required|integer|exists:users,id',
            'roleid' => 'required|integer|exists:roles,id'
        ]);

        $finduser = User::find($request->userid);
        $findrole = Role::find($request->roleid);
        $finduser->removeRole($findrole->name);
        return HelperController::api_response_format(201, [], 'Role Revoked from user Successfully');
    }

    /*
      This function is to Revoke Permission from role
      @param: permissionid,roleid
      @output: 'if permission assigned' -> Permission Revoked Successfully,
               'else' -> Please Try again
    */
    public function Revoke_Permission_from_Role(Request $request)
    {
        $request->validate([
            'permissionid' => 'required|integer|exists:permissions,id',
            'roleid' => 'required|integer|exists:roles,id'
        ]);
        $findPer = Permission::find($request->permissionid);
        $findrole = Role::find($request->roleid);

        $findrole->revokePermissionTo($findPer->name);

        return HelperController::api_response_format(201, [], 'Permission Revoked from Role Successfully');
    }

    /*
      This function is to List All Roles and Permissions
      @output: List of all Roles and permissions
    */
    public function List_Roles_Permissions()
    {
        $roles = Role::all();
        $permissions = array();
        $pers = Permission::all();
        foreach ($pers as $permission) {
            $key =  explode("/", $permission->name)[0];
            $permissions[$key][$permission->name] = $permission->title;
            // $permissions[$key][] = $permission->name;
        }
        return HelperController::api_response_format(200, ['roles' => $roles, 'permissions' => $permissions]);
    }

    /*
      This function is to Assign Permission to user
      @param: permissionid,userid
      @output: 'if permission assigned' -> Permission Assigned Successfully,
               'else' -> Please Try again
    */
    public function Assign_Permission_User(Request $request)
    {
        $request->validate([
            'permissionid' => 'required|integer|exists:permissions,id',
            'roleid' => 'required|integer|exists:roles,id'
        ]);
        $findPer = Permission::find($request->permissionid);
        $finduser = User::find($request->userid);

        $finduser->givePermissionTo($findPer->name);
        return HelperController::api_response_format(200, [], 'Permission Assigned to User Successfully');
    }

    /*
      This function is to List all roles and permissions assigned to it.
      @output: A list of all Roles with thier Permissions

    */
    public function List_Roles_With_Permission(Request $request)
    {
        $request->validate([
            'search' => 'nullable'
        ]);

        if ($request->filled('search')) {
            $roles = Role::where('name', 'LIKE', "%$request->search%")->get()
                ->paginate(HelperController::GetPaginate($request));
            return HelperController::api_response_format(202, $roles);
        }
        $roles = Role::all();
        foreach ($roles as $role) {
            $role->count = User::role($role)->count();
            $role->permissions;
        }
        $roles->toArray();
        return HelperController::api_response_format(200, $roles->paginate(HelperController::GetPaginate(\request())));
    }

    /*
      This function is to get Spicific Role with it's permission
      @param: roleid
      @output: 'if Role Exist' -> A list of all permissions to that role,
               'else' -> Role doesn't exist.
    */
    public function Get_Individual_Role(Request $request)
    {

        $request->validate([
            'roleid' => 'required|integer|exists:roles,id',
        ]);

        $findrole = Role::find($request->roleid);
        $findrole->permissions;
        return HelperController::api_response_format(200, $findrole);
    }

    /*
      This function is to Add roles with it's permissions
      @param: Rolename,array of permissions id
      @output: 'if added' -> Done Successfully,
               'else' -> Error.
    */
    public function Add_Role_With_Permissions(Request $request)
    {
        $request->validate([
            'name' => 'required|string|min:1|unique:roles,name',
            "permissions" => "required|array|min:1",
            'permissions.*' => 'required|distinct|exists:permissions,name',
            'description' => 'string'
        ]);

        $role = Role::create(['name' => $request->name]);
        if ($request->filled('description')) {
            $role->description = $request->description;
            $role->save();
        }
        foreach ($request->permissions as $per) {
            $role->givePermissionTo($per);
        }
        return HelperController::api_response_format(200, $role,__('messages.role.add'));
    }

    /*
      This function is to Export all roles with it's permissions
      @output: A json file with all roles and thier permissions.
    */
    public function Export_Role_with_Permission()
    {
        $roles = Role::all();
        $data = [];
        foreach ($roles as $key => $role) {
            $data[$key]['roleName'] = $role->name;
            if (isset($data[$key]['roleName'])) {
                $data[$key]['permission'] = array();
                foreach ($role->permissions as $k => $permission) {
                    $data[$key]['permission'][$k] = $permission->name;
                }
            }
        }

        $newJsonString = json_encode($data, JSON_PRETTY_PRINT);
        file_put_contents(public_path('json\Roles.json'), stripslashes($newJsonString));

        return response()->download(public_path('json\Roles.json'));
    }

    /*
      This function is to Import Roles and thier permissions
      @param:Json file with Every role and it's permissons,
      @output: 'if all conditions applied' -> Done Successfully,
               'else' -> Please Try again
    */
    public function Import_Role_with_Permission(Request $request)
    {
        $request->validate([
            'Imported_file' => 'required|file'
        ]);
        $extension = $request->Imported_file->getClientOriginalExtension();
        if ($extension == 'json' || $extension == 'Json' || $extension == 'JSON') {
            $content = json_decode(file_get_contents($request->Imported_file));
            //  $data = json_decode($file, true);
            foreach ($content as $con) {
                $role = Role::where('name', $con->roleName)->count();
                if ($role != 0) {
                    return response()->json(['msg' => 'Role ' . $con->roleName . ' duplicated'], 400);
                }
                foreach ($con->permission as $pere) {
                    $per = Permission::where('name', $pere)->count();
                    if ($per == 0) {
                        return response()->json(['msg' => 'Permission ' . $pere . ' is not exist'], 400);
                    }
                }
            }

            foreach ($content as $con) {
                $role = Role::create(['name' => $con->roleName]);
                $role->syncPermissions($con->permission);
            }
            return HelperController::api_response_format(200, [], 'Added succes');
        }
        return HelperController::NOTFOUND();
    }

    /**
     *
     * @Description :check if user have certain permission.
     * @param : requires permission
     * @return : the updated role
     */
    public function checkUserHavePermession(Request $request)
    {
        $request->validate(['permission' => 'required|exists:permissions,name']);
        return HelperController::api_response_format(200, $request->user()->hasPermissionTo($request->permission));
    }

    /**
     * @Description :get permissions of a user according to it's role.
     * @param : type is an optional parameter
     *          if type -> course therefore course id is required
     *          if type -> quiz therefore quiz id is required
     * @return : permissions of user.
     */
    public function Get_permission_of_user(Request $request)
    {
        $request->validate([
            'type' => 'nullable|in:course,quiz',
        ]);

        $user_id = Auth::user()->id;

        if ($request->type == 'course') {
            $request->validate([
                'course_id' => 'required|exists:courses,id',
            ]);

            $course_seg_id = CourseSegment::getidfromcourse($request->course_id);
            $r_id = Enroll::getroleid($user_id, $course_seg_id);

            $req = new Request([
                'roleid' => $r_id
            ]);

            $user_per = SpatieController::Get_Individual_Role($req);
        } else if ($request->type == 'quiz') {
            $request->validate([
                'quiz_id' => 'required|exists:quizzes,id',
            ]);

            $course_id = Quiz::where('id', $request->quiz_id)->pluck('course_id')->first();
            $course_seg_id = CourseSegment::getidfromcourse($course_id);
            $r_id = Enroll::getroleid($user_id, $course_seg_id);

            $req = new Request([
                'roleid' => $r_id
            ]);

            $user_per = SpatieController::Get_Individual_Role($req);
        } else {

            $role_id = DB::table('model_has_roles')->where('model_id', $user_id)->pluck('role_id')->first();

            $req = new Request([
                'roleid' => $role_id
            ]);

            $user_per = SpatieController::Get_Individual_Role($req);
        }

        return HelperController::api_response_format(200, $user_per);
    }

    /**
     * @Description :check permissions of a user on a course.
     * @param : course, class, permissions are required parameters.
     * @return : if this user on course -> true
     *           if no active segment in this course -> 'No Activ  segment on this course to check permession in'
     *           if this user is not enrolled in course -> you are not enrolled this course
     */
    public function checkPermessionOnCourse(Request $request)
    {
        $request->validate([
            'course' => 'required|exists:courses,id',
            'class' => 'required|exists:classes,id',
            'permissions' => 'required|array|min:1',
            'permissions.*' => 'required|string|exists:permissions,name'
        ]);
        $activeSegment = CourseSegment::GetWithClassAndCourse($request->class, $request->course);
        if ($activeSegment == null)
            return HelperController::api_response_format(400, null, 'No Activ  segment on this course to check permession in');
        $enroll = Enroll::whereCourse_segment($activeSegment->id)
            ->whereUser_id($request->user()->id)->first();
        if ($enroll) {
            $role = Role::find($enroll->role_id);
            $has = [];
            foreach ($request->permissions as $permission) {
                $has[$permission] = $role->hasPermissionTo($permission);
            }
            return HelperController::api_response_format(200, $has);
        } else
            return HelperController::api_response_format(200, 'you are not enrolled this course');
    }

    public function comparepermissions(Request $request)
    {
        $request->validate([
            'permissions' => 'required|array',
            'permissions.*' => 'required|string|min:3|max:50|exists:permissions,name',
        ]);

        $user_id = Auth::user()->id;
        $user_role = DB::table('model_has_roles')->where('model_id', $user_id)->pluck('role_id');

        $user_permissions = array();
        foreach ($user_role as $role) {
            $findrole = Role::find($role);
            $user_permissions[] = $findrole->permissions;
        }

        $userpermissions_name = collect([]);
        foreach ($user_permissions as $uper) {
            foreach ($uper as $per) {
                $userpermissions_name->push($per->name);
            }
        }
        $uniquepername = $userpermissions_name->unique();

        $results = array();
        $trueper = array();
        $count = 0;
        foreach ($request->permissions as $value) {
            foreach ($uniquepername as $per) {
                $results[] = isset($per) && $value == $per ? 'true' : 'false';
                if ($results[$count] == 'true') {
                    $trueper[] = $value;
                }
                $count++;
            }
        }
        return HelperController::api_response_format(201, $trueper);
    }
    /**
     * @Description :toggles dashboard of a permission in database.
     * @param  : permission_id.
     * @return : if succeded -> return permission and a string message 'toggle successfuly'
     *           if fails   ->string message 'Please Try again'
     */
    public function Toggle_dashboard(Request $request)
    {
        $request->validate([
            'permission_id' => 'required|exists:permissions,id',
        ]);

        $permission = Permission::findById($request->permission_id);
        $permission->dashboard = ($permission->dashboard == 1) ? 0 : 1;
        $permission->save();
        return HelperController::api_response_format(200, $permission, 'Toggle Successfully');
    }

    /**
     * @Description :list dashboard permissions.
     * @param  : permission_id.
     * @return : returns dashboard permissions
     */
    public function dashboard(Request $request)
    {
        $dashbordPermission = array();
        $user = Auth::user();
        $allRole = $user->roles;
        foreach ($allRole as $role) {
            $pers = $role->getAllPermissions();
            foreach ($pers as $permission) {
                if ($permission->dashboard) {
                    $key = explode("/", $permission->name)[0];
                    $dashbordPermission[$key]['icon']= $permission->icon;
                    $dashbordPermission[$key]['routes'][] = ['route' => $permission->name, 'title' => $permission->title];
                }
            }
        }
        return HelperController::api_response_format(200, ['permissions' => $dashbordPermission], 'Successfully');
    }

    public function export(Request $request)
    {
        $request->validate([
            'ids' => 'array',
            'ids.*' => 'exists:roles,id'
        ]); 

        if(!$request->filled('ids'))
            $request['ids']= Role::pluck('id');

        $filename = uniqid();
        $file = Excel::store(new ExportRoleWithPermissions($request->ids), 'roles'.$filename.'.xls','public');
        $file = url(Storage::url('roles'.$filename.'.xls'));
        return HelperController::api_response_format(201,$file, __('messages.success.link_to_file'));
    }
}
