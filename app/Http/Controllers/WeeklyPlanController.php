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

class WeeklyPlanController extends Controller
{
    public function __construct()
    {
        $this->middleware(['permission:weekly_plan/create'],   ['only' => ['store']]);
        $this->middleware(['permission:weekly_plan/get'],   ['only' => ['index']]);
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
            // 'plans.*.date' => 'required|date|date_format:Y-m-d',
            'view' => 'nullable|in:week', 
        ]); 
        $now = Carbon::now();
        $weekStartDate = $now->startOfWeek(Carbon::SATURDAY)->format('Y-m-d');
        $weekEndDate   = $now->endOfWeek(Carbon::FRIDAY)->format('Y-m-d ');

        if($request->filled('from') && $request->filled('to')){
            $weekStartDate = $request->from;
            $weekEndDate   = $request->to;
        }
        $plans = WeeklyPlan::select('id' , 'added_by' , 'description','date')->WhereBetween('date', [$weekStartDate, $weekEndDate]);

        if($request->filled('course_id'))
            $plans->where('course_id', $request->course_id);


        return response()->json(['message' => null, 'body' => $plans->get()->groupBy('date') ], 200); 
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
        $plan = WeeklyPlan::find($id);
        return response()->json(['message' => null , 'body' => $plan ], 200); 
    }



    public function getWeekNumber(Request $request)
    {
        $request->validate([
            'course_id'   => 'required|exists:courses,id',
        ]); 

        $segment = Segment::select('start_date' , 'end_date')->whereId((Course::select('segment_id')->whereId($request->course_id)->first()->segment_id))->first();
        $start = Carbon::parse($segment->start_date);
        $end = Carbon::parse($segment->end_date);
        $weeks = [];
        $key = 1;
        while ($start->weekOfYear !== $end->weekOfYear) {
            $weeks[] = [
                'from' => $start->startOfWeek()->format('Y-m-d'),
                'to' => $start->endOfWeek()->format('Y-m-d'),
                'week_number' => $key++

            ];
            $start->addWeek(1);
        }
        return response()->json(['message' => 'Weeks number', 'body' => $weeks ], 200); 

    }

}
