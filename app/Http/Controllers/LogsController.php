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
            'model' => 'exists:logs,model',
            'start_date' => 'date',
            'end_date' => 'date'
        ]);
        $logs=Log::whereNotNull('id');
        if(isset($request->user))
            $logs->where('user',$request->user);
        if(isset($request->model))
            $logs->where('model',$request->model);
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

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
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
