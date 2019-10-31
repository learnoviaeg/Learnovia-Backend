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
use DB;
use Modules\QuestionBank\Entities\Quiz;


class SpatieController extends Controller
{
    public function install()
    {
        $user = User::whereEmail('admin@learnovia.com')->first();
        if ($user) {
            return "This Site is Installed before go and ask admin";
        } else {
            //Message Permissiosns
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'messages/send', 'title' => 'send messages']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'messages/delete-for-all', 'title' => 'delete messages for all']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'messages/delete-for-me', 'title' => 'delete messages for me']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'messages/seen', 'title' => 'seen messages']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'messages/get-from-to', 'title' => 'get messages from to']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'messages/add-send-permission-for-role', 'title' => 'messages for add and send permissions to role']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'messages/mythreads', 'title' => 'my threads']);

            //Notifications Permissiosns
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'notifications/get-all', 'title' => 'get all notifications']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'notifications/get-unread', 'title' => 'get unread notifications']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'notifications/mark-as-read', 'title' => 'mark notification as read']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'notifications/get-for-user', 'title' => 'get user notifications']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'notifications/delete-duration', 'title' => 'delete notifications duration']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'notifications/seen', 'title' => 'seen notifications']);


            //Spatie Permissions
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'roles/add', 'title' => 'add role']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'roles/delete', 'title' => 'delete role']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'roles/get', 'title' => 'get role']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'roles/update', 'title' => 'update role']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'roles/assign-to-user', 'title' => 'assign role to user']);
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

            //Type Permissions
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'type/delete', 'title' => 'delete type']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'type/add', 'title' => 'add type']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'type/get-all', 'title' => 'get all types']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'type/get', 'title' => 'get type']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'type/update', 'title' => 'update type']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'type/assign', 'title' => 'assign type']);

            //Level Permissions
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'level/add', 'title' => 'add level']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'level/update', 'title' => 'update level']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'level/get-all', 'title' => 'get all levels']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'level/get', 'title' => 'get level']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'level/delete', 'title' => 'delete level']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'level/assign', 'title' => 'assign level']);

            //Class Permissions
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'class/add', 'title' => 'add class']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'class/get-all', 'title' => 'get all classes']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'class/get', 'title' => 'get class']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'class/update', 'title' => 'update class']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'class/delete', 'title' => 'delete class']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'class/assign', 'title' => 'assign class']);

            //Segment Permissions
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'segment/add', 'title' => 'add segment']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'segment/delete', 'title' => 'delete segment']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'segment/assign', 'title' => 'assign segment']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'segment/update', 'title' => 'update segment']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'segment/get-all', 'title' => 'get all segment']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'segment/get', 'title' => 'get segment']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'segment/set-current', 'title' => 'set current segment']);

            //Cetegory Permissions
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'category/add', 'title' => 'add category']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'category/update', 'title' => 'update category']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'category/delete', 'title' => 'delete category']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'category/get-all', 'title' => 'get all categories']);

            //Course Permissions
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'course/add', 'title' => 'add course']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'course/update', 'title' => 'update course']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'course/delete', 'title' => 'delete course']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'course/get-all', 'title' => 'get all courses']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'course/my-courses', 'title' => 'get my courses']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'course/layout', 'title' => 'course layout']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'course/optional', 'title' => 'optional courses']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'course/assgin-course-to', 'title' => 'assign course']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'course/course-with-teacher', 'title' => 'course with teacher']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'course/sorted-componenets', 'title' => 'course sorted components']);

            //Enroll Permissions
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'enroll/enroll-single-user', 'title' => 'enroll single user']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'enroll/un-enroll-single-user', 'title' => 'un enroll single user']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'enroll/get-enrolled-courses', 'title' => 'get enrolled courses']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'enroll/mandatory-course', 'title' => 'enroll mandatory courses']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'enroll/bulk-of-exist-users', 'title' => 'enroll bulk of exist users']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'enroll/add-and-enroll-bulk-of-new-users', 'title' => 'add and enroll bulk of new users']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'enroll/enrolled-users', 'title' => 'enrolled users']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'enroll/get-unenroll-users', 'title' => 'get enrolled users']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'enroll/get-unenrolled-users-Bulk', 'title' => 'get bulk of unenrolled users']);

            //Contact Permissions
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'contact/add', 'title' => 'add contact']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'contact/get', 'title' => 'get contact']);

            //USER CRUD Permissions
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'user/add', 'title' => 'add user']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'user/update', 'title' => 'update user']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'user/delete', 'title' => 'delete user']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'user/get-all', 'title' => 'get all user']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'user/suspend', 'title' => 'suspend user']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'user/un-suspend', 'title' => 'un suspend user']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'user/show-hide-real-pass', 'title' => 'shor and hide real password']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'user/parent-child', 'title' => 'user parent/child']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'user/get-by-id', 'title' => 'get user by id']);

            //Components Permissions
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'component/get', 'title' => 'get component']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'component/install', 'title' => 'install component']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'component/uninstall', 'title' => 'uninstall component']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'component/toggle', 'title' => 'toggle component']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'component/sort', 'title' => 'sort component']);

            //Announcements Permissions
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'announcements/delete', 'title' => 'delete announcements']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'announcements/send', 'title' => 'send announcements']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'announcements/get', 'title' => 'get announcements']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'announcements/update', 'title' => 'update announcements']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'announcements/getbyid', 'title' => 'get announcements by id']);

            //Calendar Permission
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'calendar/get', 'title' => 'get calendar']);

            //Import
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'import', 'title' => 'import excel sheet']);

            //Lesson Permissions
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'lesson/add', 'title' => 'add lesson']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'lesson/get', 'title' => 'get lesson']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'lesson/delete', 'title' => 'delete lesson']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'lesson/update', 'title' => 'update lesson']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'lesson/sort', 'title' => 'sort lesson']);

            //Grade Ctegory
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'grade/category/add', 'title' => 'add grade category']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'grade/category/get', 'title' => 'get grade category']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'grade/category/delete', 'title' => 'delete grade category']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'grade/category/update', 'title' => 'update grade category']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'grade/category/move', 'title' => 'move grade category']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'grade/category/tree', 'title' => 'grade category tree']);

            //Grade Item
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'grade/item/add', 'title' => 'add grade item']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'grade/item/get', 'title' => 'get grade item']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'grade/item/delete', 'title' => 'delete grade item']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'grade/item/update', 'title' => 'update grade item']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'grade/item/move-category', 'title' => 'move grade item category']);

            //User Grade
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'grade/user/add', 'title' => 'add user grade']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'grade/user/get', 'title' => 'get user grade']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'grade/user/delete', 'title' => 'delete user grade']);
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'grade/user/update', 'title' => 'update user grade']);

            //dashboard
            \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'dashboard/toggle', 'title' => 'toggle dashboard']);



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
    }

    /*
      This function is to add new Role
      @param: name
      @output: Role Added!
    */

    public function Add_Role(Request $request)
    {
        $request->validate([
            'name' => 'required'
        ]);
        $role = Role::create(['name' => $request->name]);
        return HelperController::api_response_format(201, $role, 'Role Added!');
    }

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

    public function Update_Role(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:roles,id'
        ]);
        $role = Role::find($request->id);

        $request->validate([
            'name' => 'required|string'
        ]);

        $update = $role->update([
            'name' => $request->name,
        ]);

        unset($role->guard_name);
        unset($role->created_at);
        unset($role->updated_at);

        return HelperController::api_response_format(201, $role, 'Role Updated Successfully');
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
        $find = Role::find($request->id);
        if ($find) {
            $find->delete();
            return HelperController::api_response_format(200, $find, 'Role Deleted!');
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
        try {
            $validater = Validator::make($request->all(), [
                'userid' => 'required|integer|exists:users,id',
                'roleid' => 'required|integer|exists:roles,id'

            ]);
            if ($validater->fails()) {
                $errors = $validater->errors();
                return HelperController::api_response_format(400, $errors);
            }

            $finduser = User::find($request->userid);
            $findrole = Role::find($request->roleid);

            $finduser->assignRole($findrole->name);

            return HelperController::api_response_format(201, [], 'Role Assigned Successfully');

        } catch (Exception $ex) {
            return HelperController::NOTFOUND();
        }
    }

    /*
     This function is to Assign Permission to role
     @param: permissionid,roleid
     @output: 'if permission assigned' -> Permission Assigned Successfully,
              'else' -> Please Try again
   */

    public function Assign_Permission_Role(Request $request)
    {

        try {
            $validater = Validator::make($request->all(), [
                'permissionid' => 'required|array',
                'permissionid.*' => 'required|integer|exists:permissions,id',
                'roleid' => 'required|integer|exists:roles,id'

            ]);
            if ($validater->fails()) {
                $errors = $validater->errors();
                return response()->json($errors, 400);
            }

            $findrole = Role::find($request->roleid);

            foreach ($request->permissionid as $per) {
                $findPer = Permission::find($per);
                $findrole->givePermissionTo($findPer);

            }

            return HelperController::api_response_format(201, [], 'Permission/s Assigned to Role Successfully');

        } catch (Exception $ex) {
            return HelperController::NOTFOUND();
        }
    }

    /*
      This function is to Revoke Role from user
      @param: userif,roleid
      @output: 'if role revoked' -> Role Revoked Successfully,
               'else' -> Please Try again
    */

    public function Revoke_Role_from_user(Request $request)
    {
        try {
            $validater = Validator::make($request->all(), [
                'userid' => 'required|integer|exists:users,id',
                'roleid' => 'required|integer|exists:roles,id'

            ]);
            if ($validater->fails()) {
                $errors = $validater->errors();
                return response()->json($errors, 400);
            }

            $finduser = User::find($request->userid);
            $findrole = Role::find($request->roleid);
            $finduser->removeRole($findrole->name);
            return HelperController::api_response_format(201, [], 'Role Revoked from user Successfully');
        } catch (Exception $ex) {
            return HelperController::NOTFOUND();
        }
    }

    /*
      This function is to Revoke Permission from role
      @param: permissionid,roleid
      @output: 'if permission assigned' -> Permission Revoked Successfully,
               'else' -> Please Try again
    */

    public function Revoke_Permission_from_Role(Request $request)
    {
        try {
            $validater = Validator::make($request->all(), [
                'permissionid' => 'required|integer|exists:permissions,id',
                'roleid' => 'required|integer|exists:roles,id'

            ]);
            if ($validater->fails()) {
                $errors = $validater->errors();
                return response()->json($errors, 400);
            }

            $findPer = Permission::find($request->permissionid);
            $findrole = Role::find($request->roleid);

            $findrole->revokePermissionTo($findPer->name);

            return HelperController::api_response_format(201, [], 'Permission Revoked from Role Successfully');

        } catch (Exception $ex) {
            return HelperController::NOTFOUND();
        }
    }

    /*
      This function is to List All Roles and Permissions
      @output: List of all Roles and permissions
    */

    public function List_Roles_Permissions()
    {
        $roles = Role::all();
        $permissions = Permission::all();
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

        try {
            $validater = Validator::make($request->all(), [
                'permissionid' => 'required|integer|exists:permissions,id',
                'userid' => 'required|integer|exists:users,id'

            ]);
            if ($validater->fails()) {
                $errors = $validater->errors();
                return response()->json($errors, 400);
            }

            $findPer = Permission::find($request->permissionid);
            $finduser = User::find($request->userid);

            $finduser->givePermissionTo($findPer->name);
            return HelperController::api_response_format(200, [], 'Permission Assigned to User Successfully');
        } catch (Exception $ex) {
            return HelperController::NOTFOUND();
        }

    }

    /*
      This function is to List all roles and permissions assigned to it.
      @output: A list of all Roles with thier Permissions

    */

    public function List_Roles_With_Permission()
    {

        try {
            $roles = Role::all();
            foreach ($roles as $role) {
                $role->permissions;
            }
            return HelperController::api_response_format(200, $roles);
        } catch (Exception $ex) {
            return HelperController::NOTFOUND();
        }

    }

    /*
      This function is to get Spicific Role with it's permission
      @param: roleid
      @output: 'if Role Exist' -> A list of all permissions to that role,
               'else' -> Role doesn't exist.
    */

    public function Get_Individual_Role(Request $request)
    {

        try {
            $validater = Validator::make($request->all(), [
                'roleid' => 'required|integer|exists:roles,id',
            ]);
            if ($validater->fails()) {
                $errors = $validater->errors();
                return response()->json($errors, 400);
            }

            $findrole = Role::find($request->roleid);
            $findrole->permissions;
            return HelperController::api_response_format(200, $findrole);
        } catch (Exception $ex) {
            return HelperController::NOTFOUND();
        }

    }

    /*
      This function is to Add roles with it's permissions
      @param: Rolename,array of permissions id
      @output: 'if added' -> Done Successfully,
               'else' -> Error.
    */

    public function Add_Role_With_Permissions(Request $request)
    {

        try {
            $validater = Validator::make($request->all(), [
                'name' => 'required|string|min:1|unique:roles,name',
                "permissions" => "required|array|min:1",
                'permissions.*' => 'required|distinct|exists:permissions,id'
            ]);
            if ($validater->fails()) {
                $errors = $validater->errors();
                return response()->json($errors, 400);
            }

            $createrole = Role::create(['name' => $request->name]);
            if ($createrole) {
                foreach ($request->permissions as $per) {
                    $createrole->givePermissionTo($per);
                }
                return HelperController::api_response_format(200, $createrole->permissions);
            }
            return HelperController::NOTFOUND();

        } catch (Exception $ex) {
            return HelperController::NOTFOUND();
        }

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
        try {
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

        } catch (Exception $ex) {
            return HelperController::NOTFOUND();
        }
    }

    public function checkUserHavePermession(Request $request)
    {
        $request->validate(['permission' => 'required|exists:permissions,name']);
        return HelperController::api_response_format(200, $request->user()->hasPermissionTo($request->permission));
    }

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

    public function Toggle_dashboard(Request $request)
    {

        try {
            $request->validate([
                'permission_id' => 'required|exists:permissions,id',
            ]);
            $permission = Permission::findById($request->permission_id);
            $permission->dashboard = ($permission->dashboard == 1) ? 0 : 1;
            $permission->save();
            return HelperController::api_response_format(200, $permission, 'Toggle Successfully');
        } catch (Exception $ex) {
            return HelperController::api_response_format(400, null, 'Please Try again');
        }
    }

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
                    $dashbordPermission[$key][] = ['route' =>$permission->name, 'title'=>$permission->title];
                }
            }
        }
        return HelperController::api_response_format(200, ['permissions' => $dashbordPermission], 'Successfully');
    }
}
