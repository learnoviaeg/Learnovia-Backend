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
    /**
     * create User - and Enroll to optional if there and enroll mandatory if there is class
     *
     * //required
     * @param  [array] firstname, lastname, password, role, optional, course, class_id
     * //optional
     * @param  [atring..path] picture
     * @param  [string] arabicname, country, birthdate, gender, phone, address, nationality, notes, email, language,
     *              timezone, religion, second language
     * @return [object] and [string] User Created Successfully
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
            'language', 'timezone', 'religion', 'second language'];
        $enrollOptional = 'optional';
        $teacheroptional = 'course';
        foreach ($request->firstname as $key => $firstname) {
            $user = User::create([
                'firstname' => $firstname,
                'lastname' => $request->lastname[$key],
                'username' => User::generateUsername(),
                'password' => bcrypt($request->password[$key]),
                'real_password' => $request->password[$key],
                'class_id' => $request->class_id[$key]
            ]);

            if ($request->picture != null) {
                $user->picture = attachment::upload_attachment($request->picture, 'User')->id;
            }

            foreach ($optionals as $optional) {
                if ($request->filled($optional)) {
                    $user->$optional = $request->$optional;
                }

            }

            $user->save();
            $role = Role::find($request->role[$key]);
            $user->assignRole($role);
            if ($request->role[$key] == 3) {

                $classLevID = ClassLevel::GetClass($request->class_id[$key]);
                $classSegID = SegmentClass::GetClasseLevel($classLevID);

                $option = new Request([
                    'SegmentClassId' => $classSegID
                ]);
                EnrollUserToCourseController::EnrollInAllMandatoryCourses($option);
                $enrollcounter = 0;
                while (isset($request->$enrollOptional[$key][$enrollcounter])) {
                    $course_id = Course::findByName($request->$enrollOptional[$key][$enrollcounter]);
                    $segmentid = CourseSegment::getidfromcourse($course_id);
                    $option = new Request([
                        'course_segment' => array($segmentid),
                        'users' => array($user->id),
                        'role_id' => array(3)
                    ]);
                    EnrollUserToCourseController::EnrollCourses($option);
                    $enrollcounter++;
                }
            } else {
                $teachercounter = 0;

                while (isset($request->$teacheroptional[$key][$teachercounter])) {

                    $course_id = Course::findByName($request->$teacheroptional[$key][$teachercounter]);
                    $segmentid = CourseSegment::getidfromcourse($course_id);
                    $option = new Request([
                        'course_segment' => array($segmentid),
                        'users' => array($user->id),
                        'role_id' => array($role->id)
                    ]);
                    EnrollUserToCourseController::EnrollCourses($option);
                    $teachercounter++;
                }
            }
            $users->push($user);
        }
        return HelperController::api_response_format(201, $users, 'User Created Successfully');

    }

    /**
     * update User
     *
     * //required
     * @param [int] id
     * @param  [array] firstname, lastname, password, role, optional, course, class_id
     * //optional
     * @param  [atring..path] picture
     * @param  [string] arabicname, country, birthdate, gender, phone, address, nationality, notes, email, language,
     *              timezone, religion, second language
     * @return [object] and [string] User updated Successfully
    */
    public function update(Request $request)
    {
        $optionals = ['arabicname', 'country', 'birthdate', 'gender', 'phone', 'address', 'nationality', 'notes', 'email',
            'language', 'timezone', 'religion', 'second language'];
        $request->validate([
            'id' => 'required|exists:users,id',
        ]);

        $user = User::find($request->id);

        $request->validate([
            'name' => 'required|string|min:3|max:50',
            'email' => ['required', 'email', 'unique:users,email'],
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

    /**
     * Delete User
     *
     * //required
     * @param  [int] id
     * @return [object] and [string] User deleted Successfully
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

    /**
     * Get User
     *
     * @param  [atring] search
     * @return if search|id [object] user
    */
    public function list(Request $request)
    {
        $request->validate([
            'search' => 'nullable'
        ]);
        $users = User::with('roles');
        if ($request->filled('search'))
            $users->where('username', 'LIKE', "%$request->search%");
        $users = $users->paginate(HelperController::GetPaginate($request));
        if (Auth::user()->can('show/real-password')) {
            foreach ($users->items() as $value) {
                $value->setHidden(['password']);
            }
        }
        HelperController::api_response_format(200 , $users);
    }

    /**
     * Block User
     *
     * @param  [int] id
     * @return [object] user and [string] User Blocked Successfully
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

    /**
     * UnBlock User
     *
     * @param  [int] id
     * @return [object] user and [string] User Un Blocked Successfully
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

    /**
     * get User by id
     *
     * @param  [int] id
     * @return [object] user
    */
    public function GetUserById(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:users,id',
        ]);
        $user = User::find($request->id);
        $user->roles;
        return HelperController::api_response_format(201, $user, null);
    }

    /**
     * update User password
     *
     * @param  [int] id
     * @param  [string] password
     * @return [object] user and [string] User Updated Successfully
    */
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

    /**
     * get user but hide password
     *
     * @return [object] user --without real password--
    */
    public function Show_and_hide_real_password_with_permission(){
        $user = User::all();
        if (Auth::user()->can('show/real-password')) {
            $user->each(function ($row) {
                $row->setHidden(['password']);
            });
        }
        return HelperController::api_response_format(200, $user);
    }

    /**
     * paresnt with child
     *
     * @return [object] users with parents and children
    */
    public function parent_child()
    {
        $user_id = Auth::user()->id;
        $user=User::find($user_id);

        $parent=array();
        foreach($user->parents as $p)
            $parent[]=$p;

        $child=array();
        foreach($user->childs as $c)
            $child[]=$c;

        if($parent == null && $child != null)
            return HelperController::api_response_format(201, ['Childs' => $child]);
        else if($child == null && $parent != null)
            return HelperController::api_response_format(201, ['Parent'=>$parent]);
        else
            return HelperController::api_response_format(201,null,'There is no data for you.');
    }

    /**
     * get Users filtered by role
     *
     * @param  [int] role_id
     * @return [object] users with parents and filtered by role
    */
    public function get_users_with_filter_role(Request $request){
        $request->validate([
            'role_id' => 'exists:roles,id'
        ]);
        $course_segments = GradeCategoryController::getCourseSegment($request);
        $user_ids = Enroll::whereIn('course_segment', $course_segments);
        if ($request->filled('role_id')) {
            $user_ids = $user_ids->where('role_id', $request->role_id);
        }
        $user_ids = $user_ids->pluck('user_id');
        $users=User::whereIn('id',$user_ids)->with(['parents'])->get();
        return HelperController::api_response_format(201,$users);
    }

    /**
     * update User password
     *
     * @param  [array] roles
     * @return [object] users
    */
    public function allUserFilterRole(Request $request){
        $request->validate([
            'roles' => 'required|array',
            'roles.*' => 'required|exists:roles,id'
        ]);
        return HelperController::api_response_format(200, User::whereHas("roles", function ($q) use ($request) {
            $q->whereIn("id", $request->roles);
        })->paginate(HelperController::GetPaginate($request)));
    }

    public function getAllUsersInCourseSegment()
    {
        $user = Auth::user();

        $users = User::where('id','!=',$user->id);

        if(!$user->can('search/system-wide')){
            $course_segments = $user->enroll->pluck('course_segment');
            $users_id = Enroll::whereIn('course_segment', $course_segments)->pluck('user_id');
            $users = $users->whereIn('id', $users_id)->get();
            return HelperController::api_response_format(200, $users, 'all users in course segment ...');
        }
        $users = $users ->get();
        return HelperController::api_response_format(200, $users, 'all users are  ...');
    }


}
