<?php

namespace App\Http\Controllers;

use App\Repositories\ChainRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Lesson;
use App\SecondaryChain;

class LessonsController extends Controller
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
        $this->middleware(['permission:course/layout' , 'ParentCheck'],   ['only' => ['index']]);
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
            // 'level' => 'exists:levels,id',
            'classes'    => 'nullable|array',
            'classes.*'  => 'nullable|integer|exists:classes,id',
            'courses'    => 'nullable|array',
            'courses.*'  => 'nullable|integer|exists:courses,id',
        ]);
        $enrolls = $this->chain->getEnrollsByManyChain($request)->get()->pluck('id');
        if($request->user()->can('site/show-all-courses')){//admin
            $lessons = SecondaryChain::select('lesson_id')->distinct()->whereIn('enroll_id',$enrolls)->get()->pluck('lesson_id');
        }

        if(!$request->user()->can('site/show-all-courses')){ //student or teacher
            $lessons = SecondaryChain::select('lesson_id')->distinct()->where('user_id',Auth::id())->whereIn('enroll_id',$enrolls)->get()->pluck('lesson_id');
        }
        $result = Lesson::whereIn('id',$lessons)->get();
        return response()->json(['message' => __('messages.lesson.list'), 'body' => $result], 200);
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
