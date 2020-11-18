<?php

namespace App\Http\Controllers;

use App\Repositories\ChainRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\User;
use App\Enroll;
use App\Paginate;

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
    public function index(Request $request)
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

        //only users with course/participants permission can get any users the rest of them cannot
        // if(($request->has('role_id') && $request->role_id != 4 && !$request->user()->can('course/participants')) || (!$request->has('role_id') && !$request->user()->can('course/participants')))
        //     return response()->json(['message' => 'User does not have the right permissions.', 'body' => []], 400);

        $enrolls = $this->chain->getCourseSegmentByChain($request);
        if($request->filled('roles')){
            $users = $enrolls->whereIn('role_id',$request->roles);
        }
        $mychains=Enroll::where('user_id',Auth::id())->pluck('course_segment');
        if($request->user()->can('site/show-all-courses'))
            $mychains=$enrolls->pluck('course_segment');
        $coursesegments = array_intersect($enrolls->pluck('course_segment')->toArray(),$mychains->toArray());
        // $users = $enrolls->pluck('user_id');
        // $users = user:: whereIn('id',$users)->with('attachment');
        $enro = Enroll::whereIn('course_segment',$coursesegments)->pluck('user_id');
        $users = user:: whereIn('id',$enro)->with('attachment');

        if($request->filled('search'))
        {
            $users  =$users->where('id','!=',Auth::id())
                                ->where( function($q)use($request){
                                            $q->orWhere('arabicname', 'LIKE' ,"%$request->search%" )
                                                    ->orWhere('username', 'LIKE' ,"%$request->search%" )
                                                    ->orWhereRaw("concat(firstname, ' ', lastname) like '%$request->search%' ");
                                            });

        }

        return response()->json(['message' => 'Users List', 'body' =>  $users->get()->paginate(Paginate::GetPaginate($request))], 200);
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
