<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\WeeklyPlan;
use App\Segment;
use App\Course;
use Auth;
use DateTime;
use DateInterval;
use DatePeriod;
use App\Repositories\ChainRepositoryInterface;

class WeeklyPlanController extends Controller
{
    public function __construct(ChainRepositoryInterface $chain)
    {
        $this->chain = $chain;
        $this->middleware(['permission:weekly_plan/create'],   ['only' => ['store']]);
        $this->middleware(['permission:weekly_plan/get'],   ['only' => ['index']]);
        $this->middleware(['permission:weekly_plan/update'],   ['only' => ['update , updateCourse']]);
        $this->middleware(['permission:weekly_plan/delete'],   ['only' => ['delete']]);
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $request->validate([
            'course_id'   => 'exists:courses,id',
            'from' => 'date|date_format:Y-m-d',
            'to' => 'date|date_format:Y-m-d',
            'view' => 'nullable|in:week',
            '' 
        ]); 
        $courses = $this->chain->getEnrollsByChain($request)->where('user_id',Auth::id())->get('course')->pluck('course');
        $now = Carbon::now();
       
        $plans = WeeklyPlan::select('id', 'description','date','course_id')->with('course:id,name')->whereIn('course_id', $courses);
        if($request->filled('view') && $request->view == 'week'){
            $weekStartDate = $now->startOfWeek(Carbon::SATURDAY)->format('Y-m-d');
            $weekEndDate   = $now->endOfWeek(Carbon::FRIDAY)->format('Y-m-d ');
        }

        if($request->filled('from') && $request->filled('to')){
            $weekStartDate = $request->from;
            $weekEndDate   = $request->to;
        }

        if(isset($weekStartDate) && isset($weekEndDate))
            $plans->WhereBetween('date', [$weekStartDate, $weekEndDate]);


        if(!$request->filled('view') && $request->view != 'week' && !isset($weekStartDate) && !isset($weekEndDate))
            $plans->where('date', $now->format('Y-m-d'));

        if($request->filled('course_id'))
            $plans->where('course_id', $request->course_id);


        return response()->json(['message' => null, 'body' => $plans->get()->groupBy('course_id') ], 200); 
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'course_id'   => 'required|exists:courses,id',
            'plans' => 'required|array',
            'plans.*.date' => 'required|date|date_format:Y-m-d',
            'plans.*.description' => 'required',
        ]); 

        foreach($request->plans as $plan){
            WeeklyPlan::firstOrcreate([
                'date' => $plan['date'],
                'description' => $plan['description'],
                'course_id' => $request->course_id,],[
                'added_by' => Auth::id(),
            ]); 
        }
        return response()->json(['message' => __('messages.weekly-plan.add'), 'body' => null ], 200); 
        
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
        $plan = WeeklyPlan::find($id);
        $plan->update([
            'date' => isset($request->date) ?? $plan->date,
            'description' => isset($request->description) ?? $plan->description,
        ]);

        return response()->json(['message' => __('messages.weekly-plan.update'), 'body' => null ], 200); 
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $plan = WeeklyPlan::find($id);
        $plan->delete();
        return response()->json(['message' => __('messages.weekly-plan.delete'), 'body' => null ], 200); 

    }

     /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $plan = WeeklyPlan::whereId($id)->with(['user:id,lastname,firstname','course:id,name'])->first();
        return response()->json(['message' => null , 'body' => $plan ], 200); 
    }



    public function getWeekNumber(Request $request)
    {
        $request->validate([
            'course_id'   => 'exists:courses,id',
        ]); 

        $segment = Segment::where("end_date", '>' ,Carbon::now())->where("start_date", '<=' ,Carbon::now())->first();
        
        if($request->filled('course_id'))
            $segment = Segment::select('start_date' , 'end_date')->whereId((Course::select('segment_id')->whereId($request->course_id)->first()->segment_id))->first();

        $start = Carbon::parse($segment->start_date);
        $end = Carbon::parse($segment->end_date);
        $weeks = [];
        $key = 1;
        while ($start->weekOfYear !== $end->weekOfYear) {
            $weeks[] = [
                'from' => $start->startOfWeek(Carbon::SATURDAY)->format('Y-m-d'),
                'to' => $start->endOfWeek(Carbon::FRIDAY)->format('Y-m-d'),
                'week_number' => $key++

            ];
            $start->addWeek(1);
        }
        return response()->json(['message' => 'Weeks number', 'body' => $weeks ], 200); 

    }

    public function updateCourse(Request $request)
    {
        $request->validate([
            'old_course'   => 'required|exists:courses,id',
            'new_course'   => 'required|exists:courses,id',
        ]); 
        $plan = WeeklyPlan::where('course_id',$request->old_course)->update(['course_id'=> $request->new_course]);
        return response()->json(['message' =>  __('messages.weekly-plan.update'), 'body' => null ], 200); 
    }



}
