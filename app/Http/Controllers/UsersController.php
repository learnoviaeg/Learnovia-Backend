<?php

namespace App\Http\Controllers;

use App\Repositories\ChainRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\User;
use App\Enroll;
use App\Paginate;
use App\LastAction;
use App\Level;
use App\Classes;
use Spatie\Permission\Models\Permission;
use Carbon\Carbon;
use App\Log;
use App\Lesson;
use App\UserSeen;
use App\GradeCategory;
use App\Segment;
use App\Parents;
use App\AcademicYear;
use App\AcademicType;
use App\YearLevel;
use App\AcademicYearType;
use App\Course;
use App\Contract;
use App\CourseSegment;
use App\ClassLevel;
use Str;
use Spatie\Permission\Models\Role;
use DB;
use App\attachment;
use App\SegmentClass;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use App\Exports\InactiveUsers;

class UsersController extends Controller
{
    protected $chain;

    /**
     * ChainController constructor.
     *
     * @param ChainRepositoryInterface $post
     */
    public function __construct(ChainRepositoryInterface $chain)
    {
        $this->chain = $chain;
        $this->middleware('auth');
        $this->middleware(['permission:course/teachers|course/participants'],   ['only' => ['index']]);
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request,$my_chain=null)
    {
        //validate the request
        $request->validate([
            'year' => 'exists:academic_years,id',
            'type' => 'exists:academic_types,id',
            'level' => 'exists:levels,id',
            'segment' => 'exists:segments,id',
            'courses' => 'array',
            'courses.*' => 'exists:courses,id',
            'class' => 'exists:classes,id',
            'roles' => 'array',
            'roles.*' => 'exists:roles,id',
            'search' => 'string',
        ]);
        //using in chat api new route { api/user/all}
        if($my_chain == 'all'){

            $users = User::with(['attachment','roles']);

            if($request->filled('search')){
                $users->where(function($q) use($request){
                    $q->orWhere('arabicname', 'LIKE' ,"%$request->search%" )
                    ->orWhere('username', 'LIKE' ,"%$request->search%" )
                    ->orWhereRaw("concat(firstname, ' ', lastname) like '%$request->search%' ");
                });
            }
            return response()->json(['message' => __('messages.users.all_list'), 'body' =>   $users->paginate(Paginate::GetPaginate($request))], 200);
        }

        $enrolls = $this->chain->getEnrollsByChain($request);

        if($my_chain=='count'){
            $count = [];
            $roles = new Role;
            if($request->filled('roles'))
                $roles = $roles->whereIn('id',$request->roles);

            $roles = $roles->get();
            $users = User::with(['attachment','roles']);

            $all_roles = Role::all();

            foreach($all_roles as $role){
                $count[Str::slug($role->name, '_')] = DB::table('model_has_roles')->whereIn('model_id',$users->pluck('id'))->where('role_id',$role->id)->count();
            }

            return HelperController::api_response_format(200 ,$count,__('messages.users.count'));
        }

        if($request->filled('class') && getType($request->class) == 'array')
        {
            $requ = new Request([
                'classes' => $request->class,
                'courses' => $request->courses,
            ]);
            $enrolls = $this->chain->getEnrollsByManyChain($requ);
        }
        //using in participants api new route { api/user/participants}
        // if($my_chain=='participants' || $my_chain=='seen_report'){
        //     // site/show/as-participant
        //     $permission = Permission::where('name','site/show/as-participant')->with('roles')->first();
        //     $roles_id = $permission->roles->pluck('id');
        //     if(isset($request->roles))
        //         $roles_id = $permission->roles->whereIn('id',$request->roles)->pluck('id');

        //     $enrolls->whereIn('role_id',$roles_id);
        // }
        if($my_chain=='participants'){
            // site/show/as-participant
            $permission = Permission::where('name','site/show/as-participant')->with('roles')->first();
            $roles_id = $permission->roles->pluck('id');
            if(isset($request->roles))
                $roles_id = $permission->roles->whereIn('id',$request->roles)->pluck('id');

            $enrolls->whereIn('role_id',$roles_id);
        }
        
        //using in chat api new route { api/user/my_chain}
        if($my_chain=='my_chain'){
            // if(!$request->user()->can('site/show-all-courses')) //student
            //     $enrolls=$enrolls->where('user_id',Auth::id());

            // $enrolls =  Enroll::whereIn('course_segment',$enrolls->pluck('course_segment'))->where('user_id' ,'!=' , Auth::id());
            if($request->user()->can('site/course/student'))
                $enrolls->where('role_id','!=',7);
        }

        if($request->filled('roles'))
            $enrolls->whereIn('role_id',$request->roles);

        if ($request->filled('courses')){

            $enrolls->with(['user.lastactionincourse'=>function ($query) use($request){
                    $query->whereIn('course_id',$request->courses);
                }]);
        }

        if($my_chain=='dropdown'){
            $permission = Permission::where('name','site/course/student')->with('roles')->first();
            $roles_id = $permission->roles->pluck('id');
            $enrolls->whereIn('role_id',$roles_id);

            $enrolls =  $enrolls->select('user_id','group')->distinct()->with(['user.attachment', 'classes'])->get()->filter()->values();
            return response()->json(['message' => __('messages.users.list'), 'body' =>   $enrolls->paginate(Paginate::GetPaginate($request))], 200);
        }

        $enrolls =  $enrolls->select('user_id','group')->distinct()->with(['user.attachment','user.roles', 'classes'])->get()->pluck('user')->filter()->values();
        if($request->filled('search'))
        {
            $enrolls = collect($enrolls)->filter(function ($item) use ($request) {
                if((($item->arabicname!=null) && str_contains($item->arabicname, $request->search))||
                 str_contains(strtolower($item->username),strtolower($request->search))||
                  str_contains(strtolower($item->fullname),strtolower($request->search))) 
                    return $item; 
            });
        }

        return response()->json(['message' => __('messages.users.list'), 'body' =>   $enrolls->paginate(Paginate::GetPaginate($request))], 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id, Request $requ)
    {
        $user = User::find($id);
        if(isset($user->attachment))
            $user->picture = $user->attachment->path;
        $user->roles;
        if(isset($user->class_id))
           $user['class_name']=Classes::find($user->class_id)->name;
        if(isset($user->level))
           $user['level_name']=Level::find($user->level)->name;
        $i = 0;
        foreach ($user->enroll as $enroll) {
            $all[$i]['role'] = $enroll->roles;
            $all[$i]['enroll_id'] = $enroll->id;

            // $segment_Class_id = CourseSegment::where('id', $enroll->CourseSegment->id)->get(['segment_class_id', 'course_id'])->first();
            $all[$i]['Course'] = Course::where('id', $enroll->course)->first();

            // $segment = SegmentClass::where('id', $segment_Class_id->segment_class_id)->get(['segment_id', 'class_level_id'])->first();
            $all[$i]['segment'] = Segment::find($enroll->segment);

            // $class_id = ClassLevel::where('id', $segment->class_level_id)->get(['class_id', 'year_level_id'])->first();
            $all[$i]['class'] = Classes::find($enroll->group);

            // $level = YearLevel::where('id', $class_id->year_level_id)->get(['level_id', 'academic_year_type_id'])->first();
            $all[$i]['level'] = level::find($enroll->level);

            // $year_type = AcademicYearType::where('id', $level->academic_year_type_id)->get(['academic_year_id', 'academic_type_id'])->first();
            // $all[$i]['type'] = "";
            // $all[$i]['year'] = "";
            // if(isset($year_type)){
                $all[$i]['type'] = AcademicType::find($enroll->type);
                $all[$i]['year'] = AcademicYear::find($enroll->year);    
            // }
            $i++;
        }
        if(!Auth::user()->can('site/show-all-courses') && $id != Auth::id()){
            $enrolls = $this->chain->getEnrollsByManyChain($requ);

            // $chain = Enroll::where('user_id',Auth::id())->pluck('course_segment');
            $users = $enrolls->where('user_id' ,'!=' , Auth::id())->pluck('user_id');
            if (!in_array($id, $users->toArray()))
                return response()->json(['message' => __('messages.error.not_allowed'), 'body' => null ], 404);
            if(!Auth::user()->can('allow-edit-profiles')){
                // unset($user->username);
                unset($user->real_password);
            }
        }

        if (isset($all))
        {
            unset($user->enroll);
            $user->Chain=$all;
            return response()->json(['message' => null, 'body' => $user ], 200);
        }
        return response()->json(['message' =>  __('messages.error.no_available_data'), 'body' => $user ], 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
