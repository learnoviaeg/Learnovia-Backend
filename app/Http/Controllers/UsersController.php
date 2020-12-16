<?php

namespace App\Http\Controllers;

use App\Repositories\ChainRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\User;
use App\Enroll;
use App\Paginate;
use App\LAstAction;

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
        // $this->middleware(['permission:course/teachers|course/participants' , 'ParentCheck'],   ['only' => ['index']]);
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
            'search' => 'string'
        ]);


        $enrolls = $this->chain->getCourseSegmentByChain($request);
        if($request->filled('roles')){
           $enrolls->whereIn('role_id',$request->roles);
        }
        
        //using in chat api new route { api/user/my_chain}
        if($my_chain=='my_chain'){
                if(!$request->user()->can('site/show-all-courses')) //student
                    $enrolls=$enrolls->where('user_id',Auth::id());
               $enrolls =  Enroll::whereIn('course_segment',$enrolls->pluck('course_segment'))->where('user_id' ,'!=' , Auth::id());
            }
        $enrolls =  $enrolls->select('user_id')->distinct()->with(['user.attachment','user.roles'])
        // ->whereHas('user', function ($query) use($request){
        //     dd($query->pluck('id'));
        //     if ($request->filled('courses')){
        //         $last_action  = LastAction :: whereIn('user_id',$query->pluck('id'))->whereIn('course_id',$request->courses)->first();
        //         $user->last_action_in_course =null;
        //             if (isset($last_action))
        //                 $user->last_action_in_course = $last_action->date;
        //     }

        // })
        ->get()->pluck('user')->filter()->values();
        foreach($enrolls as $user)
        { 
            if ($request->filled('courses')){
                        $last_action  = LastAction :: where('user_id',$user->id)->whereIn('course_id',$request->courses)->first();
                        $user->last_action_in_course =null;
                            if (isset($last_action))
                                $user->last_action_in_course = $last_action->date;
                    }

        }

            if($request->filled('search'))
        {

            $enrolls = collect($enrolls)->filter(function ($item) use ($request) {
                if(  (($item->arabicname!=null) && str_contains($item->arabicname, $request->search) )|| str_contains(strtolower($item->username), strtolower($request->search))|| str_contains(strtolower($item->fullname), strtolower($request->search) ) ) 
                    return $item; 
            });
        }
        return response()->json(['message' => 'Users List', 'body' =>   $enrolls->paginate(Paginate::GetPaginate($request))], 200);
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
