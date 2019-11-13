<?php
/**
 * Created by PhpStorm.
 * User: Huda
 * Date: 6/23/2019
 * Time: 9:51 AM
 */

namespace App\Http\Controllers;
use App\Enroll;
use App\GradeCategory;
use App\User;
use App\Course;
use App\CourseSegment;
use Auth;
use Spatie\Permission\Models\Role;
use Illuminate\Http\Request;
use App\Http\Controllers\EnrollUserToCourseController;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use App\ClassLevel;
use App\Attachment;
use App\SegmentClass;
use DB;

class UserController extends Controller
{
    /*
        @Description:: This Function is for creating new user.
        @Param:: name, email [must be correct and unique], password[must be more than or equal 8].
        @Output:: 'if every thing correct' -> User Created Successfully.
                  'else' -> Error.
    */
    public function create(Request $request)
    {
        $request->validate([
            'firstname' => 'required|array',
            'firstname.*' => 'required|string|min:3|max:50',
            'lastname' => 'required|array',
            'lastname.*' => 'required|string|min:3|max:50',
            'password' => 'required|array',
            'password.*' => 'required|string|min:8|max:191',
            'role' => 'required|array',
            'optional.*' => 'exists:courses,name',
            'optional' => 'array',
            'course.*' => 'exists:courses,name',
            'course' => 'array',
            'role.*' => 'required|exists:roles,id',
            'class_id' => 'required|array',
            'picture' => 'nullable'
        ]);
        $users = collect([]);
        $optionals = ['arabicname', 'country', 'birthdate', 'gender', 'phone', 'address', 'nationality', 'notes', 'email',
                        'language','timezone','religion','second language'];
        $enrollOptional = 'optional';
        $teacheroptional='course';
        foreach ($request->firstname as $key => $firstname) {
            $user = User::create([
                'firstname' => $firstname,
                'lastname' => $request->lastname[$key],
                'username' => User::generateUsername(),
                'password' => bcrypt($request->password[$key]),
                'real_password'=> $request->password[$key],
                'class_id' => $request->class_id[$key]
            ]);

            if($request->picture != null )
            {
                $user->picture=attachment::upload_attachment($request->picture, 'User')->id;
            }

            foreach ($optionals as $optional) {
                if ($request->filled($optional))
                {
                    $user->$optional = $request->$optional;
                }

            }

            $user->save();
            $role = Role::find($request->role[$key]);
            $user->assignRole($role);
            if ($request->role[$key] == 3) {

                $classLevID=ClassLevel::GetClass($request->class_id[$key]);
                $classSegID=SegmentClass::GetClasseLevel($classLevID);

                $option = new Request([
                    'SegmentClassId' => $classSegID
                ]);
                EnrollUserToCourseController::EnrollInAllMandatoryCourses($option);
                $enrollcounter=0;
                while(isset($request->$enrollOptional[$key][$enrollcounter])) {
                    $course_id=Course::findByName($request->$enrollOptional[$key][$enrollcounter]);
                    $segmentid= CourseSegment::getidfromcourse($course_id);
                    $option = new Request([
                        'course_segment' => array($segmentid),
                        'users'=> array($user->id),
                        'role_id'=>array(3)
                    ]);
                    EnrollUserToCourseController::EnrollCourses($option);
                    $enrollcounter++;
                }
            }
            else{
                $teachercounter=0;

                while(isset($request->$teacheroptional[$key][$teachercounter])){

                    $course_id=Course::findByName($request->$teacheroptional[$key][$teachercounter]);
                    $segmentid= CourseSegment::getidfromcourse($course_id);
                    $option = new Request([
                        'course_segment' => array($segmentid),
                        'users'=> array($user->id),
                        'role_id'=>array($role->id)
                    ]);
                    EnrollUserToCourseController::EnrollCourses($option);
                    $teachercounter++;
                }
            }
            $users->push($user);
        }
        return HelperController::api_response_format(201, $users, 'User Created Successfully');

    }

    /*
        @Description:: This Function is for Update user.
        @Param::id, name, email [must be correct and unique], password[must be more than or equal 8].
        @Output:: 'if every thing correct' -> User Updated Successfully.
                  'else' -> Error.
    */

    public function update(Request $request)
    {
        $optionals = ['arabicname', 'country', 'birthdate', 'gender', 'phone', 'address', 'nationality', 'notes', 'email',
                        'language','timezone','religion','second language'];
        $request->validate([
            'id' => 'required|exists:users,id',
        ]);

        $user = User::find($request->id);

        $request->validate([
            'name' => 'required|string|min:3|max:50',
            'email' => ['required','email' , 'unique:users,email'],
            'password' => 'required|string|min:8|max:191'
        ]);
        $check = $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'real_password' => $request->password,
        ]);

        foreach ($optionals as $optional) {
            if ($request->filled($optional))
                $user->$optional = $request->$optional;
        }
        $user->save();
        return HelperController::api_response_format(200, $user, 'User Updated Successfully');

    }

    /*
       @Description:: This Function is for Delete user.
       @Param:: id.
       @Output:: 'if User Exist' -> User Deleted Successfully.
                 'else' -> Error.
   */

    public function delete(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:users,id',
        ]);

        $user = User::find($request->id);
        $user->delete();

        return HelperController::api_response_format(201, null, 'User Deleted Successfully');

    }

    /*
       @Description:: This Function is for List All users.
       @Output:: All users in system.
   */

    public function list(Request $request)
    {
        $request->validate([
            'search' => 'nullable'
        ]);

        $user_id = Auth::user()->id;
        $role_id = DB::table('model_has_roles')->where('model_id',$user_id)->pluck('role_id')->first();
        if($role_id == 1 || $role_id == 2)
        {
            if($request->filled('search'))
            {
                $user = User::with('roles')->where('username', 'LIKE' , "%$request->search%")->get()
                ->paginate(HelperController::GetPaginate($request));
                return HelperController::api_response_format(202, $user);
            }
            $user =User::with('roles')->paginate(HelperController::GetPaginate($request));
            foreach ($user->items() as $value) {
                $value->setHidden(['password']);
            }
            return HelperController::api_response_format(200, $user);
        }
        else {
            if($request->filled('search'))
            {
                $user = User::with('roles')->where('name', 'LIKE' , "%$request->search%")->get()
                ->paginate(HelperController::GetPaginate($request));
                return HelperController::api_response_format(202, $user);
            }
            $user = User::with('roles')->paginate(HelperController::GetPaginate($request));
            return HelperController::api_response_format(200, $user);
        }
    }

    /*
       @Description:: This Function is for Block a user.
       @Param::id.
       @Output:: 'if user found' -> User Blocked Successfully.
                 'else' -> Error.
   */

    public function suspend_user(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:users,id',
        ]);

        $user = User::find($request->id);
        $check = $user->update([
            'suspend' => 1
        ]);
        return HelperController::api_response_format(201, $user, 'User Blocked Successfully');

    }

    /*
       @Description:: This Function is for un Block a user.
       @Param::id.
       @Output:: 'if user found' -> User un Blocked Successfully.
                'else' -> Error.
   */

    public function unsuspend_user(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:users,id',
        ]);
        $user = User::find($request->id);
        $check = $user->update([
            'suspend' => 0
        ]);
        return HelperController::api_response_format(201, $user, 'User Un Blocked Successfully');
    }

    public function GetUserById(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:users,id',
        ]);
        $user = User::find($request->id);
        $user->roles;
        return HelperController::api_response_format(201, $user, null);
    }

    public function UpdateUserPassword(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:users,id',
            'password' => 'required|string|min:8|max:191'
        ]);
        $user = User::find($request->id);
        $user->update([
            'real_password' => $request->password,
            'password' => bcrypt($request->password)
        ]);

        return HelperController::api_response_format(201, $user, 'User Updated Successfully');
    }

    public function Show_and_hide_real_password_with_permission(){
        $user_id = Auth::user()->id;
        $role_id = DB::table('model_has_roles')->where('model_id',$user_id)->pluck('role_id')->first();
        if($role_id == 1 || $role_id == 2)
        {
            $user =User::all()->each(function($row)
            {
                $row->setHidden(['password']);
            });
            return HelperController::api_response_format(200, $user);
        }
        else {
            $user = User::all();
            return HelperController::api_response_format(200, $user);
        }
    }

    public function parent_child(Request $request)
    {
        $user_id = Auth::user()->id;
        $user=User::find($user_id);
        $parent=array();
        foreach($user->parents as $p)
        {
            $parent[]=$p;
        }
        $child=array();
        foreach($user->childs as $c)
        {
            $child[]=$c;
        }
        if($parent == null && $child != null)
        {
            return HelperController::api_response_format(201, ['Childs' => $child]);
        }
        else if($child == null && $parent != null)
        {
            return HelperController::api_response_format(201, ['Parent'=>$parent]);
        }
        else
        {
            return HelperController::api_response_format(201,null,'There is no data for you.');
        }

    }
    public function get_users_with_filter_role(Request $request){
        $request->validate([
            'role_id' => 'exists:roles,id'
        ]);
        $course_segments =GradeCategoryController::getCourseSegment($request);
        $user_ids = Enroll::whereIn('course_segment',$course_segments);
        if($request->filled('role_id')){
            $user_ids = $user_ids->where('role_id',$request->role_id);
        }
        $user_ids = $user_ids->pluck('user_id');
        $users=User::whereIn('id',$user_ids)->with(['parents'])->get();
        return HelperController::api_response_format(201,$users);

    }

    public function allUserFilterRole(Request $request){
        $request->validate([
            'roles' => 'required|array',
            'roles.*' => 'required|exists:roles,id'
        ]);
        return HelperController::api_response_format(200 , User::whereHas("roles", function($q) use ($request){ $q->whereIn("id", $request->roles); })->paginate(HelperController::GetPaginate($request)));
    }
}
