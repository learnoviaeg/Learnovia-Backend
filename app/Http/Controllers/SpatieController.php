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
    public function index()
    {
        // $permission1=Permission::findById(1);
        // $p=Permission::create(['name' => 'Send Message to users']);
        // $findPer = Permission::find(2);
        // $findrole = Role::find(1);

        // $findrole->givePermissionTo($findPer);

        // $role=Role::create(['name' => 'Admin']);
        //  $role->givePermissionTo(['name' => 'Admin']);
        // create permissions

        //Permission::create(['name' => 'Add Permission To User']);

        // $permission1=Permission::findById(1);
        // $permission2=Permission::findById(2);
        // $permission3=Permission::findById(3);
        // $permission4=Permission::findById(4);
        // $permission5=Permission::findById(5);
        // $permission6=Permission::findById(6);
        // $permission7=Permission::findById(7);
        // $permission8=Permission::findById(8);
        // $permission9=Permission::findById(9);
        // $permission10=Permission::findById(10);
        // $permission11=Permission::findById(11);
        // $permission12=Permission::findById(12);
        // $permission13=Permission::findById(13);
        // $permission14=Permission::findById(14);

        // $role = Role::findById(1);

        // $role->syncPermissions([$permission1, $permission2,$permission3,$permission4,$permission5,$permission6,$permission7,$permission8,$permission9,$permission10,$permission11,$permission12,$permission13,$permission14]);
        // Permission::create(['name' => 'Add Bulk of Users']);


        // create roles and assign created permissions

        // this can be done as separate statements
        /*$role = Role::create(['name' => 'Admin']);
        $role->givePermissionTo('Add Role');
        $role->givePermissionTo('Delete Role');
        $role->givePermissionTo('Assign Role to User');
        $role->givePermissionTo('Assign Permission To Role');
        $role->givePermissionTo('Revoke Role from User');
        $role->givePermissionTo('Revoke Permission from role');*/

        // $role = Role::create(['name' => 'Teacher']) ->givePermissionTo(['Add Course', 'delete Course','Update Course','List Course']);
        //$role->givePermissionTo(Permission::all());

        // auth()->user()->assignRole('Admin');
        // return User::role('Admin')->get();
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
                'permissionid' => 'required|integer|exists:permissions,id',
                'roleid' => 'required|integer|exists:roles,id'

            ]);
            if ($validater->fails()) {
                $errors = $validater->errors();
                return response()->json($errors, 400);
            }

            $findPer = Permission::find($request->permissionid);
            $findrole = Role::find($request->roleid);

            $findrole->givePermissionTo($findPer);

            return HelperController::api_response_format(201, [], 'Permission Assigned to Role Successfully');

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
        //dd(json_decode(file_get_contents($request->Imported_file)));
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
            'permissions' => 'required|array|min:1',
            'permissions.*' => 'required|string|exists:permissions,name'
        ]);
        $activeSegment = Course::with('activeSegment')->whereId($request->course)->first();
        $enroll = Enroll::whereCourse_segment($activeSegment->activeSegment->id)->whereUser_id($request->user()->id)->first();
        $role = Role::find($enroll->role_id);
        $has = [];
        foreach ($request->permissions as $permission) {
            $has[$permission] = $role->hasPermissionTo($permission);
        }
        return HelperController::api_response_format(200, $has);
    }
}