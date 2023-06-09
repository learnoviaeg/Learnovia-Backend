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
        if($my_chain=='participants'){
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
            ]);

            $users_lastaction = Log::whereYear('created_at', $request->report_year)->whereIn('user',$enrolls->pluck('username'))->with('users');
            
            if($request->filled('report_month'))
                $users_lastaction->whereMonth('created_at',$request->report_month);
            
            if($request->filled('report_day'))
                $users_lastaction->whereDay('created_at',$request->report_day);

            if($request->filled('from') && $request->filled('to'))
                $users_lastaction->whereBetween('created_at', [$request->from, $request->to]);
            
            if($my_chain == 'in_active'){
                $users_lastaction->where('created_at','<=' ,Carbon::now()->subHours(1));
            }

            if($request->filled('since'))
                $users_lastaction->where('created_at','>=' ,Carbon::now()->subMinutes($request->since))->where('created_at','<=' ,Carbon::now())->first();

            $users_lastaction = $users_lastaction->select('user')->distinct()->get()->pluck('users');
            
            if($request->filled('never')){
                $last_actions = LastAction::whereNull('course_id')->pluck('user_id');
                $users_lastaction  = $enrolls->whereNotIn('id',$last_actions)->values();
            }

            return response()->json(['message' => $my_chain.' users list ', 'body' => $users_lastaction], 200);
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
