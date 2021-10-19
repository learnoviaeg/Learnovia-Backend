<?php

namespace App\Http\Controllers;

use App\Repositories\ChainRepositoryInterface;
use App\Helpers\ComponentsHelper;
use Illuminate\Http\Request;
use App\Level;
use App\Classes;
use App\Course;
use App\Exports\CourseProgressReport;
use App\User;
use App\Paginate;
use App\LAstAction;
use Spatie\Permission\Models\Permission;
use Carbon\Carbon;
use App\Log;
use App\UserSeen;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use App\Exports\InactiveUsers;
use Modules\QuestionBank\Entities\QuizLesson;

class ReportsController extends Controller
{
    public function __construct(ChainRepositoryInterface $chain)
    {
        $this->chain = $chain;
        $this->middleware('auth');
        $this->middleware(['permission:course/teachers|course/participants' , 'ParentCheck'],   ['only' => ['index']]);
    }

    public function index(Request $request,$option=null)
    {
        //validate the request
        $request->validate([
            'year' => 'exists:academic_years,id',
            'type' => 'exists:academic_types,id',
            'level' => 'exists:levels,id',
            'segment' => 'exists:segments,id',
            'courses' => ['array',Rule::requiredIf($option === 'seen_report' || $option === 'seen_report_chart')],
            'courses.*' => 'exists:courses,id',
            'class' => ['exists:classes,id',Rule::requiredIf($option === 'seen_report' || $option === 'seen_report_chart')],
            'roles' => 'array',
            'roles.*' => 'exists:roles,id',
            'search' => 'string',
            'item_type' => ['string','in:page,file,media,assignment,quiz,meeting,h5p','required_with:item_id',Rule::requiredIf($option === 'seen_report' || $option === 'seen_report_chart')],
            'lesson_id' => ['exists:lessons,id',Rule::requiredIf($option === 'seen_report' || $option === 'seen_report_chart')],
            'view_status' => 'in:yes,no',
            'item_id' => ['integer',Rule::requiredIf($option === 'seen_report' || $option === 'seen_report_chart')],
            'from' => 'date|required_with:to',
            'to' => 'date|required_with:from',
            'times' => 'integer',
        ]);

        $enrolls = $this->chain->getEnrollsByManyChain($request);
        if($request->filled('class') && getType($request->class) == 'array')
        {
            $requ = new Request([
                'classes' => $request->class,
                'courses' => $request->courses,
            ]);
            $enrolls = $this->chain->getEnrollsByManyChain($requ);
        }

        //using in participants api new route { api/user/participants}
        if( $option=='seen_report'){
            // site/show/as-participant
            $permission = Permission::where('name','site/show/as-participant')->with('roles')->first();
            $roles_id = $permission->roles->pluck('id');
            if(isset($request->roles))
                $roles_id = $permission->roles->whereIn('id',$request->roles)->pluck('id');

            $enrolls->whereIn('role_id',$roles_id);
        }


        if($request->filled('roles'))
            $enrolls->whereIn('role_id',$request->roles);

        if ($request->filled('courses')){

            $enrolls->with(['user.lastactionincourse'=>function ($query) use($request){
                    $query->whereIn('course_id',$request->courses);
                }]);
        }

        $enrolls =  $enrolls->select('user_id')->distinct()->with(['user.attachment','user.roles'])->get()->pluck('user')->filter()->values();

        if($request->filled('search'))
        {
            $enrolls = collect($enrolls)->filter(function ($item) use ($request) {
                if((($item->arabicname!=null) && str_contains($item->arabicname, $request->search))||
                 str_contains(strtolower($item->username),strtolower($request->search))||
                  str_contains(strtolower($item->fullname),strtolower($request->search))) 
                    return $item; 
            });
        }

        //using in active user api new route { api/user/active} && { api/user/in_active}
        if($option == 'active' || $option == 'in_active'){

            if($option == 'active' && !$request->user()->can('reports/active_users'))
                return response()->json(['message' => __('messages.error.no_permission'), 'body' => null], 403);

            if($option == 'in_active' && !$request->user()->can('reports/in_active_users'))
                return response()->json(['message' => __('messages.error.no_permission'), 'body' => null], 403);

            $request->validate([
                'from' => 'date|required_with:to',
                'to' => 'date|required_with:from',
                'report_year' => 'required|integer',
                'report_month' => 'integer|required_with:report_day',
                'report_day' => 'integer',
                'never' => 'in:1',
                'since' => 'in:1,5,10',
                'export' => 'in:1'
            ]);

            $users_lastaction = Log::whereYear('created_at', $request->report_year)->whereIn('user',$enrolls->pluck('username'))->with('users');
            
            if($request->filled('report_month'))
                $users_lastaction->whereMonth('created_at',$request->report_month);
            
            if($request->filled('report_day'))
                $users_lastaction->whereDay('created_at',$request->report_day);

            if($request->filled('from') && $request->filled('to'))
                $users_lastaction->whereBetween('created_at', [$request->from, $request->to]);
            
            $since = 10;
            if($option == 'in_active')
                $since = 59;

            if($request->filled('since'))
                $since = $request->since;

            $users_lastaction->where('created_at','>=' ,Carbon::now()->subMinutes($since))->where('created_at','<=' ,Carbon::now());

            if($option == 'in_active'){
                $active = $users_lastaction->pluck('user');  
                $users_lastaction =Log::whereYear('created_at', $request->report_year)
                                        ->whereIn('user',$enrolls->pluck('username'))->with('users')
                                        ->where('created_at','<=' ,Carbon::now()->subHours(1))
                                        ->whereNotIn('user',$active);
            }    
            $users_lastaction = $users_lastaction->select('user')->distinct()->get()->pluck('users');
            
            if($request->filled('never')){

                if(!$request->filled('year')){
                    $enrolls = User::get();
                }
                $last_actions = LastAction::whereNull('course_id')->pluck('user_id');
                $users_lastaction  = $enrolls->whereNotIn('id',$last_actions)->values();
            }

            if($request->filled('export')){

                $filename = uniqid();
                $file = Excel::store(new InactiveUsers($users_lastaction), 'reports'.$filename.'.xlsx','public');
                $file = url(Storage::url('reports'.$filename.'.xlsx'));
                return response()->json(['message' => __('messages.success.link_to_file') , 'body' => $file], 200);

            }

            return response()->json(['message' => $option.' users list ', 'body' => $users_lastaction], 200);
        }

        if($option == 'seen_report' || $option == 'seen_report_chart'){

            if(!$request->user()->can('reports/seen_report'))
                return response()->json(['message' => __('messages.error.no_permission'), 'body' => null], 403);

            $seen_users = UserSeen::where('type',$request->item_type)->where('item_id',$request->item_id);

            if($request->filled('lesson_id'))
                $seen_users->where('lesson_id',$request->lesson_id);
            
            $seen_users = $seen_users->get();

            if($request->filled('from') && $request->filled('to')){
                $seen_users = $seen_users->whereBetween('updated_at', [$request->from, $request->to]);
                $enrolls = $enrolls->whereIn('id',$seen_users->pluck('user_id'))->values();
            }

            $enrolls->map(function ($user) use ($seen_users,$request) {

                $user['seen'] = 'no';
                $user['seen_count'] = 0;
                $user['seen_at'] = null;
                if(in_array($user->id,$seen_users->pluck('user_id')->toArray())){
                    
                    $seen = UserSeen::where('type',$request->item_type)->where('item_id',$request->item_id)->where('user_id',$user->id)->first();

                    $user['seen'] = 'yes';
                    $user['seen_count'] = $seen->count;
                    $user['seen_at'] = $seen->updated_at;
                }
                
                return $user;
            });

            if($request->filled('view_status')){
                $enrolls = $enrolls->where('seen',$request->view_status)->values();
            }

            if($request->filled('times')){
                $enrolls = $enrolls->where('seen_count',$request->times)->values();
            }

            if($option == 'seen_report_chart'){

                $total = count($enrolls);
                $seen_users = count($enrolls->where('seen','yes'));
                $percentage = 0;
                if($total != 0)
                    $percentage = round(($seen_users/$total)*100,1);
                return response()->json(['message' => 'Seen percentage', 'body' => $percentage  ], 200);
                
            }
        }

        return response()->json(['message' => __('messages.users.list'), 'body' =>   $enrolls->paginate(Paginate::GetPaginate($request))], 200);
    }


    public function courseProgressReport(Request $request){

        $types = ['materials','assignments','quizzes','interactives','virtuals'];

        //validate the request
        $request->validate([
            'years'    => 'nullable|array',
            'years.*' => 'exists:academic_years,id',
            'types'    => 'nullable|array',
            'types.*' => 'exists:academic_types,id',
            'levels'    => 'nullable|array',
            'levels.*' => 'exists:levels,id',
            'classes'    => 'nullable|array',
            'classes.*' => 'exists:classes,id',
            'segments'    => 'nullable|array',
            'segments.*' => 'exists:segments,id',
            'courses' => 'array',
            'courses.*' => 'exists:courses,id',
            'from' => 'date|required_with:to',
            'to' => 'date|required_with:from',
            'user_id' => 'exists:users,id',
            'component' => 'in:'.implode(',',$types),
            'details' => 'in:1',
            'export' => 'in:1'
        ]);

        if($request->has('component')){
            $types = [$request->component];
        }

        $enrolls = $this->chain->getEnrollsByManyChain($request);
   
        $courses = $enrolls->orderBy('level')->select('course')->distinct()->with('courses')->get()->pluck('courses')->filter();
       
        $reportObjects = collect();

        foreach($courses as $course){

            $level = $course->level->name;

            foreach($course->classes as $groupId){

                $group = Classes::whereId($groupId)->pluck('name')->first();

                $componentsHelper = new ComponentsHelper();

                $componentsHelper->setCourse($course->id);
                $componentsHelper->setClass($groupId);

                if($request->has('user_id')){
                    $componentsHelper->setTeacher($request->user_id);
                }

                if($request->has('from') && $request->has('to')){
                    $componentsHelper->setDate($request->from,$request->to);
                }

                foreach($types as $type){

                    //if we need the detailed report
                    if($request->has('details') && $request->details){

                        $items = $componentsHelper->$type()->with('user')->get();
                    
                        foreach($items as $item){

                            $reportObjects->push([
                                'level' => $level,
                                'course' => $course->name,
                                'class' => $group,
                                'type' => $type,
                                'item_name' => $item->name,
                                'item_id' => $item->id,
                                'created_at' => Carbon::parse($item->created_at)->format('Y-m-d h:i:s'),
                                'teacher' => $item->user? $item->user->full_name : null
                            ]);
                        }
                    }

                    //if just the counters
                    if(!$request->has('details')){

                        $reportObjects->push([
                            'level' => $level,
                            'course' => $course->name,
                            'class' => $group,
                            'type' => $type,
                            'count' => $componentsHelper->$type()->count(),
                        ]);
                    }
                }

            }
        }

        if($request->filled('export')){

            $exportDetails = 0;
            $fields = ['level','course','class','type','count'];

            if($request->has('details')){
                $exportDetails = 1;
                $fields = ['level','course','class','type','item_name','item_id','created_at','teacher'];
            }

            $filename = uniqid();
            $file = Excel::store(new CourseProgressReport($reportObjects,$exportDetails,$fields), 'reports'.$filename.'.xlsx','public');
            $file = url(Storage::url('reports'.$filename.'.xlsx'));
            return response()->json(['message' => __('messages.success.link_to_file') , 'body' => $file], 200);
        }

        return response()->json(['message' => 'Course progress', 'body' =>  $reportObjects->paginate(Paginate::GetPaginate($request))], 200);
    }

    public function CourseProgressCounters(Request $request){

        $types = ['materials','assignments','quizzes','interactives','virtuals'];

        //validate the request
        $request->validate([
            'years'    => 'nullable|array',
            'years.*' => 'exists:academic_years,id',
            'types'    => 'nullable|array',
            'types.*' => 'exists:academic_types,id',
            'levels'    => 'nullable|array',
            'levels.*' => 'exists:levels,id',
            'classes'    => 'nullable|array',
            'classes.*' => 'exists:classes,id',
            'segments'    => 'nullable|array',
            'segments.*' => 'exists:segments,id',
            'courses' => 'array',
            'courses.*' => 'exists:courses,id',
            'from' => 'date|required_with:to',
            'to' => 'date|required_with:from',
            'user_id' => 'exists:users,id',
            'component' => 'in:'.implode(',',$types)
        ]);

        //need to be refactored (line below)
        $lessons = $this->chain->getEnrollsByManyChain($request)->with('SecondaryChain')->get()->pluck('SecondaryChain.*.lesson_id')->collapse()->unique();

        $componentsHelper = new ComponentsHelper();
        $componentsHelper->setLessons($lessons);

        if($request->has('user_id')){
            $componentsHelper->setTeacher($request->user_id);
        }

        if($request->has('from') && $request->has('to')){
            $componentsHelper->setDate($request->from,$request->to);
        }

        $counterObject = collect();

        foreach($types as $type){

            $count = $componentsHelper->$type()->count();

            if($request->has('component') && $request->component != $type){
                $count = 0;
            }

            $counterObject->push([
                'type' =>$type,
                'count' => $count
            ]);
        }

        return response()->json(['message' => 'Course progress Counters', 'body' =>  $counterObject], 200);
    }

    public function quizStatusReport(){
        
        $quizzes = QuizLesson::with(['quiz','lesson.course','lesson' => function($query){
                                    $query->withCount(['SecondaryChain as students_number'=> function($q){
                                        $q->where('role_id',3);
                                    }]);
                                }])
                                ->withCount(['user_quiz as solved_students','user_quiz as full_mark' => function($q){
                                    $q->where('user_quizzes.grade', 'quiz_lessons.grade');
                                }])
                                ->get();
        return $quizzes;
    }
    
}
