<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Repositories\ChainRepositoryInterface;
use App\Enroll;
use Illuminate\Support\Facades\Auth;
use App\Paginate;
use App\h5pLesson;
use DB;
use App\Lesson;
use App\Level;
use App\Classes;
use App\Course;
use Carbon\Carbon;

class InterActiveController extends Controller
{
    protected $chain;

    public function __construct(ChainRepositoryInterface $chain)
    {
        $this->chain = $chain;
        $this->middleware(['permission:h5p/lesson/get-all' , 'ParentCheck'],   ['only' => ['index']]);
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request,$count = null)
    {
        $request->validate([
            'year' => 'exists:academic_years,id',
            'type' => 'exists:academic_types,id',
            'level' => 'exists:levels,id',
            'segment' => 'exists:segments,id',
            'courses'    => 'nullable|array',
            'courses.*'  => 'nullable|integer|exists:courses,id',
            'class' => 'nullable|integer|exists:classes,id',
            'lesson' => 'nullable|integer|exists:lessons,id',
            'sort_in' => 'in:asc,desc', 
        ]);

        if($request->user()->can('site/show-all-courses')){//admin
            $course_segments = collect($this->chain->getAllByChainRelation($request));
            $lessons = Lesson::whereIn('course_segment_id',$course_segments->pluck('id'))->pluck('id');
        }
        
        if(!$request->user()->can('site/show-all-courses')){//enrolled users

            $user_course_segments = $this->chain->getCourseSegmentByChain($request);
            $user_course_segments = $user_course_segments->where('user_id',Auth::id());
            $lessons = $user_course_segments->select('course_segment')->distinct()->with('courseSegment.lessons')->get()->pluck('courseSegment.lessons.*.id')->collapse();
        }
      
        if($request->filled('lesson')){
            if (!in_array($request->lesson,$lessons->toArray()))
                return response()->json(['message' => __('messages.error.no_active_for_lesson'), 'body' => []], 400);

            $lessons  = [$request->lesson];
        }

        $sort_in = 'desc';
        if($request->has('sort_in'))
            $sort_in = $request->sort_in;

        $h5p_lessons = h5pLesson::whereIn('lesson_id',$lessons)->orderBy('start_date',$sort_in);

        if($request->user()->can('site/course/student')){
           $h5p_lessons->where('visible',1)->where('publish_date' ,'<=', Carbon::now());
        }

        if($count == 'count'){
            return response()->json(['message' => __('messages.interactive.count'), 'body' => $h5p_lessons->count()], 200);
        }

        $h5p_lessons = $h5p_lessons->get();

        $h5p_contents=[];

        $url= substr($request->url(), 0, strpos($request->url(), "/api"));

        foreach($h5p_lessons as $h5p){                                
            $content = response()->json(DB::table('h5p_contents')->whereId($h5p->content_id)->first());
            $content->original->link = config('app.url').'api/interactive/'.$h5p->content_id.'/?api_token='.Auth::user()->api_token;
            $content->original->item_lesson_id = $h5p->id;
            $content->original->visible = $h5p->visible;
            $content->original->edit_link = $url.'/api/h5p/'.$h5p->content_id.'/edit'.'?editting_done=false';
            if(!$request->user()->can('h5p/lesson/allow-edit') && $h5p->user_id != Auth::id())
                $content->original->edit_link = null;
            
            unset($content->original->parameters,$content->original->filtered,$content->original->metadata);

            $content->original->lesson = Lesson::find($h5p->lesson_id);
            $content->original->class = Classes::find($content->original->lesson->courseSegment->segmentClasses[0]->classLevel[0]->class_id);
            $content->original->level = Level::find($content->original->lesson->courseSegment->segmentClasses[0]->classLevel[0]->yearLevels[0]->level_id);
            $content->original->course = Course::find($content->original->lesson->courseSegment->course_id);
            unset($content->original->lesson->courseSegment);

            $h5p_contents[]=$content->original;
        }
        return response()->json(['message' => __('messages.interactive.list'), 'body' => collect($h5p_contents)->paginate(Paginate::GetPaginate($request))], 200);
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
        return redirect(config('app.url').'/api/h5p/'.$id);
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
