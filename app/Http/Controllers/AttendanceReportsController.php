<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Repositories\ChainRepositoryInterface;
use App\SessionLog;
use Carbon\Carbon;
use App\Classes;

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
            foreach($sessions as $session){
                $report['day']=Carbon::parse($session['start_date'])->format('l');
                $report['date']=Carbon::parse($session['start_date'])->format('Y-m-d H:i');
                if(in_array($report['day'], array_column($reports, 'day')))
                    continue;

                $countSessionDay=$sessions->where('start_date',$session['start_date'])->pluck('id');
                $all=SessionLog::whereIn('session_id',$countSessionDay);
                $clo=clone $all;
                $countStatus =$all->where('status',$request->status)->count();

                // kol l session lly fel youm da
                // kol l session elly feha 8eyab
                foreach($sessions->where('start_date',$session['start_date'])->pluck('class_id') as $class)
                {
                    $class_name=Classes::find($class)->name;
                    if(!in_array($class_name, array_column($rr, 'class_name'))){
                        $rr[$i]['class_name']=$class_name;
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
}
