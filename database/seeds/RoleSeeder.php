<?php

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;



class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $teacher_permissions = [
            'site/restrict', 'notifications/get-all', 'notifications/get-unread', 'notifications/mark-as-read', 'notifications/seen', 'year/get', 'year/get-all',
            'year/get-my-years', 'type/get-all', 'type/get', 'type/get-my-types', 'level/get-all', 'level/get', 'level/get-my-levels', 'class/get-all', 'class/get-my-classes',
            'class/get', 'segment/get-all', 'segment/get', 'segment/get-my-segments', 'category/get-all', 'course/my-courses', 'course/layout', 'course/optional', 'course/course-with-teacher',
            'course/sorted-componenets', 'course/toggle/letter', 'course/count-components', 'course/chain', 'course/components', 'course/lessons', 'course/get-classes-by-course',
            'course/get-courses-by-classes', 'enroll/get-enrolled-courses', 'event/add', 'event/delete', 'event/update', 'event/my-events', 'contact/add', 'contact/get', 'contact/search',
            'user/get-my-users', 'component/get', 'announcements/delete', 'announcements/send', 'announcements/get', 'announcement/filter-chain', 'announcements/update', 'announcements/getbyid', 'announcements/get-unread',
            'announcements/mark-as-read', 'announcements/my', 'calendar/get', 'calendar/weekly', 'languages/get', 'languages/add', 'languages/update', 'languages/delete', 'languages/dictionary',
            'user/language', 'languages/activate', 'languages/deactivate', 'languages/set-default', 'lesson/add', 'lesson/get', 'lesson/delete', 'lesson/update', 'lesson/sort',
            'grade/category/add', 'grade/category/get', 'grade/category/delete', 'grade/category/update', 'grade/category/tree', 'grade/category/chain-categories', 'grade/grades',
            'grade/category/get-gradecategories', 'grade/item/add', 'grade/item/get', 'grade/item/delete', 'grade/item/update', 'grade/user/add', 'grade/user/get', 'grade/user/update',
            'grade/user/delete', 'grade/report/grader', 'grade/report/user', 'grade/report/over-all', 'scale/add', 'scale/update', 'scale/delete', 'scale/get', 'scale/get-with-course',
            'letter/add', 'letter/update', 'letter/delete', 'letter/get', 'letter/assign', 'site/user/search-all-users', 'site/course/teacher', 'chat/add-room', 'timeline/get', 'material/get',
            'course/teachers', 'course/participants', 'notifications/send', 'site/show/as-participant', 'course/progress-bar'
        ];
        $student_permissions = [
            'notifications/get-all', 'notifications/get-unread', 'notifications/mark-as-read', 'notifications/seen', 'year/get-all', 'year/get-my-years',
            'type/get-all', 'type/get-my-types', 'level/get-my-levels', 'class/get-all', 'class/get-my-classes', 'class/get', 'class/get-lessons', 'segment/get-all', 'segment/get',
            'segment/get-my-segments', 'course/my-courses', 'course/layout', 'course/components', 'contact/add', 'contact/get', 'user/get-by-id', 'user/get-my-users',
            'component/get', 'announcements/get', 'announcements/getbyid', 'announcements/get-unread', 'announcements/mark-as-read', 'calendar/get', 'calendar/weekly',
            'languages/get', 'languages/update', 'languages/delete', 'languages/dictionary', 'user/language', 'languages/activate', 'languages/deactivate', 'languages/set-default',
            'grade/user/course-grade', 'grade/report/user', 'site/course/student', 'chat/add-room', 'timeline/get', 'material/get', 'course/teachers', 'site/show/as-participant'
        ];

        $parent_permissions = [
            'notifications/get-all', 'notifications/get-unread', 'notifications/mark-as-read', 'notifications/seen', 'year/get-all', 'year/get-my-years',
            'type/get-all', 'type/get-my-types', 'level/get-my-levels', 'class/get-all', 'class/get-my-classes', 'class/get', 'class/get-lessons', 'segment/get-all', 'segment/get',
            'segment/get-my-segments', 'course/my-courses', 'course/layout', 'course/components', 'contact/add', 'contact/get', 'user/get-by-id', 'user/get-my-users',
            'component/get', 'announcements/get', 'announcements/getbyid', 'announcements/get-unread', 'announcements/mark-as-read', 'calendar/get', 'calendar/weekly',
            'languages/get', 'languages/update', 'languages/delete', 'languages/dictionary', 'user/language', 'languages/activate', 'languages/deactivate', 'languages/set-default',
            'grade/user/course-grade', 'grade/report/user', 'site/course/student', 'user/parent-child', 'user/current-child', 'user/get-someone-child', 'user/get-my-child', 'user/get-current-child',
            'timeline/get', 'material/get', 'course/teachers', 'chat/add-room'
        ];

         $Super = \Spatie\Permission\Models\Role::create(['guard_name' => 'api', 'name' => 'Super Admin', 'description' => 'System manager that can monitor everything.']);
          $Super->givePermissionTo(\Spatie\Permission\Models\Permission::where('name', 'not like', '%user/parent-child%')->where('name', 'not like', '%site/course/student%')->where('name', 'not like', 'user/get-my-child')->where('name', 'not like', '%user/get-current-child%')->where('name', 'not like', '%site/show/as-participant%')->get());

        \Spatie\Permission\Models\Role::create(['guard_name' => 'api', 'name' => 'System Admin', 'description' => 'System admin.']);

        $Student=  \Spatie\Permission\Models\Role::create(['guard_name' => 'api', 'name' => 'Student', 'description' => 'System student.']);
        $Student->givePermissionTo(\Spatie\Permission\Models\Permission::whereIn('name', $student_permissions)->get());

         $Teacher=\Spatie\Permission\Models\Role::create(['guard_name' => 'api', 'name' => 'Teacher', 'description' => 'System teacher.']);
        $Teacher->givePermissionTo(\Spatie\Permission\Models\Permission::whereIn('name', $teacher_permissions)->get());

        \Spatie\Permission\Models\Role::create(['guard_name' => 'api', 'name' => 'Manager', 'description' => 'System Manager.']);
        \Spatie\Permission\Models\Role::create(['guard_name' => 'api', 'name' => 'Supervisor', 'description' => 'System Supervisor.']);

        $Parent=\Spatie\Permission\Models\Role::create(['guard_name' => 'api', 'name' => 'Parent', 'description' => 'Students Parent.']);
        $Parent->givePermissionTo(\Spatie\Permission\Models\Permission::whereIn('name', $parent_permissions)->get());

        $Authenticated =\Spatie\Permission\Models\Role::create(['guard_name' => 'api', 'name' => 'Authenticated', 'description' => 'Allow user to only login untill has another permissions.']);
        $Authenticated->givePermissionTo(\Spatie\Permission\Models\Permission::where('name', 'not like', '%bulk%')->where('name', 'like', '%messages%')->get());
    }
}
