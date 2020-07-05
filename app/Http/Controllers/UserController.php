<?php

/**
 * Created by PhpStorm.
 * User: Huda
 * Date: 6/23/2019
 * Time: 9:51 AM
 */

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Storage;
use App\Language;
use App\Level;
use App\Classes;
use App\Enroll;
use stdClass;
use App\GradeCategory;
use App\Segment;
use App\Parents;
use App\User;
use App\AcademicYear;
use App\AcademicType;
use App\YearLevel;
use App\AcademicYearType;
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
use App\attachment;
use App\SegmentClass;
use App\Exports\UsersExport;
use Maatwebsite\Excel\Facades\Excel;
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
            'password.*' => 'required|string|min:6|max:191',
            // 'role' => 'required|array',
            // 'role.*' => 'required|exists:roles,id',
            'role' => 'required|exists:roles,id', /// in all system
            'role_id' => 'required_with:level|exists:roles,id', /// chain role
            'optional.*' => 'exists:courses,id',
            'optional' => 'array',
            'course.*' => 'exists:courses,id',
            'course' => 'array',
            'class_id' => 'array',
            'class_id.*' => 'exists:classes,id',
            'picture' => 'nullable|array','arabicname' => 'nullable|array', 'gender' => 'nullable|array', 'phone' => 'nullable|array',
            'address' => 'nullable|array','nationality' => 'nullable|array','country' => 'nullable|array', 'birthdate' => 'nullable|array',
            'notes' => 'nullable|array','email' => 'nullable|array|unique:users',
            'language' => 'nullable|array',
            'language.*' => 'integer|exists:languages,id',
            'timezone' => 'nullable|array',
            'religion' => 'nullable|array',
            'second language' => 'nullable|array',
            'second language.*' => 'integer|exists:languages,id',
             'username' => 'required|array', 'type' => 'nullable|array',
            'level' => 'nullable|array', 'real_password' => 'nullable|array',
            'suspend.*' => 'boolean',
            'suspend'=>'array'
        ]);

        // return User::max('id');
        $users_is = collect([]);
        $optionals = ['arabicname', 'country', 'birthdate', 'gender', 'phone', 'address', 'nationality', 'notes', 'email', 'suspend',
            'language', 'timezone', 'religion', 'second language', 'level', 'type', 'class_id', 'username'
        ];
        $enrollOptional = 'optional';
        $teacheroptional = 'course';
        $i=0;
        $count=0;
        $max_allowed_users = Contract::whereNotNull('id')->pluck('numbers_of_users')->first();
        $users=Enroll::where('role_id',3)->get();
        if($request->role == 3)
            $count+=1;

        if((count($users) + $count) > $max_allowed_users)
            return HelperController::api_response_format(404 ,$max_allowed_users, 'exceed MAX, U Can\'t add users any more');

        foreach ($request->firstname as $key => $firstname) {
            $username=User::where('username',$request->username[$key])->pluck('username')->count();
            if($username>0)
                return HelperController::api_response_format(404 ,$username, 'This username is already  used');

            $user = User::create([
                'firstname' => $firstname,
                'lastname' => $request->lastname[$key],
                'username' => $request->username[$key],
                'password' => bcrypt($request->password[$key]),
                'real_password' => $request->password[$key],
                'suspend' =>  (isset($request->suspend[$key])) ? $request->suspend[$key] : 0
            ]);

            foreach ($optionals as $optional){
                if($request->filled($optional[$i])){
                    $user->optional =$request->optional[$i];
                }
                if (isset($request->picture[$i]))
                    $user->picture = attachment::upload_attachment($request->picture[$i], 'User')->id;
                if ($request->filled($optional)){
                    if($optional =='birthdate')
                        $user->$optional = Carbon::parse($request->$optional[$i])->format('Y-m-d');
                    $user->$optional =$request->$optional[$i];
                }
            }
            $i++;

            $user->save();
            if(!isset($user->language)){
                $user->language = Language::where('default', 1)->first()->id;
                $user->save();
            }
            $role = Role::find($request->role);
            $user->assignRole($role);
            $Auth_role = Role::find(8);
            $user->assignRole($Auth_role);
            if ($request->role_id == 3) {
                $option = new Request([
                    'users' => [$user->id],
                    'level' => $request->level[$key] ,
                    'type' => $request->type[$key],
                    'class' => $request->class_id[$key]
                ]);
                EnrollUserToCourseController::EnrollInAllMandatoryCourses($option);

                $enrollcounter = 0;
                while (isset($request->$enrollOptional[$key][$enrollcounter])) {
                    $option = new Request([
                        'course' => [$request->$enrollOptional[$key]],
                        'class' =>$request->class_id[$key],
                        'users' => array($user->id),
                        'role_id' => array(3)
                    ]);
                    EnrollUserToCourseController::EnrollCourses($option);
                    $enrollcounter++;
                }
            } else {
                $teachercounter = 0;

                while (isset($request->$teacheroptional[$key][$teachercounter])) {
                    $option = new Request([
                        'course' => [$request->$teacheroptional[$key]],
                        'class' =>$request->class_id[$key],
                        'users' => array($user->id),
                        'role_id' => array(4)
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
        $request->validate([
            'firstname' => 'required|string|min:3|max:50',
            'lastname' => 'required|string|min:3|max:50',
            'id' => 'required|exists:users,id',
            'email' => 'unique:users,email,'.$request->id,
            'password' => 'string|min:6|max:191',
            'username' => 'unique:users,username,'.$request->id,
            'role' => 'exists:roles,id', /// in all system
            'role_id' => 'required_with:level|exists:roles,id', /// chain role
            'suspend' => 'boolean',
            'language' => 'integer|exists:languages,id',
            'second language' => 'integer|exists:languages,id',
        ]);

        $users_is = collect([]);
        $optionals = ['arabicname', 'country', 'birthdate', 'gender', 'phone', 'address','nationality', 'notes', 'email', 'suspend',
            'language', 'timezone', 'religion', 'second language', 'level', 'type', 'class_id',
        ];
        $enrollOptional = 'optional';
        $teacheroptional = 'course';

        $user = User::find($request->id);

        $check = $user->update([
            'firstname' => $request->firstname,
            'lastname' => $request->lastname,
        ]);

            if (Auth::user()->can('user/update-password')) {
                if (isset($request->password)){
                    $user->real_password=$request->password;
                    $user->password =   bcrypt($request->password);
                }
            }

            if (Auth::user()->can('user/update-username')) {
                if (isset($request->username))
                    $user->username=$request->username;
            }

        if (isset($request->picture))
            $user->picture = attachment::upload_attachment($request->picture, 'User')->id;

        foreach ($optionals as $optional) {
            if ($request->filled($optional))
                if($optional =='birthdate')
                    $user->$optional = Carbon::parse($request->$optional)->format('Y-m-d');
                $user->$optional = $request->$optional;
        }
        $user->save();

        // role is in all system
        $role = Role::find($request->role);
        $user->assignRole($role);

        if ($request->role_id == 3) {
            $oldChain=Enroll::where('user_id',$user->id)->where('role_id',$request->role_id)->get();
            foreach($oldChain as $old)
                $old->delete();
            $option = new Request([
                'users' => [$user->id],
                'level' => $request->level ,
                'type' => $request->type,
                'class' => $request->class_id
            ]);
            EnrollUserToCourseController::EnrollInAllMandatoryCourses($option);

            $enrollcounter = 0;
            while (isset($request->$enrollOptional[$enrollcounter])) {
                $option = new Request([
                    'course' => [$request->$enrollOptional],
                    'class' =>$request->class_id,
                    'users' => array($user->id),
                    'role_id' => array(3)
                ]);
                EnrollUserToCourseController::EnrollCourses($option);
                $enrollcounter++;
            }
        } else {
            $teachercounter = 0;

            while (isset($request->$teacheroptional[$teachercounter])) {
                $option = new Request([
                    'course' => [$request->$teacheroptional],
                    'class' =>$request->class_id,
                    'users' => array($user->id),
                    'role_id' => array(4)
                ]);
                EnrollUserToCourseController::EnrollCourses($option);
                $teachercounter++;
            }
        }
        return HelperController::api_response_format(201, $user, 'User updated Successfully');
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
            'search' => 'nullable|string',
            'gender' => 'nullable|string|in:male,female',
            'religion' => 'nullable|string',
            'nationality' => 'nullable|string',
            'country' => 'nullable|string   ',
            'level' => 'nullable|integer|exists:levels,id',
            'type' => 'nullable|integer|exists:academic_types,id',
            'class' => 'nullable|integer|exists:classes,id',
            'segment' => 'nullable|integer|exists:segments,id',
            'course' => 'nullable|integer|exists:courses,id',
            'year' => 'nullable|integer|exists:academic_years,id',
            'roles' => 'nullable|array',
            'roles.*' => 'required|integer|exists:roles,id',
        ]);
        $users = User::where('id','!=',0)->with('roles');
        if($request->filled('country'))
            $users = $users->where('country','LIKE',"%$request->country%");
        if($request->filled('nationality'))
            $users = $users->where('nationality','LIKE',"%$request->nationality%");
        if($request->filled('religion'))
            $users = $users->where('religion','LIKE',"%$request->religion%");
        if($request->filled('gender'))
            $users = $users->where('gender','LIKE',"$request->gender");
        if ($request->filled('roles'))
            $users=$users->whereHas("roles", function ($q) use ($request) {
                $q->whereIn("id", $request->roles);
            });

        $users= $users->pluck('id');

        $enrolled_users=Enroll::whereIn('user_id',$users);
        if ($request->filled('level'))
            $enrolled_users=$enrolled_users->where('level',$request->level);
        if ($request->filled('type'))
            $enrolled_users=$enrolled_users->where('type',$request->type);
        if ($request->filled('class'))
            $enrolled_users=$enrolled_users->where('class',$request->class);
        if ($request->filled('segment'))
            $enrolled_users=$enrolled_users->where('segment',$request->segment);
        if ($request->filled('course'))
            $enrolled_users=$enrolled_users->where('course',$request->course);
        if ($request->filled('year'))
            $enrolled_users=$enrolled_users->where('year',$request->year);
       
        $enrolled_users=$enrolled_users->pluck('user_id');
        $users = User:: whereIn('id',$enrolled_users)->with('roles');
        // $intersect = array_intersect($users->pluck('id')->toArray(),$enrolled_users->pluck('user_id')->toArray());
        // $users=$users->whereIn('id',$intersect);

        if ($request->filled('search'))
            $users=$users->WhereRaw("concat(firstname, ' ', lastname) like '%$request->search%' ")->orWhere('arabicname', 'LIKE' ,"%$request->search%" );
        $users = $users->paginate(HelperController::GetPaginate($request));
        foreach($users->items() as $user)
        {
            if(isset($user->attachment)){
                $user->picture = $user->attachment->path;
            }
        }

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
            'id' => 'nullable|exists:users,id',
        ]);
        if(isset($request->id)){
            $user = User::find($request->id);
        }else{
            $user = User::find(Auth::user()->id);
        }
        if(isset($user->attachment))
            $user->picture = $user->attachment->path;
        $user->roles;

        $i = 0;
        foreach ($user->enroll as $enroll) {
            $all[$i]['role'] = $enroll->roles;

            $segment_Class_id = CourseSegment::where('id', $enroll->CourseSegment->id)->get(['segment_class_id', 'course_id'])->first();
            $all[$i]['Course'] = Course::where('id', $segment_Class_id->course_id)->first();

            $segment = SegmentClass::where('id', $segment_Class_id->segment_class_id)->get(['segment_id', 'class_level_id'])->first();
            $all[$i]['segment'] = Segment::find($segment->segment_id);

            $class_id = ClassLevel::where('id', $segment->class_level_id)->get(['class_id', 'year_level_id'])->first();
            $all[$i]['class'] = Classes::find($class_id->class_id);

            $level = YearLevel::where('id', $class_id->year_level_id)->get(['level_id', 'academic_year_type_id'])->first();
            $all[$i]['level'] = level::find($level->level_id);

            $year_type = AcademicYearType::where('id', $level->academic_year_type_id)->get(['academic_year_id', 'academic_type_id'])->first();
            $all[$i]['type'] = "";
            $all[$i]['year'] = "";
            if(isset($year_type)){
                $all[$i]['type'] = AcademicType::find($year_type->academic_year_id);
                $all[$i]['year'] = AcademicYear::find($year_type->academic_type_id);    
            }
            $i++;
        }
        if (isset($all))
        {
            unset($user->enroll);
            $user->Chain=$all;
            return HelperController::api_response_format(201, $user, null);
        }

        return HelperController::api_response_format(200, $user, 'there is no courses');
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

        if(count($child) > 0)
            return HelperController::api_response_format(201, ['Childs' => $child]);
        if(count($parent) > 0)
            return HelperController::api_response_format(201, ['Parent'=>$parent]);

        return HelperController::api_response_format(201,null,'There is no data for you.');
    }

    /**
     * set paresnt's child
     *
     * @return Assigned Successfully
    */
    public function set_parent_child(Request $request)
    {
        $request->validate([
            'parent_id' => 'required|exists:users,id',
            'child_id' => 'required|array|exists:users,id'
        ]);
        foreach($request->child_id as $child)
        {
            $parent=Parents::firstOrCreate([
                'child_id' => $child,
                'parent_id' => $request->parent_id
            ]);
        }
        return HelperController::api_response_format(201,null,'Assigned Successfully');
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
            'roles.*' => 'required|exists:roles,id',
            'search' => 'nullable'
        ]);

        $searched=collect();
        
        $users=User::whereHas("roles", function ($q) use ($request) {
            $q->whereIn("id", $request->roles);
        })->get();

        if(isset($request->search))
        {
            foreach($users as $user)
            {
                $test=strpos($user->fullname, $request->search);
                if($test > -1)
                    $searched->push($user);
            }
            return HelperController::api_response_format(200, $searched->paginate(HelperController::GetPaginate($request)));
        }

        return HelperController::api_response_format(200, $users->paginate(HelperController::GetPaginate($request)));
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
        foreach($children as $child)
            if(isset($child->attachment))
                $child->picture = $child->attachment->path;

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
        return HelperController::api_response_format(200,$parent ,'Your Parent is ...');
    }

    Public function get_my_users(Request $request){
        $request->validate([
            'courses' => 'array',
            'courses.*' => 'exists:courses,id',
            'classes' => 'array',
            'classes.*' => 'exists:classes,id',
            'levels' => 'array',
            'levels.*' => 'exists:levels,id',
            'roles' => 'array',
            'roles.*' => 'exists:roles,id'
        ]);

        $Given_courseSegments = GradeCategoryController::getCourseSegmentWithArray($request);
        $course_segments = Enroll::where('user_id',Auth::id())->pluck('course_segment')->unique();
        $CS =  array_intersect($course_segments->toArray(),$Given_courseSegments->toArray());
        $users = Enroll::whereIn('course_segment',$CS)->pluck('user_id')->unique();

        if($request->filled('roles')){
            $users = Enroll::whereIn('course_segment',$CS)->whereIn('role_id',$request->roles)->pluck('user_id')->unique();
        }
        $students = user::whereIn('id',$users->toArray())->where('id','!=',Auth::id())->get();
        foreach ($students as $student)
            if(isset($student->attachment))
                $student->picture = $student->attachment->path;

        return HelperController::api_response_format(200,$students ,'Users are.......');
    }

    public function export()
    {
        $filename = uniqid();
         $file = Excel::store(new UsersExport, 'users'.$filename.'.xls','public');
         $file = url(Storage::url('users'.$filename.'.xls'));
         return HelperController::api_response_format(201,$file, 'Link to file ....');
    }
    public function generate_username_password(Request $request)
    {
        $auth = collect([]);
        $auth['username'] = User::generateUsername();
        $auth['password'] =  User::generatePassword()."";
        return HelperController::api_response_format(200,$auth, 'your username and password is ........');

    }
}
