<?php
/**
 * Created by PhpStorm.
 * User: Huda
 * Date: 6/23/2019
 * Time: 9:51 AM
 */

namespace App\Http\Controllers;

use App\Level;
use App\Classes;
use App\Enroll;
use App\GradeCategory;
use App\Parents;
use App\User;
use Carbon\Carbon;
use App\Course;
use App\Contract;
use App\CourseSegment;
use Auth;
use Spatie\Permission\Models\Role;
use Illuminate\Http\Request;
use App\Http\Controllers\EnrollUserToCourseController;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use App\ClassLevel;
use App\Attachment;
use App\SegmentClass;

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
            'class_id.*' => 'required|exists:classes,id',
            'picture' => 'nullable|array','arabicname' => 'nullable|array', 'gender' => 'nullable|array', 'phone' => 'nullable|array',
            'address' => 'nullable|array','nationality' => 'nullable|array','country' => 'nullable|array', 'birthdate' => 'nullable|array',
            'notes' => 'nullable|array','email' => 'nullable|array', 'language' => 'nullable|array','timezone' => 'nullable|array',
            'religion' => 'nullable|array','second language' => 'nullable|array', 'username' => 'nullable|array', 'type' => 'nullable|array',
            'level' => 'nullable|array', 'real_password' => 'nullable|array'
        ]);

        // return User::max('id');
        $users_is = collect([]);
        $optionals = ['arabicname', 'country', 'birthdate', 'gender', 'phone', 'address', 'nationality', 'notes', 'email', 'suspend',
            'language', 'timezone', 'religion', 'second language','picture', 'real_password', 'level', 'type', 'class_id', 'username'
        ];
        $enrollOptional = 'optional';
        $teacheroptional = 'course';
        $i=0;

        $count=0;
        $max_allowed_users = Contract::whereNotNull('id')->pluck('numbers_of_users')->first();
        $users=Enroll::where('role_id',3)->get();
        foreach($request->role as $role)
            if($role == 3)
                $count++;

        if((count($users) + $count) > $max_allowed_users)
            return HelperController::api_response_format(404 ,$max_allowed_users, 'exceed MAX, U Can\'t add users any more');

        foreach ($request->firstname as $key => $firstname) {
            $user = User::create([
                'firstname' => $firstname,
                'lastname' => $request->lastname[$key],
                'username' => User::generateUsername(),
                'password' => bcrypt($request->password[$key]),
                'real_password' => $request->password[$key],
                'class_id' => $request->class_id[$key]
            ]);

            foreach ($optionals as $optional)
                if ($request->filled($optional)){
                    if ($optional == 'picture')
                        $user->$optional = attachment::upload_attachment($request->$optional[$i], 'User')->id;
                    if($optional =='birthdate')
                        $user->$optional = Carbon::parse($request->$optional[$i])->format('Y-m-d');
                    if($optional =='real_password'){
                        $user->$optional = $request->$optional[$i];
                        $user->password =   bcrypt($request->$optional[$i]);
                    }
                    $user->$optional =$request->$optional[$i];
                }

            $i++;

            $user->save();
            $role = Role::find($request->role[$key]);
            $user->assignRole($role);
            if ($request->role[$key] == 3) {
                $option = new Request([
                    'users' => [$user->id],
                    'level' => $request->level[$key] ,
                    'type' => $request->type[$key],
                    'class' => $request->class_id[$key]
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
            $users_is->push($user);
        }
        return HelperController::api_response_format(201, $users_is, 'User Created Successfully');

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
        $users = User::with('roles')->get();

        if ($request->filled('search'))
            $users->where('username', 'LIKE', "%$request->search%");
        $users = $users->paginate(HelperController::GetPaginate($request));
        if (Auth::user()->can('show/real-password')) {
            foreach ($users->items() as $value) {
                $value->setHidden(['password']);
            }
        }
        return HelperController::api_response_format(200 , $users);
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
    Public Function Overview_Report()
    {
        $user_id=Auth::id();
        $course_segments=Enroll::where('user_id',$user_id)->with(['courseSegment.courses','courseSegment.GradeCategory.GradeItems.UserGrade'
        =>function ($query) use ($user_id) {
            $query->where('user_id',$user_id);
        }])->get();
        return $course_segments;
    }

    Public Function SetCurrentChild(Request $request)
    {
        $request->validate([
            'child_id' => 'required|exists:parents,child_id'
        ]);
        Parents::where('child_id',$request->child_id)->where('parent_id',Auth::id())->update(['current'=> 1]);
        return HelperController::api_response_format(200, 'Child is choosen successfully');

    }
    Public Function getMyChildren(){
        $childrenIDS = Parents::where('parent_id',Auth::id())->pluck('child_id');
        $children =  User::whereIn('id',$childrenIDS)->get();
        return HelperController::api_response_format(200,$children ,'Children are.......');

    }
    Public Function getSomeoneChildren(Request $request){
        $request->validate([
            'parent_id' => 'required|exists:parents,parent_id'
        ]);
        $childrenIDS = Parents::where('parent_id',$request->parent_id)->pluck('child_id');
        $children =  User::whereIn('id',$childrenIDS)->get();
        return HelperController::api_response_format(200,$children ,'Children are.......');

    }
    Public Function getSomeoneParent(Request $request)
    {
        $request->validate([
            'child_id' => 'required|exists:parents,child_id'
        ]);
        $parentID = Parents::where('child_id',$request->child_id)->first('parent_id');
        $parent =  User::find($parentID);
        return $parent;
    }
    Public function get_my_users(Request $request){
        $request->validate([
            'course_id' => 'array',
            'course_id.*' => 'exists:courses,id',
            'class_id' => 'array',
            'class_id.*' => 'exists:classes,id',
            'level_id' => 'array',
            'level_id.*' => 'exists:levels,id',
            'role_id' => 'exists:roles,id',
            'role_id.*' => 'exists:roles,id'
        ]);
        $total = array();
        $SegmentClasses = array();
        if($request->filled('level_id')){
            $levels = Level::whereIn('id',$request->level_id)->with(['yearlevel.classLevels.segmentClass'])->get();
            foreach($levels as $level){
                foreach($level->yearlevel->classLevels as $classLevel){
                    foreach($classLevel->segmentClass as $segmentClass){
                        if(!in_array($segmentClass->id, $SegmentClasses))
                            array_push($SegmentClasses ,$segmentClass->id);
                    }
                }
            }
            if(!isset($SegmentClasses))
                return HelperController::api_response_format(400 ,'This class is not assigned to a course segment');
            
            $course_segment = CourseSegment::whereIn('segment_class_id',$SegmentClasses)->pluck('id')->unique();
            $users = Enroll::whereIn('course_segment',$course_segment)->pluck('user_id')->unique();
    
            if($request->filled('role_id'))
                 $users = Enroll::whereIn('course_segment',$course_segment)->where('role_id',$request->role_id)->pluck('user_id')->unique();
        }
        elseif($request->filled('course_id')){
            $course_segment = CourseSegment::whereIn('course_id',$request->course_id)->pluck('id')->unique();
            $users = Enroll::whereIn('course_segment',$course_segment)->pluck('user_id')->unique();
            if($request->filled('role_id'))
                 $users = Enroll::whereIn('course_segment',$course_segment)->whereIn('role_id',$request->role_id)->pluck('user_id')->unique();

        }elseif($request->filled('class_id')){
            $classes = Classes::whereIn('id',$request->class_id)->with(['classlevel.segmentClass'])->get();
            foreach($classes as $class){
                foreach($class->classlevel->segmentClass as $segClass){
                    if(!in_array($segClass->id, $SegmentClasses))
                        array_push($SegmentClasses ,$segClass->id);
                }
            }
            if(!isset($SegmentClasses))
                return HelperController::api_response_format(400 ,'This class is not assigned to a course segment');
            $course_segment = CourseSegment::whereIn('segment_class_id',$SegmentClasses)->pluck('id')->unique();
            $users = Enroll::whereIn('course_segment',$course_segment)->pluck('user_id')->unique();
            if($request->filled('role_id'))
                $users = Enroll::whereIn('course_segment',$course_segment)->whereIn('role_id',$request->role_id)->pluck('user_id')->unique();

        }else{
            $course_segments = Enroll::where('user_id',Auth::id())->pluck('course_segment')->unique();
            $users = Enroll::whereIn('course_segment',$course_segments)->pluck('user_id')->unique();
            if($request->filled('role_id'))
                 $users = Enroll::whereIn('course_segment',$course_segments)->whereIn('role_id',$request->role_id)->pluck('user_id')->unique();

        }
        $students = user::whereIn('id',$users)->get();
        foreach($students as $student){
            if($student->can('site/course/student'))
                array_push($total ,$student) ;
        }
        return HelperController::api_response_format(200,$total ,'Users are.......');
    }
    
}
