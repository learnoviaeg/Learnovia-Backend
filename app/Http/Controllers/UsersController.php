<?php

namespace App\Http\Controllers;

use App\Repositories\ChainRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\User;

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
            'level' => 'exists:levels,id',
            'class' => 'exists:classes,id',
            'courses'    => 'nullable|array',
            'courses.*'  => 'nullable|integer|exists:courses,id',
            'role_id' => 'exists:roles,id'
        ]);

        //only users with course/participants permission can get any users the rest of them cannot
        if(($request->has('role_id') && $request->role_id != 4 && !$request->user()->can('course/participants')) || (!$request->has('role_id') && !$request->user()->can('course/participants')))
            return response()->json(['message' => 'User does not have the right permissions.', 'body' => []], 400);

        $enrolls = $this->chain->getCourseSegmentByChain($request);

        $users = $enrolls->with('user.attachment');

        if($request->has('role_id'))
            $users->where('role_id',$request->role_id);

        $users = $users->get()->pluck('user');

        if(count($users) > 0)
            $users = $users->map->only(['id','firstname', 'lastname', 'username','fullname','arabicname', 'email','attachment'])->unique()->values();

        return response()->json(['message' => 'Users List', 'body' => $users], 200);
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
