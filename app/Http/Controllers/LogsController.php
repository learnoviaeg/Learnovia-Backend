<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Log;
use App\Course;
use App\Classes;
use App\AcademicYear;
use App\AcademicType;
use App\Segment;
use App\Level;
use App\User;
use Carbon\Carbon;

class LogsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(['permission:user/logs'],['only' => ['index','List_Types']]);
    }
    
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        //username
        $request->validate([
            'user' => 'exists:logs,user',
            'type' => 'exists:logs,model',
            'start_date' => 'date',
            'end_date' => 'date',
            'action' => 'in:updated,deleted,created'
        ]);
        $logs=Log::whereNotNull('id');
        if(isset($request->user))
            $logs->where('user',$request->user);
        if(isset($request->type))
            $logs->where('model',$request->type);
        if(isset($request->action))
            $logs->where('action',$request->action);
        if(isset($request->start_date)){
            $end_date=Carbon::now();
            if(isset($request->end_date))
                $end_date=$request->end_date;
            $logs=Log::where('created_at', '>=', $request->start_date)->where('created_at', '<=', $end_date);
        }
        $AllLogs=array();
        foreach($logs->get() as $log)
        {
            $log->data=unserialize($log->data);
            if($log->model == 'Enroll' && !isset($log->data['before']))
            {
                $log->data->user_id=User::find($log->data->user_id);
                $log->data->course=Course::find($log->data->course);
                $log->data->class=Classes::find($log->data->class);
                $log->data->level=Level::find($log->data->level);
                $log->data->year=AcademicYear::find($log->data->year);
                $log->data->type=AcademicType::find($log->data->type);
                $log->data->segment=Segment::find($log->data->segment);
                unset($log->data->courseSegment);
            }            
            $AllLogs[]=$log;
        }
        return HelperController::api_response_format(200, $AllLogs, 'Logs are');
    }

    public function List_Types(Request $request){
        $types=Log::where('user',$request->user()->username)->pluck('model')->unique();
        return array_values($types->toArray());
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
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
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
