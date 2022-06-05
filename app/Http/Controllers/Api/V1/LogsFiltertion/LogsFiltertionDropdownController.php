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
        $data =  AuditLog::where('subject_type', '!=', 'Material')->where('subject_type', '!=', 'CourseItem')->where('subject_type', '!=', 'UserCourseItem')->where('subject_type', '!=', 'FileLesson')->where('subject_type', '!=', 'pageLesson')->where('subject_type', '!=', 'MediaLesson')->where('subject_type', '!=', 'QuizLesson')->where('subject_type', '!=', 'AssignmentLesson')->groupBy('subject_type')->pluck('subject_type');
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

    public function seed_logs(Request $request)
    {
        $created_at = $request->created_at;
        for ($i=0; $i < 500000; $i++) 
        { 
            AuditLog:: create([
                        'action' => 'created',
                        'subject_id' => 17,
                        'subject_type' => 'file',
                        'user_id' => 1,
                        // 
                        'properties' => '{"id":17,"attachment_name":"dummy.txt","name":"file to be deleted","description":"6279124039572.txt","size":"5","type":"txt","url":"https:\/\/docs.google.com\/viewer?url=https:\/\/loggapi.learnovia.com\/storage\/files\/6279124039572.txt","url2":"https:\/\/loggapi.learnovia.com\/storage\/files\/6279124039572.txt","deleted_at":"2022-05-09 15:49:38"}',
                       
                        'before' => '{"id":17,"attachment_name":"dummy.txt","name":"file to be deleted","description":"6279124039572.txt","size":"5","type":"txt","url":"https:\/\/docs.google.com\/viewer?url=https:\/\/loggapi.learnovia.com\/storage\/files\/6279124039572.txt","url2":"files\/6279124039572.txt","user_id":1,"created_at":"2022-05-09 15:08:16","updated_at":"2022-05-09 15:27:02","deleted_at":null}',

                        'host' => request()->ip() ?? null,
        // chain
                        'year_id' => [2],
                        'type_id' => [1],
                        'level_id' => [1],
                        'class_id' => [1],
                        'segment_id' => [1], 
                        'course_id' => [5],
                        'role_id' => [1], 
                        'notes' => 'script',
                        'item_name' => 'Dummy',
                        'item_id' => null,
                        'hole_description' => 'Item in module Announcement has been created by name',
                        'created_at' => $created_at,
            ]);
        }
       
        return response()->json([
            'status_code' => 200,
            'data'        => 'done', 
        ], 200);
    }
}
