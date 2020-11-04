<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Repositories\ChainRepositoryInterface;
use App\Enroll;
use Illuminate\Support\Facades\Auth;
use App\h5pLesson;
use DB;
use App\Lesson;
use Carbon\Carbon;

class InterActiveController extends Controller
{
    public function __construct(ChainRepositoryInterface $chain)
    {
        $this->chain = $chain;
        $this->middleware('auth');
        $this->middleware(['permission:h5p/lesson/get-all' , 'ParentCheck'],   ['only' => ['index']]);
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
            'class' => 'nullable|integer|exists:classes,id',
            'lesson' => 'nullable|integer|exists:lessons,id' 

        ]);
        $user_course_segments = $this->chain->getCourseSegmentByChain($request);
        if(!$request->user()->can('site/show-all-courses'))//student
            {
                $user_course_segments = $enrolls->where('user_id',Auth::id());
            }

        $user_course_segments = $user_course_segments->with('courseSegment.lessons')->get();
        $lessons =[];
        foreach ($user_course_segments as $user_course_segment){
            $lessons = array_merge($lessons,$user_course_segment->courseSegment->lessons->pluck('id')->toArray());
        }
        $lessons =  array_values (array_unique($lessons)) ;
        if($request->filled('lesson')){
            if (!in_array($request->lesson,$lessons)){
                return response()->json(['message' => 'No active course segment for this lesson ', 'body' => []], 400);
            }
            $lessons  = [$request->lesson];
        }
        $h5p_lessons = h5pLesson::whereIn('lesson_id',$lessons)->where('visible', '=', 1)->where('publish_date', '<=', Carbon::now())->get()->sortByDesc('start_date');
        $h5p_contents=[];
        $url= substr($request->url(), 0, strpos($request->url(), "/api"));

        foreach($h5p_lessons as $h5p){                                
            $content = response()->json(DB::table('h5p_contents')->whereId($h5p->content_id)->first());
            $content->original->link =  $url.'/api/h5p/'.$h5p->content_id;
            $content->original->item_lesson_id = $h5p->id;
            $content->original->visible = $h5p->visible;
            $content->original->edit_link = $url.'/api/h5p/'.$h5p->content_id.'/edit'.'?editting_done=false';
            if(!$request->user()->can('h5p/lesson/allow-edit') && $h5p->user_id != Auth::id() ){
                $content->original->edit_link = null;
            }
            unset($content->original->parameters,$content->original->filtered,$content->original->metadata);
            $content->original->lesson = Lesson::find($h5p->lesson_id);
            $h5p_contents[]=$content->original;
        }
        return $h5p_contents;
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
