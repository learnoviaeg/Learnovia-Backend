<?php

namespace App\Http\Controllers;

use App\Repositories\ChainRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\User;
use App\Enroll;
use App\Paginate;
use App\LAstAction;
use Spatie\Permission\Models\Permission;
use Carbon\Carbon;
use App\Log;
use App\Lesson;
use App\UserSeen;
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
        $this->middleware(['permission:course/teachers|course/participants' , 'ParentCheck'],   ['only' => ['index']]);
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
            'courses' => ['array',Rule::requiredIf($my_chain === 'seen_report' || $my_chain === 'seen_report_chart')],
            'courses.*' => 'exists:courses,id',
            'class' => ['exists:classes,id',Rule::requiredIf($my_chain === 'seen_report' || $my_chain === 'seen_report_chart')],
            'roles' => 'array',
            'roles.*' => 'exists:roles,id',
            'search' => 'string',
            'item_type' => ['string','in:page,file,media,assignment,quiz,meeting,h5p','required_with:item_id',Rule::requiredIf($my_chain === 'seen_report' || $my_chain === 'seen_report_chart')],
            'lesson_id' => ['exists:lessons,id',Rule::requiredIf($my_chain === 'seen_report' || $my_chain === 'seen_report_chart')],
            'view_status' => 'in:yes,no',
            'item_id' => ['integer',Rule::requiredIf($my_chain === 'seen_report' || $my_chain === 'seen_report_chart')],
            'from' => 'date|required_with:to',
            'to' => 'date|required_with:from',
            'times' => 'integer',
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

        $enrolls = $this->chain->getCourseSegmentByChain($request);
        if($request->filled('class') && getType($request->class) == 'array')
        {
            $requ = new Request([
                'classes' => $request->class,
                'courses' => $request->courses,
            ]);
            $enrolls = $this->chain->getCourseSegmentByManyChain($requ);
        }

        //using in participants api new route { api/user/participants}
        if($my_chain=='participants' || $my_chain=='seen_report'){
            // site/show/as-participant
            $permission = Permission::where('name','site/show/as-participant')->with('roles')->first();
            $roles_id = $permission->roles->pluck('id');
            $enrolls->whereIn('role_id',$roles_id);
        }
        
        //using in chat api new route { api/user/my_chain}
        if($my_chain=='my_chain'){
            if(!$request->user()->can('site/show-all-courses')) //student
                $enrolls=$enrolls->where('user_id',Auth::id());

            $enrolls =  Enroll::whereIn('course_segment',$enrolls->pluck('course_segment'))->where('user_id' ,'!=' , Auth::id());
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
        if($my_chain == 'active' || $my_chain == 'in_active'){

            if($my_chain == 'active' && !$request->user()->can('reports/active_users'))
                return response()->json(['message' => __('messages.error.no_permission'), 'body' => null], 403);

            if($my_chain == 'in_active' && !$request->user()->can('reports/in_active_users'))
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
            if($my_chain == 'in_active')
                $since = 59;

            if($request->filled('since'))
                $since = $request->since;

            $users_lastaction->where('created_at','>=' ,Carbon::now()->subMinutes($since))->where('created_at','<=' ,Carbon::now());

            if($my_chain == 'in_active'){
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
                $file = Excel::store(new InactiveUsers($users_lastaction), 'reports'.$filename.'.xls','public');
                $file = url(Storage::url('reports'.$filename.'.xls'));
                return response()->json(['message' => __('messages.success.link_to_file') , 'body' => $file], 200);

            }

            return response()->json(['message' => $my_chain.' users list ', 'body' => $users_lastaction], 200);
        }

        if($my_chain == 'seen_report' || $my_chain == 'seen_report_chart'){

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

            if($my_chain == 'seen_report_chart'){

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
    public function show($id)
    {
        //
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
