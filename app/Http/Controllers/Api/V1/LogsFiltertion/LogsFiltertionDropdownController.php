<?php

namespace App\Http\Controllers\Api\V1\LogsFiltertion;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\AuditLog;
use App\Http\Controllers\HelperController;
use App\User;
use App\AcademicType;
use App\AcademicYear;
use App\Level;
use App\Classes;
use App\Segment;
use App\Course;

class LogsFiltertionDropdownController extends Controller
{    
    public function logs_models_dropdown()
    {
        $data =  AuditLog::groupBy('subject_type')->pluck('subject_type');
        return response()->json([
        	'data' => $data, 
        	'status_code' => 200,
        ], 200);
    }

    public function logs_actions_dropdown()
    {
       $data =  AuditLog::groupBy('action')->pluck('action');
        return response()->json([
        	'data' => $data, 
        	'status_code' => 200,
        ], 200);
    }


    public function logs_users_dropdown()
    {
       $ids    =  AuditLog::groupBy('user_id')->pluck('user_id')->toArray();
       $select = ['id', 'firstname', 'lastname', 'username', 'arabicname'];
       $data   = User::whereIn('id', $ids)->select($select)->get();
        return response()->json([
            'status_code' => 200,
            'data'        => $data, 
        ], 200);
    }

    public function logs_years_dropdown()
    {
      $ids    =  AuditLog::whereNotNull('year_id')->groupBy('year_id')->pluck('year_id');
       $data   = AcademicYear::whereIn('id', $ids)->select('id', 'name', 'current')->get();
        return response()->json([
            'status_code' => 200,
            'data'        => $data, 
        ], 200);
    }

    public function logs_types_dropdown()
    {
      $ids    =  AuditLog::whereNotNull('type_id')->groupBy('type_id')->pluck('type_id');
       $data   = AcademicType::whereIn('id', $ids)->select('id', 'name')->get();
        return response()->json([
            'status_code' => 200,
            'data'        => $data, 
        ], 200);
    }

    public function logs_levels_dropdown()
    {
      $ids    =  AuditLog::whereNotNull('level_id')->groupBy('level_id')->pluck('level_id');
       $data   = Level::whereIn('id', $ids)->select('id', 'name')->get();
        return response()->json([
            'status_code' => 200,
            'data'        => $data, 
        ], 200);
    }

    public function logs_classes_dropdown()
    {
      $ids     =  AuditLog::whereNotNull('class_id')->groupBy('class_id')->pluck('class_id');
       $data   = Classes::whereIn('id', $ids)->select('id', 'name')->get();
        return response()->json([
            'status_code' => 200,
            'data'        => $data, 
        ], 200);
    }

    public function logs_segments_dropdown()
    {
      $ids    =  AuditLog::whereNotNull('segment_id')->groupBy('segment_id')->pluck('segment_id');
       $data   = Segment::whereIn('id', $ids)->select('id', 'name')->get();
        return response()->json([
            'status_code' => 200,
            'data'        => $data, 
        ], 200);
    }

    public function logs_courses_dropdown()
    {
      $ids    =  AuditLog::whereNotNull('course_id')->groupBy('course_id')->pluck('course_id');
       $data   = Course::whereIn('id', $ids)->select('id', 'name')->get();
        return response()->json([
            'status_code' => 200,
            'data'        => $data, 
        ], 200);
    }
}
