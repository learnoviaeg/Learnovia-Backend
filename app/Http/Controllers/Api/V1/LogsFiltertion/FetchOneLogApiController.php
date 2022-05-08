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
use Route;

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

            $foreign_keys = [
              'type_id'            => '\App\AcademicType',
              'academic_type_id'   => '\App\AcademicType',
              'year_id'            => '\App\AcademicYear',
              'academic_year_id'   => '\App\AcademicYear',
              'course_id'          => '\App\Course',
              'shared_classes'     => '\App\Classes',
            ];

    	if ($log->action == 'updated') {

    		    $before             = $log->before;
            $diff_before        = $before->toArray();
            $diff_after         = $data->toArray();
          
          // case updated subject is lesson
          if ($log->subject_type = 'Lesson') {
                $diff_after['shared_classes']  = implode(',', $log->class_id);
          }
          // case updated subject is lesson

            $get_diff_before    = array_diff_assoc($diff_before, $diff_after); 
            $get_diff_after     = array_diff_assoc($diff_after, $diff_before);

            foreach ($get_diff_before as $before_key => $before_value) {
              if (array_key_exists($before_key, $foreign_keys)) {
                // $get_diff_before[$before_key] = $foreign_keys[$before_key]::find(intval($before_value))->name;
                if (!is_array($before_value)) {
                  $before_value = [$before_value];
                }
               
              // case lesson fetch classes  before
              if ($log->subject_type = 'Lesson') {
                 $diff_before['shared_classes'] = str_replace('["', '', $diff_before['shared_classes']);
                 $diff_before['shared_classes'] = str_replace('"]', '', $diff_before['shared_classes']);
                 $diff_before['shared_classes'] = str_replace('"', '', $diff_before['shared_classes']);
                 $lesson_old_classes = explode(',', $diff_before['shared_classes']);
                 $before_value = $lesson_old_classes;
              }
                // case lesson fetch classes before

                $new_name = __('ahmed.'.$before_key.'');
                $get_diff_before[$new_name] = $foreign_keys[$before_key]::whereIn('id', $before_value)
                                                                      ->groupBy('name')->pluck('name');
                unset($get_diff_before[$before_key]);
              }
            } // end foreach


            foreach ($get_diff_after as $after_key => $after_value) {
              if (array_key_exists($after_key, $foreign_keys)) {
                if (!is_array($after_value)) {
                  $after_value = [$after_value];
                }
                // case lesson fetch classes  before
              if ($log->subject_type = 'Lesson') {
                 $lesson_new_classes = explode(',', $diff_after['shared_classes']);
                 $after_value = $lesson_new_classes;
              }
                // case lesson fetch classes before
                $new_name = __('ahmed.'.$after_key.'');
                $get_diff_after[$new_name] = $foreign_keys[$after_key]::whereIn('id', $after_value)
                                                                      ->groupBy('name')->pluck('name');
                unset($get_diff_after[$after_key]);

              }
            } // end foreach

            unset($get_diff_before['created_at']);
            unset($get_diff_before['updated_at']);
            unset($get_diff_before['deleted_at']);
      
      // response case update
    		return response()->json([
                    'headlines'            => $headlines, 
                    'record_info'          => $record_info, 
                    'chain_details'        => $chain_details, 
                    //'data'                 => $data,      
                    //'before'               => $before, 
                    'get_diff_before'      => $get_diff_before, 
                    'get_diff_after'       => $get_diff_after, 
                    'status_code'          => 200,
                  ], 200);
    	}else{
        // response case create || delete
           $only_one_data         = $data->toArray();
            foreach ($only_one_data as $only_one_data_key => $only_one_data_value) {
              if (array_key_exists($only_one_data_key, $foreign_keys)) {
                if (!is_array($only_one_data_value)) {
                  $only_one_data_value = [$only_one_data_value];
                }
                if ( array_key_exists($only_one_data_key, $foreign_keys) && $only_one_data_key == 'shared_classes' && $log->subject_type == 'Lesson' ) {
                  $only_one_data_value = $log->class_id;
                }
                $new_name = __('ahmed.'.$only_one_data_key.'');
                $only_one_data[$new_name] = $foreign_keys[$only_one_data_key]::whereIn('id', $only_one_data_value)
                                                                      ->groupBy('name')->pluck('name');
                unset($only_one_data[$only_one_data_key]);
              }
            } // end foreach
    		return response()->json([
          'headlines'      => $headlines, 
          'record_info'    => $record_info, 
          'chain_details'  => $chain_details, 
          'data'           => $only_one_data, 
          'status_code'    => 200,
        ], 200);
    	}
    }

  /*  public function array_key_replace($item, $replace_with, array $array){
        $updated_array = [];
        foreach ($array as $key => $value) {
            if (!is_array($value) && $key == $item) {
                $updated_array = array_merge($updated_array, [$replace_with => $value]);
            }else{
              continue;
            }
            $updated_array = array_merge($updated_array, [$key => $value]);
        }
        return $updated_array;
    }*/
}
