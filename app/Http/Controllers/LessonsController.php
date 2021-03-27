<?php

namespace App\Http\Controllers;

use App\Repositories\ChainRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

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
            'level' => 'exists:levels,id',
            'class' => 'exists:classes,id',
            'courses'    => 'nullable|array',
            'courses.*'  => 'nullable|integer|exists:courses,id',
        ]);

        if($request->user()->can('site/show-all-courses')){//admin
            $course_segments = collect($this->chain->getAllByChainRelation($request));
            $lessons = Lesson::whereIn('course_segment_id',$course_segments->pluck('id'))->get();

            return response()->json(['message' => __('messages.lesson.list'), 'body' => $lessons], 200);
        }

        if(!$request->user()->can('site/show-all-courses')){ //student or teacher
            $enrolls = $this->chain->getCourseSegmentByChain($request);
            $enrolls->where('user_id',Auth::id());
        }

        $lessons = $enrolls->select('course_segment')->distinct()->with('courseSegment.lessons')->get()->pluck('courseSegment.lessons')->collapse();

        return response()->json(['message' => __('messages.lesson.list'), 'body' => $lessons->filter()->values()], 200);
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
