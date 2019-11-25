<?php

namespace Modules\Attendance\Http\Controllers;
use Modules\Attendance\Entities\AttendanceStatus;
use App\Http\Controllers\HelperController;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

class StatusController extends Controller
{
    
    public function Add(Request $request)
    {
        $request->validate([
            'attendance_id' => 'required|exists:attendances,id',
            'letter'        =>'required|string',
            'descrption'    =>'required|string',
            'grade'         =>'required|integer',
            'visible'       =>'boolean',
        ]);
        $Data=[
            'attendance_id' => $request->attendance_id,
            'letter'=> $request->letter,
            'descrption' => $request->descrption,
            'grade' =>$request->grade,
        ];
        if(isset($request->visible)) {
            $Data['visible'] = $request->visible;
        }
        $Status=AttendanceStatus::create($Data);

        return HelperController::api_response_format(200,$Status,'Status Created Successfully');
    }

    public function Update(Request $request)
    {
        $request->validate([
            'id'            => 'required|exists:attendance_statuses,id',
            'attendance_id' => 'exists:attendances,id',
            'letter'        =>'string',
            'descrption'    =>'string',
            'grade'         =>'integer',
            'visible'       =>'boolean',
        ]);
        $status=AttendanceStatus::find($request->id);
        if($request->filled('attendance_id')) {
            $status->attendance_id = $request->attendance_id;
        }
        if($request->filled('letter')) {
            $status->letter = $request->letter;
        }
        if($request->filled('descrption')) {
            $status->descrption = $request->descrption;
        }
        if($request->filled('visible')) {
            $status->visible = $request->visible;
        }
        $status->save();
        return HelperController::api_response_format(200,$status,'Status Updated Successfully');
    }
    public function Delete(Request $request)
    {
        $request->validate([
            'id'            => 'required|exists:attendance_statuses,id',
        ]);
        $status=AttendanceStatus::find($request->id);
        $status->delete();
        return HelperController::api_response_format(201,'Status Deleted Successfully');
    }
  
}
