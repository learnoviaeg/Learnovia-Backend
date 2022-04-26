<?php

namespace App\Http\Controllers\Api\V1\LogsFiltertion;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\User;
use Auth;
use App\AuditLog;
use App\AcademicYear;
use App\AcademicType;
use App\Level;
use App\Classes;
use App\Segment;
use App\Course;

class FetchOneLogApiController extends Controller
{
    public function fetch_logs(AuditLog $log)
    {
        ///return $log->id;

        $record_info['time']         = $log->created_at;
        $record_info['username']     = $log->user->fullname;
        $record_info['module']       = $log->subject_type;
        $record_info['action']       = $log->action;
        $record_info['ipAdress']     = $log->host;

        $chain_details['year']        = AcademicYear::whereIn('id', $log->year_id)->first()->name;
        $chain_details['type']        = AcademicType::whereIn('id', $log->type_id)->first()->name;
        $chain_details['level']       = Level::whereIn('id', $log->level_id)->first()->name;
        $chain_details['class']       = Classes::whereIn('id', $log->class_id)->first()->name;
        $chain_details['segment']     = Segment::whereIn('id', $log->segment_id)->first()->name;
        $chain_details['course']      = Course::whereIn('id', $log->course_id)->first()->name;      

    	$data          = $log->properties;
    	if ($log->action == 'updated') {
    		$before = $log->before;
    		//$diff = array_diff_assoc( array($before), array($data) );
    		//$diff->makeHidden(['created_at', 'updted_at', 'deleted_at']); // hide some attrs
    		return response()->json(['record_info' => $record_info, 'chain_details' => $chain_details, 'data' => $data, 'before' => $before, 'status_code' => 200], 200);
    	}else{
    		return response()->json(['record_info' => $record_info, 'chain_details' => $chain_details, 'data' => $data, 'status_code' => 200], 200);
    	}
    }
}
