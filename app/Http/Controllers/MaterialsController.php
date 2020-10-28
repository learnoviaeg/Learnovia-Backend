<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Repositories\ChainRepositoryInterface;
use App\Enroll;
use App\Material;
use Illuminate\Support\Facades\Auth;
use App\Lesson;



class MaterialsController extends Controller
{

    public function __construct(ChainRepositoryInterface $chain)
    {
        $this->chain = $chain;
        $this->middleware('auth');
        $this->middleware(['permission:material/get' , 'ParentCheck'],   ['only' => ['index']]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $request->validate([
            'courses'    => 'nullable|array',
            'courses.*'  => 'nullable|integer|exists:courses,id',
            'sort_in' => 'in:asc,desc',
            'item_type' => 'string|in:page,media,file'

        ]);
        $current_course_segments = $this->chain->getCourseSegmentByChain($request);
        if(count($current_course_segments) == 0)
            return response()->json(['message' => 'There is no active course segments', 'body' => []], 200);
        
        $user_course_segments = Enroll::where('user_id',Auth::id())->pluck('course_segment');
        $user_course_segments = array_intersect($current_course_segments->toArray(),$user_course_segments->toArray());
        if($request->user()->can('site/show-all-courses'))
            $user_course_segments = $current_course_segments;

        $lessons = Lesson::whereIn('course_segment_id', $user_course_segments)->pluck('id');
        $material = Material::with(['lesson','course'])->whereIn('lesson_id',$lessons);
        if($request->user()->can('site/course/student'))
            $material->where('visible',1);
        if($request->has('sort_in'))
            $material->orderBy("publish_date",$request->sort_in);
        if($request->has('item_type'))
            $material->where('type',$request->item_type);

        return response()->json(['message' => 'materials list.... ', 'body' => $material->get()], 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        
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
