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
use App\Events\MassLogsEvent;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
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
use DB;
use Str;
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
            // 'nickname' => 'array',
            // 'nickname.*' => 'string|min:3|max:50',
            'firstname' => 'required|array',
            'firstname.*' => 'required|string|min:3|max:50',
            'lastname' => 'required|array',
            'lastname.*' => 'required|string|min:3|max:50',
            'password' => 'required|array',
            'password.*' => 'required|string|min:6|max:191',
            // 'role' => 'required|array',
            // 'role.*' => 'required|exists:roles,id',
            'role' => 'required|integer|exists:roles,id', /// in all system
            'role_id' => 'required_with:level|exists:roles,id|integer', /// chain role
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
            'language', 'timezone', 'religion', 'second language', 'level', 'type', 'class_id', 'username','nickname'
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
            if (isset($request->picture[$i]))
                    $user_picture = attachment::upload_attachment($request->picture[$i], 'User');
            $clientt = new Client();
            $data = array(
                'name' => $firstname. " " .$request->lastname[$key], 
                'meta_data' => array(
                    "image_link" => ($request->has('picture'))?$user_picture->path:null,
                    'role'=> Role::find($request->role)->name,
                ),
            );    
            $data = json_encode($data);

            $res = $clientt->request('POST', 'https://us-central1-learnovia-notifications.cloudfunctions.net/createUser', [
                'headers'   => [
                    'Content-Type' => 'application/json'
                ], 
                'body' => $data
            ]);
            
            $user = User::create([
                'firstname' => $firstname,
                'lastname' => $request->lastname[$key],
                'username' => $request->username[$key],
                'password' => bcrypt($request->password[$key]),
                'real_password' => $request->password[$key],
                'suspend' =>  (isset($request->suspend[$key])) ? $request->suspend[$key] : 0,
                'chat_uid' => json_decode($res->getBody(),true)['user_id'],
                'chat_token' => json_decode($res->getBody(),true)['custom_token'],
                'refresh_chat_token' => json_decode($res->getBody(),true)['refresh_token']

            ]);

            foreach ($optionals as $optional){
                if($request->filled($optional[$i])){
                    $user->optional =$request->optional[$i];
                }
                if (isset($request->picture[$i]))
                    $user->picture = $user_picture->id;
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
            // $Auth_role = Role::find(8);
            // $user->assignRole($Auth_role);
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
            'nickname'=>'nullable|string|min:3|max:50',
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
            'birthdate' => 'nullable|date'
        ]);

        $users_is = collect([]);
        $optionals = ['arabicname', 'country', 'birthdate', 'gender', 'phone', 'address','nationality', 'notes', 'email', 'suspend',
            'language', 'timezone', 'religion', 'second language', 'level', 'type', 'class_id','nickname'
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
            if ($request->has($optional)){

                $user->$optional = $request->$optional;

                if($optional =='birthdate' && isset($request->birthdate))
                    $user->$optional = Carbon::parse($request->$optional)->format('Y-m-d');

                if($optional == 'suspend' && $request->suspend == 1){
                    $user->token = null;

                    $tokens = $user->tokens->where('revoked',false);
                    foreach($tokens as $token){
                        $token->revoke();
                    }
                
                    unset($user->tokens);
                }

                if($optional == 'nickname' && $request->$optional == 'null')
                    $user->$optional = null;
            }
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
    public function list(Request $request, $call = 0)
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
            'count' => 'in:1,0'
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
            $users= $users->whereHas("roles", function ($q) use ($request) {
            $q->whereIn("id", $request->roles);
        });
        
        $flag=false;
        $enrolled_users=Enroll::where('id','!=',0);
        if ($request->filled('level')){
            $enrolled_users=$enrolled_users->where('level',$request->level);
            $flag=true;
        }if ($request->filled('type')){            
            $enrolled_users=$enrolled_users->where('type',$request->type);
            $flag=true;
        }if ($request->filled('class')){ 
            $enrolled_users=$enrolled_users->where('class',$request->class);
            $flag=true;
        }if ($request->filled('segment')){            
            $enrolled_users=$enrolled_users->where('segment',$request->segment);
            $flag=true;
        }if ($request->filled('course')){            
            $enrolled_users=$enrolled_users->where('course',$request->course);
            $flag=true;
        }if ($request->filled('year')){            
            $enrolled_users=$enrolled_users->where('year',$request->year);
            $flag=true;
        }      
        // $enrolled_users=$enrolled_users->pluck('user_id');
        // $users = User:: whereIn('id',$enrolled_users)->with('roles');
        
        if($flag){
            $intersect = array_intersect($users->pluck('id')->toArray(),$enrolled_users->pluck('user_id')->toArray());
            $users=$users->whereIn('id',$intersect);
        }
        
        if ($request->filled('search'))
        
            $users=$users->where(function($q) use($request){
                $q->orWhere('arabicname', 'LIKE' ,"%$request->search%" )
                ->orWhere('username', 'LIKE' ,"%$request->search%" )
                ->orWhereRaw("concat(firstname, ' ', lastname) like '%$request->search%' ");
            });
            if($call == 1){
                $students = $users->pluck('id');
                return $students;
            }
    
        if($request->has('count') && $request->count == 1){
            $count = [];
            $roles = new Role;
            if($request->filled('roles'))
                $roles = $roles->whereIn('id',$request->roles);

            $roles = $roles->get();
            $users= $users->pluck('id');

            foreach($roles as $role){
                $count[Str::slug($role->name, '_')] = DB::table('model_has_roles')->whereIn('model_id',$users)->where('role_id',$role->id)->count();
            }

            return HelperController::api_response_format(200 ,$count,'User roles count');
        }

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
            'suspend' => 1,
            'token' => null
        ]);

        $tokens = $user->tokens->where('revoked',false);

        foreach($tokens as $token){
            $token->revoke();
        }
    
        unset($user->tokens);

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
            $all[$i]['enroll_id'] = $enroll->id;

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
                $all[$i]['type'] = AcademicType::find($year_type->academic_type_id);
                $all[$i]['year'] = AcademicYear::find($year_type->academic_year_id);    
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

    public function getParents(Request $request)
    {
        $users=array();
        $parents=User::whereHas("roles",function ($q){
            $q->where('name','Parent');
        })->where( function($q)use($request){
            $q->orWhere('arabicname', 'LIKE' ,"%$request->search%" )
                    ->orWhere('username', 'LIKE' ,"%$request->search%" )
                    ->orWhereRaw("concat(firstname, ' ', lastname) like '%$request->search%' ");
        })->with('attachment')->get();
        return HelperController::api_response_format(201,$parents->paginate(HelperController::GetPaginate($request)),'There r parents');
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
            'child_id' => 'exists:parents,child_id'
        ]);

        //for log event
        $logsbefore=Parents::where('parent_id',Auth::id())->get();
        $all = Parents::where('parent_id',Auth::id())->update(['current'=> 0]);
        if($all > 0)
            event(new MassLogsEvent($logsbefore,'updated'));

        $current_child=null;
        if(isset($request->child_id)){
            Parents::where('child_id',$request->child_id)->where('parent_id',Auth::id())->update(['current'=> 1]);
            $current_child = User::where('id',$request->child_id)->first();
        }
        return HelperController::api_response_format(200,$current_child ,'Children sets successfully');
    }

    Public Function getCurrentChild(Request $request)
    {
        $current = Auth::user()->currentChild;
        $currentChild = null;
        if(isset($current))
            $currentChild =User::find($current->child_id);

        return HelperController::api_response_format(200,$currentChild, 'Current child is...');
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
            'roles.*' => 'exists:roles,id',
            'search' => 'string'
        ]);

        $Given_courseSegments = GradeCategoryController::getCourseSegmentWithArray($request);
        $course_segments = Enroll::where('user_id',Auth::id())->pluck('course_segment')->unique();
        if($request->user()->can('site/show-all-courses'))
            $course_segments=$Given_courseSegments;
        $CS =  array_intersect($course_segments->toArray(),$Given_courseSegments->toArray());
        $users = Enroll::whereIn('course_segment',$CS)->pluck('user_id')->unique();

        if($request->filled('roles')){
            $users = Enroll::whereIn('course_segment',$CS)->whereIn('role_id',$request->roles)->pluck('user_id')->unique();
        }
        $students = user::whereIn('id',$users->toArray())->where('id','!=',Auth::id())->get();
        foreach ($students as $student)
            if(isset($student->attachment))
                $student->picture = $student->attachment->path;

        if(isset($request->search))
        {
            $students = user::whereIn('id',$users->toArray())->where('id','!=',Auth::id())
                                ->where( function($q)use($request){
                                            $q->orWhere('arabicname', 'LIKE' ,"%$request->search%" )
                                                    ->orWhere('username', 'LIKE' ,"%$request->search%" )
                                                    ->orWhereRaw("concat(firstname, ' ', lastname) like '%$request->search%' ");
                                            })->with('attachment')->get();
        }

        return HelperController::api_response_format(200,$students ,'Users are.......');
    }

    public function export(Request $request)
    {
        $fields = ['id', 'firstname', 'lastname'];

        if (Auth::user()->can('site/show/username')) {
            $fields[] = 'username';
        }

        $fields = array_merge( $fields, ['arabicname', 'country', 'birthdate', 'gender',
        'phone', 'address', 'nationality', 'notes','email','suspend'] );

        if (Auth::user()->can('site/show/real-password')) {
            array_push($fields,'real_password');
        }

        $fields = array_merge($fields, [ 'religion', 'created_at',
        'class_id','level', 'type','second language','role'] );

        $userIDs = self::list($request,1);
        $filename = uniqid();
        $file = Excel::store(new UsersExport($userIDs,$fields), 'users'.$filename.'.xls','public');
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

    public function GetAllCountries(Request $request)
    {
        $countries = array(
            "Afghanistan", "Aland Islands", "Albania", "Algeria", "American Samoa", "Andorra", "Angola", "Anguilla", "Antarctica",
            "Antigua", "Argentina", "Armenia", "Aruba", "Australia", "Austria", "Azerbaijan", "Bahamas", "Bahrain", "Bangladesh", 
            "Barbados", "Barbuda", "Belarus", "Belgium", "Belize", "Benin", "Bermuda", "Bhutan", "Bolivia", "Bosnia", "Botswana", "Bouvet Island",
            "Brazil", "British Indian Ocean Trty.", "Brunei Darussalam", "Bulgaria", "Burkina Faso", "Burundi", "Caicos Islands", "Cambodia",
            "Cameroon", "Canada", "Cape Verde", "Cayman Islands", "Central African Republic", "Chad", "Chile", "China", "Christmas Island", 
            "Cocos (Keeling) Islands", "Colombia", "Comoros", "Congo", "Congo, Democratic Republic of the", "Cook Islands", "Costa Rica", 
            "Cote d'Ivoire", "Croatia", "Cuba", "Cyprus", "Czech Republic", "Denmark", "Djibouti", "Dominica", "Dominican Republic", "Ecuador", 
            "Egypt", "El Salvador", "Equatorial Guinea", "Eritrea", "Estonia", "Ethiopia", "Falkland Islands (Malvinas)", "Faroe Islands", 
            "Fiji", "Finland", "France", "French Guiana", "French Polynesia", "French Southern Territories", "Futuna Islands", "Gabon", "Gambia", 
            "Georgia", "Germany", "Ghana", "Gibraltar", "Greece", "Greenland", "Grenada", "Guadeloupe", "Guam", "Guatemala", "Guernsey", "Guinea", 
            "Guinea-Bissau", "Guyana", "Haiti", "Heard", "Herzegovina", "Holy See", "Honduras", "Hong Kong", "Hungary", "Iceland", "India", 
            "Indonesia", "Iran (Islamic Republic of)", "Iraq", "Ireland", "Isle of Man", "Israel", "Italy", "Jamaica", "Jan Mayen Islands", 
            "Japan", "Jersey", "Jordan", "Kazakhstan", "Kenya", "Kiribati", "Korea", "Korea (Democratic)", "Kuwait", "Kyrgyzstan", "Lao", 
            "Latvia", "Lebanon", "Lesotho", "Liberia", "Libyan Arab Jamahiriya", "Liechtenstein", "Lithuania", "Luxembourg", "Macao", "Macedonia", 
            "Madagascar", "Malawi", "Malaysia", "Maldives", "Mali", "Malta", "Marshall Islands", "Martinique", "Mauritania", "Mauritius", 
            "Mayotte", "McDonald Islands", "Mexico", "Micronesia", "Miquelon", "Moldova", "Monaco", "Mongolia", "Montenegro", "Montserrat", 
            "Morocco", "Mozambique", "Myanmar", "Namibia", "Nauru", "Nepal", "Netherlands", "Netherlands Antilles", "Nevis", "New Caledonia", 
            "New Zealand", "Nicaragua", "Niger", "Nigeria", "Niue", "Norfolk Island", "Northern Mariana Islands", "Norway", "Oman", "Pakistan", 
            "Palau", "Palestinian Territory, Occupied", "Panama", "Papua New Guinea", "Paraguay", "Peru", "Philippines", "Pitcairn", "Poland", 
            "Portugal", "Principe", "Puerto Rico", "Qatar", "Reunion", "Romania", "Russian Federation", "Rwanda", "Saint Barthelemy", 
            "Saint Helena", "Saint Kitts", "Saint Lucia", "Saint Martin (French part)", "Saint Pierre", "Saint Vincent", "Samoa", "San Marino", 
            "Sao Tome", "Saudi Arabia", "Senegal", "Serbia", "Seychelles", "Sierra Leone", "Singapore", "Slovakia", "Slovenia", "Solomon Islands", 
            "Somalia", "South Africa", "South Georgia", "South Sandwich Islands", "Spain", "Sri Lanka", "Sudan", "Suriname", "Svalbard", 
            "Swaziland", "Sweden", "Switzerland", "Syrian Arab Republic", "Taiwan", "Tajikistan", "Tanzania", "Thailand", "The Grenadines", 
            "Timor-Leste", "Tobago", "Togo", "Tokelau", "Tonga", "Trinidad", "Tunisia", "Turkey", "Turkmenistan", "Turks Islands", "Tuvalu", 
            "Uganda", "Ukraine", "United Arab Emirates", "United Kingdom", "United States", "Uruguay", "US Minor Outlying Islands", "Uzbekistan", 
            "Vanuatu", "Vatican City State", "Venezuela", "Vietnam", "Virgin Islands (British)", "Virgin Islands (US)", "Wallis", 
            "Western Sahara", "Yemen", "Zambia", "Zimbabwe"
        );

        return HelperController::api_response_format(200 ,$countries, 'Countries are ...');
    }
    
    public function GetAllNationalities()
    {
        $nationals=array(
            'Afghan','Albanian','Algerian','American','Andorran','Angolan','Antiguans','Argentinean','Armenian','Australian','Austrian',
            'Azerbaijani','Bahamian','Bahraini','Bangladeshi','Barbadian','Barbudans','Batswana','Belarusian','Belgian','Belizean',
            'Beninese','Bhutanese','Bolivian','Bosnian','Brazilian','British','Bruneian','Bulgarian','Burkinabe','Burmese','Burundian',
            'Cambodian','Cameroonian','Canadian','Cape Verdean','Central African','Chadian','Chilean','Chinese','Colombian','Comoran',
            'Congolese','Costa Rican','Croatian','Cuban','Cypriot','Czech','Danish','Djibouti','Dominican','Dutch','East Timorese',
            'Ecuadorean','Egyptian','Emirian','Equatorial Guinean','Eritrean','Estonian','Ethiopian','Fijian','Filipino','Finnish',
            'French','Gabonese','Gambian','Georgian','German','Ghanaian','Greek','Grenadian','Guatemalan','Guinea-Bissauan','Guinean',
            'Guyanese','Haitian','Herzegovinian','Honduran','Hungarian','I-Kiribati','Icelander','Indian','Indonesian','Iranian','Iraqi',
            'Irish','Israeli','Italian','Ivorian','Jamaican','Japanese','Jordanian','Kazakhstani','Kenyan','Kittian and Nevisian','Kuwaiti',
            'Kyrgyz','Laotian','Latvian','Lebanese','Liberian','Libyan','Liechtensteiner','Lithuanian','Luxembourger','Macedonian','Malagasy',
            'Malawian','Malaysian','Maldivan','Malian','Maltese','Marshallese','Mauritanian','Mauritian','Mexican','Micronesian','Moldovan',
            'Monacan','Mongolian','Moroccan','Mosotho','Motswana','Mozambican','Namibian','Nauruan','Nepalese','New Zealander','Nicaraguan',
            'Nigerian','Nigerien','North Korean','Northern Irish','Norwegian','Omani','Pakistani','Palauan','Panamanian','Papua New Guinean',
            'Paraguayan','Peruvian','Polish','Portuguese','Qatari','Romanian','Russian','Rwandan','Saint Lucian','Salvadoran','Samoan',
            'San Marinese','Sao Tomean','Saudi','Scottish','Senegalese','Serbian','Seychellois','Sierra Leonean','Singaporean','Slovakian',
            'Slovenian','Solomon Islander','Somali','South African','South Korean','Spanish','Sri Lankan','Sudanese','urinamer','Swazi',
            'Swedish','Swiss','Syrian','Taiwanese','Tajik','Tanzanian','Thai','Togolese','Tongan','Trinidadian/Tobagonian','Tunisian',
            'Turkish','Tuvaluan','Ugandan','Ukrainian','Uruguayan','Uzbekistani','Venezuelan','Vietnamese','Welsh','Yemenite','Zambian',
            'Zimbabwean'
        );
        return HelperController::api_response_format(200 ,$nationals, 'Nationalities are ...');
    }
}
