<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Repositories\ChainRepositoryInterface;
use App\SessionLog;
use Carbon\Carbon;
use App\Classes;
use Illuminate\Support\Facades\Auth;
use App\AttendanceSession;
use Modules\Attendance\Entities\AttendanceLog;
use App\User;

class AttendanceReportsController extends Controller
{
    public function __construct(ChainRepositoryInterface $chain)
    {
        $this->chain = $chain;
        $this->middleware(['permission:attendance/report-daily|attendance/report-perSession'],   ['only' => ['index']]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $request->validate([
            'attendance_type' => 'required|in:Per Session,Daily',
            'start_date' => 'date',
            'end_date' => 'date|after:start_date',
            'status' => 'required|in:Present,Absent,Excuse,Late'
        ]);

        $ObjSessions=new AttendanceSessionController($this->chain);
        $sessions=$ObjSessions->index($request,1);

        $report=[];
        $reports=[];
        $sessionDay=[];
        if($request->attendance_type == 'Daily' && $request->user()->can('attendance/report-daily'))
        {
            $classes=$sessions->pluck('class_id');
            if(isset($request->classes))
                $classes=$request->classes;
            $i=0;
            $rr=[];
            foreach($sessions->pluck('start_date')->unique() as $session){
                $report['day']=Carbon::parse($session)->format('l');
                $report['date']=Carbon::parse($session)->format('Y-m-d H:i');
                if(in_array($report['day'], array_column($reports, 'day')))
                    continue;

                // kol l session lly fel youm da
                // kol l session elly feha 8eyab
                foreach($sessions->pluck('class_id')->unique() as $class)
                {
                    $countSessionDay=$sessions->where('start_date',$session)->where('class_id',$class)->pluck('id');
                    $all=SessionLog::whereIn('session_id',$countSessionDay);
                    $clo=clone $all;
                    $countStatus =$all->where('status',$request->status)->count();

                    $class_name=Classes::find($class)->name;
                    if(!in_array($class_name, array_column($rr, 'class_name'))){
                        $rr[$i]['class_name']=$class_name;
                        $rr[$i]['precentage']=0;
                        if($clo->count() > 0)
                            $rr[$i]['precentage']=round(($countStatus/$clo->count())*100,2);
                        $report['weekly']=array_values($rr);
                    }
                    $i++;
                }

                array_push($reports,$report);
            }

            return HelperController::api_response_format(200 , $reports , __('messages.session_reports.daily'));
        }

        if($request->attendance_type == 'Per Session' && $request->user()->can('attendance/report-perSession'))
        {
            foreach($sessions as $session){
                $all=SessionLog::where('session_id',$session->id);
                $report['id']=$session->id;
                $report['name']=$session->name;
                $report['start_date']=Carbon::parse($session->start_date)->format('Y-m-d H:i:s');
                $report['from']=Carbon::parse($session->from)->format('H:i');
                $report['to']=Carbon::parse($session->to)->format('H:i');
                $report['precentage']=round(($all->where('status',$request->status)->count()/SessionLog::where('session_id',$session->id)->count())*100,2);
                array_push($reports,$report);
            }
            return HelperController::api_response_format(200 , $reports , __('messages.session_reports.per_session'));
        }
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


    public function user_attendance_report(Request $request){

        $request->validate([
            'type' => 'required|in:Per Session,Daily'
        ]);

        $enrolls = $this->chain->getEnrollsByManyChain($request)->where('user_id', Auth::id())->where('role_id' , 3)->select('course','group');

        $attendance_type_callback = function ($query) use ($request ) {
                $query->where('attendance_type', $request->type);
        };
        $sessions_ids = AttendanceSession::select('id')->whereIn('class_id' , $enrolls->pluck('group'))->whereIn('course_id' , $enrolls->pluck('course'))->where('taken' , 1)
                        ->whereHas('attendance' , $attendance_type_callback)->pluck('id');
        $logs = User::whereId(Auth::id())->select('id')
                ///counting all sessions  
                ->withCount(['attendanceLogs as all_sessions_count'=> function($q) use ($request, $sessions_ids){
                    $q->whereNotNull('status');
                    $q->whereIn('session_id',$sessions_ids);
                }])
                ///counting Absent  
                ->withCount(['attendanceLogs as Absent'=> function($q) use ($request, $sessions_ids){
                    $q->where('status','Absent');
                    $q->whereIn('session_id',$sessions_ids);
                }])
                ///counting Late
                ->withCount(['attendanceLogs as Late'=> function($q) use ($request, $sessions_ids){
                    $q->where('status','Late');
                    $q->whereIn('session_id',$sessions_ids);
                }])
                ///counting Present
                ->withCount(['attendanceLogs as Present'=> function($q) use ($request, $sessions_ids){
                    $q->where('status','Present');
                    $q->whereIn('session_id',$sessions_ids);
                }])
                ///counting Excuse
                ->withCount(['attendanceLogs as Excuse'=> function($q) use ($request, $sessions_ids){
                    $q->where('status','Excuse');
                    $q->whereIn('session_id',$sessions_ids);
                }])->first();

        if($logs->all_sessions_count > 0){
            $logs->Present =  ($logs->Present / $logs->all_sessions_count)*100;
            $logs->Late =  ($logs->Late / $logs->all_sessions_count)*100;
            $logs->Absent =  ($logs->Absent / $logs->all_sessions_count)*100;
            $logs->Excuse =  ($logs->Excuse / $logs->all_sessions_count)*100;
        }
      
        return response()->json(['message' => null , 'body' => $logs], 200);
    }

    public function user_attendance_report_details(Request $request){
        $request->validate([
            'type' => 'required|in:Per Session,Daily',
            'start_date' => 'date',
            'end_date' => 'date', // filter all session that started before this end_date
            'from' => 'date_format:H:i',
            'to' => 'date_format:H:i|after:from',
            'current' => 'in:month,week,day', //current
            'filter' => 'integer|between:1,12',
            'search' => 'string'
        ]);

        $enrolls = $this->chain->getEnrollsByManyChain($request)->where('user_id', Auth::id())->where('role_id' , 3)->select('course','group');

        $attendance_type_callback = function ($query) use ($request ) {
                $query->where('attendance_type', $request->type);
        };
        $attendanceSession = AttendanceSession::whereIn('class_id' , $enrolls->pluck('group'))->whereIn('course_id' , $enrolls->pluck('course'))
                            ->whereHas('attendance' , $attendance_type_callback)
                            ->with(['attendance' => function($query){
                                $query->select('id','name');
                            } ])
                            ->with(['class' => function($query){
                                $query->select('id','name');
                            } ])
                            ->with(['course' => function($query){
                                $query->select('id','name');
                            } ])
                            ->with(['session_logs' => function ($query) use ($request ) {
                                $query->where('user_id', Auth::id());
                                $query->select('session_id','status');
                            }]);   

        if(isset($request->start_date))
        $attendanceSession->where('start_date','>=', $request->start_date);

        if(isset($request->end_date))
            $attendanceSession->where('start_date','<=', $request->end_date);

        if(isset($request->filter))
            $attendanceSession->whereMonth('start_date', $request->filter);

        if(isset($request->current))
        {
            if($request->current == 'day')
                $attendanceSession->whereDay('start_date', Carbon::now()->format('j'))->whereMonth('start_date',Carbon::now()->format('m'));

            if($request->current == 'week'){
                // from saterday to friday
                if(Carbon::now()->format('l') == 'Saturday')
                    $attendanceSession->where('start_date', '>=', Carbon::now()->addDay(7))
                        ->where('start_date', '<=', Carbon::now()->addDay(7));

                else
                    for($i=1;$i<=7;$i++)
                    {
                        $day=Carbon::now()->subDay($i)->format('l');
                        if($day == 'Saturday')
                            $attendanceSession->where('start_date', '>=', Carbon::now()->subDay($i))
                                ->where('start_date', '<=', Carbon::now()->subDay($i)->addDay(7));
                    }
            }

            if($request->current == 'month')
                $attendanceSession->whereMonth('start_date', Carbon::now()->format('m'));
        }

        if(isset($request->from))
            $attendanceSession->where('from','>=', $request->from);

        if(isset($request->to))
            $attendanceSession->where('to','<', $request->to);
                    
        return response()->json(['message' => null , 'body' => $attendanceSession->get() ], 200);

    }
}
