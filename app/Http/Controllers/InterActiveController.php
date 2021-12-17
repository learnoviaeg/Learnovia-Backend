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
use App\SecondaryChain;

class InterActiveController extends Controller
{
    protected $chain;

    public function __construct(ChainRepositoryInterface $chain)
    {
        $this->chain = $chain;
        $this->middleware(['permission:h5p/lesson/get-all'],   ['only' => ['index']]);
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
            $enrolls = $this->chain->getEnrollsByChain($request);
            $lessons = $enrolls->with('SecondaryChain')->where('user_id',Auth::id())->get()->pluck('SecondaryChain.*.lesson_id')->collapse()->unique(); 
        }

        if(!$request->user()->can('site/show-all-courses')){//enrolled users
           $enrolls = $this->chain->getEnrollsByChain($request)->where('user_id',Auth::id())->get()->pluck('id');
           $lessons = SecondaryChain::whereIn('enroll_id', $enrolls)->where('user_id',Auth::id())->get()->pluck('lesson_id')->unique();
        }
      
        if($request->filled('lesson')){
            if (!in_array($request->lesson,$lessons->toArray()))
                return response()->json(['message' => __('messages.error.no_active_for_lesson'), 'body' => []], 400);

            $lessons  = [$request->lesson];
        }

        // $sort_in = 'desc';
        // if($request->has('sort_in'))
        //     $sort_in = $request->sort_in;

        $h5p_lessons = h5pLesson::whereIn('lesson_id',$lessons);

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
            $content->original->publish_date = $h5p->publish_date;
            $content->original->edit_link = $url.'/api/h5p/'.$h5p->content_id.'/edit'.'?editting_done=false';
            if(!$request->user()->can('h5p/lesson/allow-edit') && $h5p->user_id != Auth::id())
                $content->original->edit_link = null;
            
            unset($content->original->parameters,$content->original->filtered,$content->original->metadata);

            $content->original->lesson = Lesson::find($h5p->lesson_id);
            $sec_chain = SecondaryChain::where('lesson_id',$h5p->lesson_id)->get();
            $classess = Classes::whereIn('id', $sec_chain->pluck('group_id'))->get();
            $content->original->class = $classess;
            $content->original->level = Level::where('id',Course::find($content->original->lesson->course_id)->get()->pluck('level_id'))->get();
            $content->original->course = Course::find($content->original->lesson->course_id);
            // unset($content->original->lesson->courseSegment);

            $h5p_contents[]=$content->original;
        }
        return response()->json(['message' => __('messages.interactive.list'), 'body' => collect($h5p_contents)->sortByDesc('created_at')->paginate(Paginate::GetPaginate($request))], 200);
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
    public function show(Request $request ,$id)
    {
        return redirect(config('app.url').'api/h5p/'.$id.'&api_token='.$request->api_token);
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
