<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Repositories\ChainRepositoryInterface;
use App\Enroll;
use Illuminate\Support\Facades\Auth;
use App\Lesson;
use Modules\Assigments\Entities\AssignmentLesson;
use Modules\Assigments\Entities\assignment;
use App\Paginate;


class AssignmentController extends Controller
{
    public function __construct(ChainRepositoryInterface $chain)
    {
        $this->chain = $chain;
        $this->middleware('auth');
        $this->middleware(['permission:assignment/get' , 'ParentCheck'],   ['only' => ['index']]);
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $request->validate([
            'year' => 'exists:academic_years,id',
            'type' => 'exists:academic_types,id',
            'level' => 'exists:levels,id',
            'segment' => 'exists:segments,id',
            'courses'    => 'nullable|array',
            'courses.*'  => 'nullable|integer|exists:courses,id',
            'class' => 'nullable|integer|exists:classes,id',
            'lesson' => 'nullable|integer|exists:lessons,id' 
        ]);

        $user_course_segments = $this->chain->getCourseSegmentByChain($request);
        if(!$request->user()->can('site/show-all-courses'))//student
            {
                $user_course_segments = $user_course_segments->where('user_id',Auth::id());
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
        $assignment_lessons = AssignmentLesson::whereIn('lesson_id',$lessons)->get()->sortByDesc('start_date');
        $assignments = collect([]);
        foreach($assignment_lessons as $assignment_lesson){
            $assignment=assignment::where('id',$assignment_lesson->assignment_id)->first();
            $assignment['assignmentlesson'] = $assignment_lesson;
            $assignment['lesson'] = Lesson::find($assignment_lesson->lesson_id);
            $assignments[]=$assignment;

        }
        return response()->json(['message' => 'Assignments List ....', 'body' => $assignments->paginate(Paginate::GetPaginate($request))], 200);

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
