<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Timeline;
use Carbon\Carbon;
use App\Enroll;
use App\CourseSegment;
use App\Lesson;
use App\Segment;
use App\AcademicYear;
use Illuminate\Support\Facades\Auth;

class TimelineController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        //validate the request
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'level' => 'exists:levels,id',
            'class' => 'exists:classes,id',
            'course' => 'exists:courses,id',
            'type' => 'in:quiz,assignment',
            'sort_by' => 'in:course,name,due_date|required_with:sort_in',
            'sort_in' => 'in:asc,desc|required_with:sort_by',
            'start_date' => 'date',
            'due_date' => 'date',
        ]);

        $year = AcademicYear::where('current',1)->pluck('id')->first();
        $segment = Segment::where('current',1)->pluck('id');
        $course_segment = Enroll::where('user_id',$request->user_id)->where('year',$year)->whereIn('segment',$segment)->pluck('course_segment');
        $current_course_segments = CourseSegment::whereIn('id',$course_segment)->where('start_date','<=',Carbon::now())
                                                ->where('end_date','>=',Carbon::now())->pluck('id');
        $lessons = Lesson::whereIn('course_segment_id', $current_course_segments)->pluck('id');
        $timeline = Timeline::with(['class','course','level'])->whereIn('lesson_id',$lessons)->where('start_date','<=',Carbon::now())->where('due_date','>=',Carbon::now());

        if($request->has('level'))
            $timeline->where('level_id',$request->level);

        if($request->has('class'))
            $timeline->where('class_id',$request->class);

        if($request->has('course'))
            $timeline->where('course_id',$request->course);

        if($request->has('type'))
            $timeline->where('type',$request->type);

        if($request->has('start_date'))
            $timeline->whereDate('start_date', '=', $request->start_date);

        if($request->has('due_date'))
            $timeline->whereDate('due_date', '=', $request->due_date);

        if($request->has('sort_by') && $request->sort_by != 'course' && $request->has('sort_in'))
            $timeline->orderBy($request->sort_by, $request->sort_in);

        if($request->has('sort_by') && $request->sort_by == 'course' && $request->has('sort_in')){
            $object_sort = $timeline;
            $course_sort =  $object_sort->get()->sortBy('course.name')->values()->pluck('id');
            if($request->sort_in == 'desc')
                $course_sort =  $object_sort->get()->sortByDesc('course.name')->values()->pluck('id');

            $ids_ordered = implode(',', $course_sort->toArray());
            $timeline->orderByRaw("FIELD(id, $ids_ordered)");
        }
        
        return response()->json(['message' => 'Timeline List of items', 'body' => $timeline->get()], 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //validate the request
        $request->validate([
            'item_id' => 'required',
            'name' => 'required|string',
            'start_date' => 'required|date',
            'due_date' => 'required|date',
            'publish_date' => 'date',
            'course_id' => 'required|exists:courses,id',
            'class_id' => 'required|exists:classes,id',
            'lesson_id' => 'required|exists:lessons,id',
            'level_id' => 'required|exists:levels,id',
            'type' => 'required|in:quiz,assignment'
        ]);

        if($request->type == 'assignment')
            $request->validate(['item_id' => 'required|exists:assignments,id',]);
        
        if($request->type == 'quiz')
            $request->validate(['item_id' => 'required|exists:quizzes,id',]);
        

        $new_timeline = Timeline::create([
            'item_id' => $request->item_id,
            'name' => $request->name,
            'start_date' => $request->start_date,
            'due_date' => $request->due_date,
            'publish_date' => isset($request->publish_date)? $request->publish_date : Carbon::now(),
            'course_id' => $request->course_id,
            'class_id' => $request->class_id,
            'lesson_id' => $request->lesson_id,
            'level_id' => $request->level_id,
            'type' => $request->type
        ]);

        return response()->json(['message' => 'timeline created.','body' => $new_timeline], 200);
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
