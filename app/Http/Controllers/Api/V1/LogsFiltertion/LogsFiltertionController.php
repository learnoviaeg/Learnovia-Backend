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

class LogsFiltertionController extends Controller
{
    public function logs_filteration(Request $request)
    {
    	$start_date = isset($request->start_date) ? $request->start_date : null;
    	$end_date   = isset($request->end_date) ? $request->end_date : null;
    	$user_id    = isset($request->user_id) ? $request->user_id : null;
    	$action     = isset($request->action) ? $request->action : null;
        $model      = isset($request->model) ? $request->model : null;
        // chain attributes
        $year_id    = isset($request->year_id) ? $request->year_id : null;
        $type_id    = isset($request->type_id) ? $request->type_id : null;
        $level_id   = isset($request->level_id) ? $request->level_id : null;
        $class_id   = isset($request->class_id) ? $request->class_id : null;
        $segment_id = isset($request->segment_id) ? $request->segment_id : null;
        $course_id  = isset($request->course_id) ? $request->course_id : null;
        $pagination = isset($request->paginate) ? $request->paginate : 50;
        // chain attributes

         $yesterday =  date("Y-m-d h:i:s", strtotime( '-1 days' ));
         $right_now =  date("Y-m-d H:i:s");

    	// default case 1
        if ($user_id == null && $action == null && $start_date == null && $end_date == null && $model == null) {
    		// fetch logs default time (1 day from now)
            $data = AuditLog::where('created_at', '>=', $yesterday)->where('created_at', '<=', $right_now)
                                                              ->orderBy('created_at', 'DESC')->select('id', 'action','subject_type', 'subject_id', 'user_id', 'created_at', 'host')//->get();
                                                                //->get();
                                                                ->paginate(Paginate::GetPaginate($request));
    	}

        // case 2
        if ($user_id != null && $action == null && $model == null && $start_date == null && $end_date == null) {
            // fetch logs related with this user
            $data = AuditLog::where('user_id', $user_id)->orderBy('created_at', 'DESC')->select('id', 'action','subject_type', 'subject_id', 'user_id', 'created_at', 'host')->get();
        }

        // case 3
    	if ($user_id != null && $action == null && $model == null && $start_date != null && $end_date != null) {
    		// fetch logs related with this user at this period
            $data = AuditLog::where('user_id', $user_id)->where('created_at', '>=', $start_date)
                                                   ->where('created_at', '<=', $end_date)
                                                   ->orderBy('created_at', 'DESC')->select('id', 'action','subject_type', 'subject_id', 'user_id', 'created_at', 'host')->get();
    	}

    	// case 4
        if ($user_id == null && $action != null && $model == null && $start_date == null && $end_date == null) {
    		// fetch logs related with this action
            $data = AuditLog::where('action', $action)->orderBy('created_at', 'DESC')->select('id', 'action','subject_type', 'subject_id', 'user_id', 'created_at', 'host')->get();
    	}

    	// case 5
        if ($user_id == null && $action != null && $model == null && $start_date != null && $end_date != null) {
    		// fetch logs related with this action at this period
            $data = AuditLog::where('action', $action)->where('created_at', '>=', $start_date)
                                                ->where('created_at', '<=', $end_date)
                                                ->orderBy('created_at', 'DESC')->select('id', 'action','subject_type', 'subject_id', 'user_id', 'created_at', 'host')->get();
    	}

        // case 6
        if ($user_id == null && $action == null && $model != null && $start_date == null && $end_date == null) {
            // fetch logs related with this model
            $data = AuditLog::where('subject_type', $model)->orderBy('created_at', 'DESC')
                                               ->select('id', 'action','subject_type', 'subject_id', 'user_id', 'created_at', 'host')->get();
        }

        // case 7
        if ($user_id == null && $action == null && $model != null && $start_date != null && $end_date != null) {
            // fetch logs related with this model at this period
            $data = AuditLog::where('subject_type', $model)->where('created_at', '>=', $start_date)
                                                   ->where('created_at', '<=', $end_date)
                                                   ->orderBy('created_at', 'DESC')->select('id', 'action','subject_type', 'subject_id', 'user_id', 'created_at', 'host')->get();
        }

    	// case 8
        if ($user_id != null && $action != null && $start_date == null && $end_date == null) {
    		// fetch logs related with this user and this action
            $data = AuditLog::where('user_id', $user_id)->where('action', $action)
                                                   ->orderBy('created_at', 'DESC')->select('id', 'action','subject_type', 'subject_id', 'user_id', 'created_at', 'host')->get();
    	}

        // case 9
        if ($user_id != null && $action == null && $model != null && $start_date == null && $end_date == null) {
            // fetch logs related with this user and this model
            $data = AuditLog::where('user_id', $user_id)->where('subject_type', $model)
                                                   ->orderBy('created_at', 'DESC')->select('id', 'action','subject_type', 'subject_id', 'user_id', 'created_at', 'host')->get();
        }

        // case 10
        if ($user_id == null && $action != null && $model != null && $start_date == null && $end_date == null) {
            // fetch logs related with this action and this model
            $data = AuditLog::where('action', $action)->where('subject_type', $model)
                                                   ->orderBy('created_at', 'DESC')->select('id', 'action','subject_type', 'subject_id', 'user_id', 'created_at', 'host')->get();
        }

    	// case 11
        if ($user_id != null && $action != null && $start_date != null && $end_date != null) {
    		//fetch logs related with this user and this action at this period
            $data = AuditLog::where('user_id', $user_id)->where('action', $action)->where('created_at', '>=', $start_date)
                                                   ->where('created_at', '<=', $end_date)
                                                   ->orderBy('created_at', 'DESC')->select('id', 'action','subject_type', 'subject_id', 'user_id', 'created_at', 'host')->get();
    	}

        // case 12
        if ($user_id != null && $action == null && $model != null && $start_date != null && $end_date != null) {
            //fetch logs related with this user and this model at this period
            $data = AuditLog::where('user_id', $user_id)->where('subject_type', $model)->where('created_at', '>=', $start_date)
                                                   ->where('created_at', '<=', $end_date)
                                                   ->orderBy('created_at', 'DESC')->select('id', 'action','subject_type', 'subject_id', 'user_id', 'created_at', 'host')->get();
        }

        // case 13
        if ($user_id == null && $action != null && $model != null && $start_date != null && $end_date != null) {
            //fetch logs related with this action and this model at this period
            $data = AuditLog::where('action', $action)->where('subject_type', $model)->where('created_at', '>=', $start_date)
                                                   ->where('created_at', '<=', $end_date)
                                                   ->orderBy('created_at', 'DESC')->select('id', 'action','subject_type', 'subject_id', 'user_id', 'created_at', 'host')->get();
        }

        // case 14
        if ($user_id != null && $action != null && $model != null && $start_date == null && $end_date == null) {
            //fetch logs related with this user and this model and this action 
            $data = AuditLog::where('user_id', $user_id)->where('action', $action)->where('subject_type', $model)
                                                   ->orderBy('created_at', 'DESC')->select('id', 'action','subject_type', 'subject_id', 'user_id', 'created_at', 'host')->get();
        }

        // case 15
        if ($user_id != null && $action != null && $model != null && $start_date != null && $end_date != null) {
            //fetch logs related with this user and this model and this action at this period
            $data = AuditLog::where('user_id', $user_id)->where('action', $action)->where('subject_type', $model)
                                                   ->where('created_at', '>=', $start_date)
                                                   ->where('created_at', '<=', $end_date)
                                                   ->orderBy('created_at', 'DESC')->select('id', 'action','subject_type', 'subject_id', 'user_id', 'created_at', 'host')->get();
        }

        // case 16
        if ($user_id == null && $action == null && $model == null && $start_date != null && $end_date != null) {
            // fetch logs related with this period
            $data = AuditLog::where('created_at', '>=', $start_date)->where('created_at', '<=', $end_date)
                                            ->orderBy('created_at', 'DESC')
                                            ->select('id', 'action','subject_type', 'subject_id', 'user_id', 'created_at', 'host')->get();
        }

        foreach ($data as $key => $value) {
            $value['description'] = 'Item in module ( '. $value->subject_type .' ) has been ( '. $value->action .' ) by ( '. $value->user->firstname. ' )';
            $value['since']       = $value->created_at->diffForHumans();
            $value->makeHidden('user');
        }

        return $this->chain_filteration($data, $year_id, $type_id, $level_id, $class_id, $segment_id, $course_id, $pagination, $request);
        // return response()->json(['data' => $data, 'status_code' => 200], 200);
    }

    public function chain_filteration($data, $year_id, $type_id, $level_id, $class_id, $segment_id, $course_id, $pagination, $request)
    {
        // filter with none
        if ($year_id == null && $type_id == null && $level_id == null && $class_id == null && $segment_id == null && $course_id == null) {
            return $this->filter_with_none($data, $pagination, $request);
        }
        // case serach with year
        elseif ($year_id != null && $type_id == null && $level_id == null && $class_id == null && $segment_id == null && $course_id == null) {
            return $this->filter_with_year($data, $year_id, $pagination, $request);
        }
        // case serach with type
        elseif ($year_id == null && $type_id != null && $level_id == null && $class_id == null && $segment_id == null && $course_id == null) {
            return $this->filter_with_type($data, $type_id, $pagination, $request);
        }
        // case serach with level
        elseif ($year_id == null && $type_id == null && $level_id != null && $class_id == null && $segment_id == null && $course_id == null) {
            return $this->filter_with_level($data, $level_id, $pagination, $request);
        }
        // case serach with course
        elseif ($year_id == null && $type_id == null && $level_id == null && $class_id == null && $segment_id == null && $course_id != null) {
            return $this->filter_with_course($data, $course_id, $pagination, $request);
        }
        // case serach with class
        elseif ($year_id == null && $type_id == null && $level_id == null && $class_id != null && $segment_id == null && $course_id == null) {
            return $this->filter_with_class($data, $class_id, $pagination, $request);
        }
        // case serach with segment
        elseif ($year_id == null && $type_id == null && $level_id == null && $class_id == null && $segment_id != null && $course_id == null) {
            return $this->filter_with_segment($data, $segment_id, $pagination, $request);
        }
        else{
            return 'another';
        }
    }

    // no chain filter selected
    public function filter_with_none($data, $pagination, $request)
    {
        $data = $data->paginate(Paginate::GetPaginate($request));
        //$data = $data->paginate($pagination);
        return response()->json(['data' => $data, 'status_code' => 200], 200);
    }

    // search with year
    public function filter_with_year($data, $year_id, $pagination, $request)
    {
        //$chain_ids = AuditLog::whereJsonContains('audit_logs.year_id', intval($year_id))->pluck('id')->toArray();
        $chain_ids = AuditLog::where('year_id', 'like', "%{$year_id}%")->pluck('id')->toArray();
        $chain_data = $data->whereIn('id', $chain_ids)->unique()->paginate($pagination);
        return response()->json(['data' => $chain_data, 'status_code' => 200], 200);
    }

     // search with type
    public function filter_with_type($data, $type_id, $pagination, $request)
    {
        //$chain_ids = AuditLog::whereJsonContains('audit_logs.type_id', intval($type_id))->pluck('id')->toArray();
        $chain_ids = AuditLog::where('type_id', 'like', "%{$type_id}%")->pluck('id')->toArray();
        $chain_data = $data->whereIn('id', $chain_ids)->unique()->paginate($pagination);
        return response()->json(['data' => $chain_data, 'status_code' => 200], 200);
    }

     // search with level
    public function filter_with_level($data, $level_id, $pagination, $request)
    {
       // $chain_ids = AuditLog::whereJsonContains('audit_logs.level_id', intval($level_id))->pluck('id')->toArray();
        $chain_ids = AuditLog::where('level_id', 'like', "%{$level_id}%")->pluck('id')->toArray();
        $chain_data = $data->whereIn('id', $chain_ids)->unique()->paginate($pagination);
        return response()->json(['data' => $chain_data, 'status_code' => 200], 200);
    }

    // search with class
    public function filter_with_class($data, $class_id, $pagination, $request)
    {
        // $chain_ids = AuditLog::whereJsonContains('audit_logs.class_id', intval($class_id))->pluck('id')->toArray();
        $chain_ids = AuditLog::where('class_id', 'like', "%{$class_id}%")->pluck('id')->toArray();
        $chain_data = $data->whereIn('id', $chain_ids)->unique()->paginate($pagination);
        return response()->json(['data' => $chain_data, 'status_code' => 200], 200);
    }

     // search with segment
    public function filter_with_segment($data, $segment_id, $pagination, $request)
    {
        // $chain_ids = AuditLog::whereJsonContains('audit_logs.segment_id', intval($segment_id))->pluck('id')->toArray();
        $chain_ids = AuditLog::where('segment_id', 'like', "%{$segment_id}%")->pluck('id')->toArray();
        $chain_data = $data->whereIn('id', $chain_ids)->unique()->paginate($pagination);
        return response()->json(['data' => $chain_data, 'status_code' => 200], 200);
    }

    
    // search with course
    public function filter_with_course($data, $course_id, $pagination, $request)
    {
        // $chain_ids  = AuditLog::whereJsonContains('audit_logs.course_id', intval($course_id))->pluck('id')->toArray();
        $chain_ids  = AuditLog::where('course_id', 'like', "%{$course_id}%")->pluck('id')->toArray();
        $chain_data = $data->whereIn('id', $chain_ids)->unique()->paginate($pagination);
        return response()->json(['data' => $chain_data, 'status_code' => 200], 200);
    }
}
