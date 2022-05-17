<?php

namespace App\Http\Controllers\Api\V1\LogsFiltertion;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Log;
use App\User;
use Auth;
use App\AuditLog;
use App\Http\Controllers\HelperController;
use App\Paginate;
use App\Exports\AuditlogExport;
use Excel;
use Illuminate\Support\Facades\Storage;
use App\AcademicYear;
use DB;
use App\Http\Resources\Api\LogsFiltertion\LogsFilterResource;

class LogsFilterController extends Controller
{
	public function checkTimeFilter($currentYear, $pagination, $notNeeeded, $whereStart, $whereEnd)
		{
		    $ids = AuditLog::where('created_at', '>=', $whereStart)->where('created_at', '<=', $whereEnd)
			               ->whereNotIn('subject_type', $notNeeeded)->where('year_id', 'like', "%{$currentYear}%")
			               ->pluck('id')->toArray();
		    $data = AuditLog::whereIn('id', $ids);
		    return $data;
		}

    public function logs_filteration(Request $request)
    {
        $yesterday =  date("Y-m-d h:i:s", strtotime( '-1 days' ));
        $right_now =  date("Y-m-d H:i:s");

        $first_created = AuditLog::first()->created_at;
        $first_created_at = $first_created != null ? $first_created : User::first()->created_at;

        /*$limit      = isset($request->paginate) ? $request->paginate : 15;
        $skip       = ($request->page -1) * $limit;*/

        $pagination = isset($request->paginate) ? $request->paginate : 15;

        $defaultFilters = array(
	    	'subject_type' => $request->model,
	    	'action'       => $request->action,
	    	'role_id'      => $request->role_id,
	    	'user_id'      => $request->user_id,
	    );

	    $chainFilters = array(
	    	'year_id'       => $request->year_id,
	    	'type_id'       => $request->type_id,
	    	'level_id'      => $request->level_id,
	    	'class_id'      => $request->class_id,
	    	'segment_id'    => $request->segment_id,
	    	'course_id'     => $request->course_id,
	    );

	    $defaultFilters = array_filter($defaultFilters);
	    $chainFilters   = array_filter($chainFilters);

	    $currentYear = AcademicYear::Get_current()->id;

        $notNeeeded = ['userQuizAnswer', 'userQuiz', 'Material', 'CourseItem', 'UserCourseItem', 'FileLesson', 'pageLesson', 'MediaLesson', 'QuizLesson', 'AssignmentLesson', 'AnnouncementsChain', 'quiz_questions'];

        // time start
		        $start_date = isset($request->start_date) ? $request->start_date  : $first_created_at;
		        $end_date   = isset($request->end_date) ? $request->end_date  : date("Y-m-d H:i:s");
        // time end

		 // $first_hit      = 0;
		 $default_filter = 0;
		 $chain_filter   = 0;

		 // start default
		if(count($defaultFilters) > 0){
        	$default_filter = 1;
        }
		 // end default 

        // start chain
		if(count($chainFilters) > 0){
        	$chain_filter = 1;
        }
		 // end chain 

        // no time detected , no filter selected case
        if ( $default_filter == 0 && $chain_filter == 0 ) {
        	  //$first_hit   = 1;
        	  // get last 24 
		    	if ($request->start_date  == null && $request->end_date == null) {
		    		$start_date  = $yesterday;
		            $end_date    = $right_now;
		    	}
		    	$data = $this->checkTimeFilter($currentYear, $pagination, $notNeeeded, $start_date, $end_date);
		    	$collection = $data->paginate($pagination);
		        LogsFilterResource::collection($collection);
		    	return response()->json(['data' => $collection, 'status_code' => 200], 200);
        }

        $common = AuditLog::whereNotIn('subject_type', $notNeeeded)
		                  ->where('created_at', '>=', $start_date)
		                  ->where('created_at', '<=', $end_date);
      
	    ///// start case default filter 1
	    if ($default_filter == 1) {
		    $data = $common->where(function($query) use ($defaultFilters)
					{
					    foreach($defaultFilters as $key => $value) 
					    {
					    	$query->where($key, $value);
					    }
					});

				if ($chain_filter == 1) 
			    {
				    $data = $data->where(function($query2) use ($chainFilters)
							{
							    foreach($chainFilters as $key2 => $value2) 
							    {
							    	$query2->where($key2, 'like', "%{$value2}%");
							    }
							});
				}
		} ////// end case default filter 1
		else{
			if ($chain_filter == 1) 
			{
				    $data = $common->where(function($query2) use ($chainFilters)
							{
							    foreach($chainFilters as $key2 => $value2) 
							    {
							    	$query2->where($key2, 'like', "%{$value2}%");
							    }
							});
			}
		}  // end else

	    //$collection = $data->simplePaginate($pagination);
	    $collection = $data->select('id', 'action','subject_type', 'subject_id', 'user_id', 'created_at', 'host', 'hole_description', 'item_name', 'item_id')->orderBy('id', 'DESC')->paginate($pagination);

	    LogsFilterResource::collection($collection);
	    return response()->json(['data' => $collection, 'status_code' => 200], 200);
    }

    // export section
        /*if ($request->has('export') && $request->export == 1) {
            //return Excel::download(new AuditlogExport($data), 'auditlogs.xlsx');
            $filename = uniqid();
            $file     = Excel::store(new AuditlogExport($data), 'AuditLog'.$filename.'.xlsx','public');
            $file     = url(Storage::url('AuditLog'.$filename.'.xlsx'));
            return HelperController::api_response_format(201,$file, __('messages.success.link_to_file')); 
    
        }*/

}
