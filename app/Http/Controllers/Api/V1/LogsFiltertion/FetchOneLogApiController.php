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
use Spatie\Permission\Models\Role;

class FetchOneLogApiController extends Controller
{
    public function fetch_logs(AuditLog $log)
    {
        $record_info['time']         = $log->created_at;
        $record_info['username']     = $log->user->fullname;
        $record_info['module']       = $log->subject_type;
        $record_info['action']       = $log->action;
        $record_info['ipAdress']     = $log->host;

        $chain_details['year']     = $log->year_id == null ? null : AcademicYear::whereIn('id', $log->year_id)
                                                                      ->groupBy('name')->pluck('name');
        $chain_details['type']     = $log->type_id == null ? null :  AcademicType::whereIn('id', $log->type_id)
                                                                      ->groupBy('name')->pluck('name');
        $chain_details['level']    = $log->level_id == null ? null :  Level::whereIn('id', $log->level_id)
                                                                      ->groupBy('name')->pluck('name');
        $chain_details['class']    = $log->class_id == null ? null :  Classes::whereIn('id', $log->class_id)
                                                                      ->groupBy('name')->pluck('name');
        $chain_details['segment']  = $log->segment_id == null ? null :  Segment::whereIn('id', $log->segment_id)
                                                                        ->groupBy('name')->pluck('name');
        $chain_details['course']   = $log->course_id == null ? null : Course::whereIn('id', $log->course_id)
                                                                      ->groupBy('name')->pluck('name');      

    	$data          = $log->properties;

        $headlines['description'] = 'Item in module ( '. $log->subject_type .' ) has been ( '. $log->action .' ) by ( '. $log->user->fullname. ' )';
        $headlines['username']    = $log->user->fullname;
        $headlines['role']        = Role::whereIn('id', $log->role_id)->pluck('name');
        $headlines['since']       = $log->created_at->diffForHumans();

        // start item name
            $names_array = [
              'QuestionsCategory' => '\Modules\QuestionBank\Entities\QuestionsCategory',
              'assignment'        => '\Modules\Assigments\Entities\assignment',
              'page'              => '\Modules\Page\Entities\page',
              'Questions'         => '\Modules\QuestionBank\Entities\Questions',
              'QuestionsAnswer'   => '\Modules\QuestionBank\Entities\QuestionsAnswer',
              'QuestionsCategory' => '\Modules\QuestionBank\Entities\QuestionsCategory',
              'QuestionsType'     => '\Modules\QuestionBank\Entities\QuestionsType',
              'quiz'              => '\Modules\QuestionBank\Entities\quiz',
              'quiz_questions'    => '\Modules\QuestionBank\Entities\quiz_questions',
              'file'              => '\Modules\UploadFiles\Entities\file',
              'media'             => '\Modules\UploadFiles\Entities\media',
            ];

            if (array_key_exists($log->subject_type, $names_array)) {
              $model = $names_array[$log->subject_type];
            }else{
              $nameSpace = '\\app\\';
              $model     = $nameSpace.$log->subject_type; 
            }
            if ($log->subject_type == 'Enroll') {
              $headlines['item_name']   = $model::withTrashed()->where('id', $log->subject_id)->first()->user->fullname; 
              $headlines['item_id']     = $model::withTrashed()->where('id', $log->subject_id)->first()->user->id;    
            }elseif($log->subject_type == 'page'){
                 $headlines['item_name']   = $model::withTrashed()->where('id', $log->subject_id)->first()->title;
            }else{
                $headlines['item_name']   = $model::withTrashed()->where('id', $log->subject_id)->first()->name;    
            }
            // end item name

    	if ($log->action == 'updated') {
    		$before = $log->before;
    		//$diff = array_diff_assoc( array($before), array($data) );
    		//$diff->makeHidden(['created_at', 'updted_at', 'deleted_at']); // hide some attrs
    		return response()->json(['headlines' => $headlines, 'record_info' => $record_info, 'chain_details' => $chain_details, 'data' => $data, 'before' => $before, 'status_code' => 200], 200);
    	}else{
    		return response()->json(['headlines' => $headlines, 'record_info' => $record_info, 'chain_details' => $chain_details, 'data' => $data, 'status_code' => 200], 200);
    	}
    }
}
