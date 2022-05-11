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
    public function logs_filteration(Request $request)
    {
        $yesterday =  date("Y-m-d h:i:s", strtotime( '-1 days' ));
        $right_now =  date("Y-m-d H:i:s");
        $first_created = AuditLog::first()->created_at;
        $first_created_at = $first_created != null ? $first_created : User::first()->created_at;

    	$user_id      = isset($request->user_id) ? $request->user_id : null;
    	$action       = isset($request->action) ? $request->action : null;
        $model        = isset($request->model) ? $request->model : null;
        $role_id      = isset($request->role_id) ? $request->role_id : null;
        // chain attributes
        $year_id    = isset($request->year_id) ? $request->year_id : null;
        $type_id    = isset($request->type_id) ? $request->type_id : null;
        $level_id   = isset($request->level_id) ? $request->level_id : null;
        $class_id   = isset($request->class_id) ? $request->class_id : null;
        $segment_id = isset($request->segment_id) ? $request->segment_id : null;
        $course_id  = isset($request->course_id) ? $request->course_id : null;
        $pagination = isset($request->paginate) ? $request->paginate : 15;
        // chain attributes

        // time start
		        $start_date = isset($request->start_date) ? $request->start_date  : $first_created_at;
		        $end_date   = isset($request->end_date) ? $request->end_date  : date("Y-m-d H:i:s");
        // time end
		        
        // no time detected , no filter selected case
        if ( $user_id == null && $action == null && $model == null && $role_id == null && $year_id == null && $type_id == null && $level_id == null && $class_id == null && $segment_id == null && $course_id == null ) {
	          $start_date =  date("Y-m-d h:i:s", strtotime( '-1 days' ));
	          $end_date =  date("Y-m-d H:i:s");
        }
        else{
		        $start_date = $start_date;
		        $end_date   = $end_date;
	    } // end one filter at least selected

	    $currentYear = AcademicYear::Get_current()->id;

	    // none
	    if( $year_id == null && $type_id == null && $level_id == null && $class_id == null && $segment_id == null && $course_id == null ){
	    	$chainIDS = $this->yearFilterLast24($currentYear);
	    	$chainIDS = $chainIDS->pluck('id')->toArray();
	    }

	    // year
	    if( $year_id != null && $type_id == null && $level_id == null && $class_id == null && $segment_id == null && $course_id == null ) {
	    	$chainIDS = $this->yearFilter($currentYear, $start_date, $end_date);
	    	$chainIDS = $chainIDS->pluck('id')->toArray();
	    }

	    // type
	    if( $year_id != null && $type_id != null && $level_id == null && $class_id == null && $segment_id == null && $course_id == null ){
	    	$chainIDS = $this->yearFilter($currentYear, $start_date, $end_date);
	    	$chainIDS = $chainIDS->where('type_id', 'like', "%{$type_id}%")->pluck('id')->toArray();
	    }
        
        // level
	    if( $year_id != null && $type_id != null && $level_id != null && $class_id == null && $segment_id == null && $course_id == null ){
	    	$chainIDS = $this->yearFilter($currentYear, $start_date, $end_date);
	    	$chainIDS = $chainIDS->where('type_id', 'like', "%{$type_id}%")
                            ->where('level_id', 'like', "%{$level_id}%")->pluck('id')->toArray();
	    }

	    // class
	    if( $year_id != null && $type_id != null && $level_id != null && $class_id != null && $segment_id == null && $course_id == null ){
	    	$chainIDS = $this->yearFilter($currentYear, $start_date, $end_date);
	    	$chainIDS = $chainIDS->where('type_id', 'like', "%{$type_id}%")
                            ->where('level_id', 'like', "%{$level_id}%")
                            ->where('class_id', 'like', "%{$class_id}%")->pluck('id')->toArray();
	    } 

	    // segment
	    if( $year_id != null && $type_id != null && $level_id != null && $class_id != null && $segment_id != null && $course_id == null ){
	    	$chainIDS = $this->yearFilter($currentYear, $start_date, $end_date);
	    	$chainIDS = $chainIDS->where('type_id', 'like', "%{$type_id}%")
                            ->where('level_id', 'like', "%{$level_id}%")->where('class_id', 'like', "%{$class_id}%")
                            ->where('segment_id', 'like', "%{$segment_id}%")->pluck('id')->toArray();
	    } 

	    // course
	    if( $year_id != null && $type_id != null && $level_id != null && $class_id != null && $segment_id != null && $course_id != null ){
	    	$chainIDS = $this->yearFilter($currentYear, $start_date, $end_date);
	    	$chainIDS = $chainIDS->where('type_id', 'like', "%{$type_id}%")
                            ->where('level_id', 'like', "%{$level_id}%")->where('class_id', 'like', "%{$class_id}%")
                            ->where('segment_id', 'like', "%{$segment_id}%")
                            ->where('course_id', 'like', "%{$course_id}%")->pluck('id')->toArray();
	    }

	    /*$data = DB::table('audit_logs')->select('id', 'action','subject_type', 'subject_id', 'user_id', 'created_at', 
	    	'host')->whereIn('id', $chainIDS);*/

	    $data = AuditLog::select('id', 'action','subject_type', 'subject_id', 'user_id', 'created_at', 
	    	'host')->whereIn('id', $chainIDS);

	    $defaultFilters = array(
	    	'user_id'      => $user_id,
	    	'action'       => $action,
	    	'subject_type' => $model,
	    	'role_id'      => $role_id,
	    );

	    foreach ($defaultFilters as $key => $value) {
	    	if ($value != null) {
	    		$data = $data->where($key, $value);
	    	}
	    }

	    $data = $data->orderBy('created_at', 'DESC');
	    $collection = $data->paginate($pagination);
	    $data = LogsFilterResource::collection($collection);
	    return response()->json(['data' => $collection, 'status_code' => 200], 200);
    }

    // no chain filter selected
    public function yearFilterLast24($currentYear)
    {
    	$notNeeeded = ['userQuizAnswer', 'userQuiz', 'Material', 'CourseItem', 'UserCourseItem', 'FileLesson', 'pageLesson', 'MediaLesson', 'QuizLesson', 'AssignmentLesson', 'AnnouncementsChain', 'userQuiz', 'quiz_questions', 'userQuizAnswer'];
        $yesterday =  date("Y-m-d h:i:s", strtotime( '-1 days' ));
        $right_now =  date("Y-m-d H:i:s");
        $chain_ids = AuditLog::whereNotIn('subject_type', $notNeeeded)
                             ->where('year_id', 'like', "%{$currentYear}%")
                            ->where('created_at', '>=', $yesterday)
                            ->where('created_at', '<=', $right_now);
        return $chain_ids;

        /*if ($request->has('export') && $request->export == 1) {
            //return Excel::download(new AuditlogExport($data), 'auditlogs.xlsx');
            $filename = uniqid();
            $file     = Excel::store(new AuditlogExport($data), 'AuditLog'.$filename.'.xlsx','public');
            $file     = url(Storage::url('AuditLog'.$filename.'.xlsx'));
            return HelperController::api_response_format(201,$file, __('messages.success.link_to_file')); 
        }*/
        return response()->json(['data' => $data, 'status_code' => 200], 200);
    }

    // search with year
    public function yearFilter($currentYear, $start_date, $end_date)
    {
    	$notNeeeded = ['userQuizAnswer', 'userQuiz', 'Material', 'CourseItem', 'UserCourseItem', 'FileLesson', 'pageLesson', 'MediaLesson', 'QuizLesson', 'AssignmentLesson', 'AnnouncementsChain', 'userQuiz', 'quiz_questions', 'userQuizAnswer'];
        $chain_ids = AuditLog::whereNotIn('subject_type', $notNeeeded)->where('year_id', 'like', "%{$year_id}%")
                       ->where('created_at', '>=', $start_date)
                       ->where('created_at', '<=', $end_date);
        return $chain_ids;
    } 
     
}
