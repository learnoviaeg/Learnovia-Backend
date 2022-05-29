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

        $yearID    =  $log->year_id == null ? null : (is_int($log->year_id) ? [$log->year_id] : [$log->year_id]);
        $typeID    =  $log->type_id == null ? null : (is_int($log->type_id) ? [$log->type_id] : [$log->type_id]);
        $levelID   =  $log->level_id == null ? null : (is_int($log->level_id) ? [$log->level_id] : [$log->level_id]);
        $classID   =  $log->class_id == null ? null : (is_int($log->class_id) ? [$log->class_id] : [$log->class_id]);
        $segmentID =  $log->segment_id == null ? null : (is_int($log->segment_id) ? [$log->segment_id] : [$log->segment_id]);
        $courseID  =  $log->course_id == null ? null : (is_int($log->course_id) ? [$log->course_id] : [$log->course_id]);

        $chain_details['year'] = $yearID == null ? null : AcademicYear::whereIn('id', $yearID)->groupBy('name')->pluck('name');
        $chain_details['type'] = $typeID == null ? null : AcademicType::whereIn('id', $typeID)->groupBy('name')->pluck('name');
        $chain_details['level'] = $levelID == null ? null : Level::whereIn('id', $levelID)->groupBy('name')->pluck('name');
        $chain_details['class'] = $classID == null ? null : Classes::whereIn('id', $classID)->groupBy('name')->pluck('name');
        $chain_details['segment'] = $segmentID == null ? null : Segment::whereIn('id', $segmentID)->groupBy('name')->pluck('name');
        $chain_details['course'] = $courseID == null ? null : Course::whereIn('id', $courseID)->groupBy('name')->pluck('name');


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
            /*if ($log->subject_type == 'Enroll') {
              $headlines['item_name']   = $model::withTrashed()->where('id', $log->subject_id)->first()->user->fullname; 
              $headlines['item_id']     = $model::withTrashed()->where('id', $log->subject_id)->first()->user->id;    
            }elseif($log->subject_type == 'page'){
                 $headlines['item_name']   = $model::withTrashed()->where('id', $log->subject_id)->first()->title;
            }elseif($log->subject_type == 'Attendance'){
                 $headlines['item_name']   = \App\Attendance::withTrashed()->where('id', $log->subject_id)->first()->name;
            }elseif($log->subject_type == 'h5pLesson'){
                 $headlines['item_name']   = \App\h5pLesson::withTrashed()->where('id', $log->subject_id)->first()->getNameAttribute();
            }else{
                $headlines['item_name']   = $model::withTrashed()->where('id', $log->subject_id)->first()->name;    
            }*/

            $headlines['item_name']   = $log->item_name; 
            $headlines['item_id']     = $log->item_id;

            // end item name
            // case h5pcontent should be handled but it is gonna be fetched from item_name

            $foreign_keys = [
              'type_id'            => '\App\AcademicType',
              'academic_type_id'   => '\App\AcademicType',
              'year_id'            => '\App\AcademicYear',
              'academic_year_id'   => '\App\AcademicYear',
              'course_id'          => '\App\Course',
              'shared_classes'     => '\App\Classes',
              'classes'            => '\App\Classes',
              'class_id'           => '\App\Classes',
              'question_id'        => '\Modules\QuestionBank\Entities\Questions',
              'quiz_id'            => 'Modules\QuestionBank\Entities\quiz',
            ];

    	if ($log->action == 'updated') {

    		    $before             = $log->before;
            $diff_before        = $before->toArray();
            $diff_after         = $data->toArray();
          
          // case updated subject is lesson
          if ($log->subject_type == 'Lesson') {
                $diff_after['shared_classes']  = implode(',', $log->class_id);
          }
          // case updated subject is lesson

          // case updated subject is course
          if ($log->subject_type == 'Course') {
                $diff_after['classes']  = implode(',', $log->class_id);
          }
          // case updated subject is course

          // case updated subject is Announcement
          if ($log->subject_type == 'Announcement') {
              //$diff_after['created_by']  = $diff_after['created_by']['id']; 
              //$diff_after['topic']       = $diff_after['topic']['id']; 
              //$diff_after['attachment']  = $diff_after['attachment']['id']; 
                unset($diff_after['attachment']);
                unset($diff_after['topic']);
                unset($diff_after['created_by']);
                unset($diff_before['attachment']);
                unset($diff_before['topic']);
                unset($diff_before['created_by']);
          }
          // case updated subject is Announcement

          // case updated subject is Announcement
          if ($log->subject_type == 'Questions') {
              $diff_after['content']  = json_encode($diff_after['content']);
          }
          // case updated subject is Announcement

            $get_diff_before    = array_diff_assoc($diff_before, $diff_after); 
            $get_diff_after     = array_diff_assoc($diff_after, $diff_before);

            // start handle user
              if ($log->subject_type == 'User') {
                unset($get_diff_before['remember_token']);
                unset($get_diff_before['chat_uid']);
                unset($get_diff_before['refresh_chat_token']); 
                unset($get_diff_after['lastaction']); 
                unset($get_diff_after['roles']); 
                unset($get_diff_after['fullname']); 
                  foreach ($get_diff_before as $key => $value) {
                    if($get_diff_before[$key] == null && $get_diff_after[$key] == "null"){
                      unset($get_diff_after[$key]);
                      unset($get_diff_before[$key]);
                    }
                  }
              }
                 // end handle user

               // start handle announcement
                if ($log->subject_type == 'Announcement') {
                    if (!isset($get_diff_before['attachment']) && isset($get_diff_after['attachment']) && $get_diff_after['attachment'] != null) {
                      $get_diff_before['attachment'] = null;
                    }
                }
                 // end handle announcement

                // start handle questions
                if ($log->subject_type == 'Questions') {
                    if (!isset($get_diff_before['count_quizzes']) && isset($get_diff_after['count_quizzes']) && $get_diff_after['count_quizzes'] == null) {
                      unset($get_diff_after['count_quizzes']);
                    }
                }
                 // end handle questions

            // model assignment cases
            if ($log->subject_type == 'assignment') 
            {
                 unset($get_diff_after['updated_at']);
                 if (isset($get_diff_after['content'])) {
                   $get_diff_after['content'] = stripslashes(strip_tags($get_diff_after['content']));
                 }
                 if (isset($get_diff_before['content'])) {
                   $get_diff_before['content'] = stripslashes(strip_tags($get_diff_before['content']));
                 }
              // restricted trace
                if( ($get_diff_before['restricted']  == 0) && ($get_diff_after['restricted']  == false) ){
                  unset($get_diff_before['restricted']);
                  unset($get_diff_after['restricted']);
                }
                // count number trace
                 if (!isset($get_diff_before['url']) && ($get_diff_after['url'] == null) ) {
                   unset($get_diff_after['url']);
                 }
                 if ( !isset($get_diff_before['url2']) && ($get_diff_after['url2'] == null) ) {
                   unset($get_diff_after['url2']);
                 }
                 if ( !isset($get_diff_before['attachment']) && ($get_diff_after['attachment'] == null) ) {
                   unset($get_diff_after['attachment']);
                 }
            }
            // file assignment cases
            if ($log->subject_type == 'file') {
                // user id trace
                 if ( isset($get_diff_before['user_id']) && !isset($get_diff_after['user_id']) ) {
                   unset($get_diff_before['user_id']);
                 }
            }

            // quiz assignment cases
            if ($log->subject_type == 'quiz') {
                // user id trace
                if ( isset($get_diff_before['restricted']) && isset($get_diff_after['restricted']) ) {
                    if( ($get_diff_before['restricted']  == 0) && ($get_diff_after['restricted']  == false) ){
                      unset($get_diff_before['restricted']);
                      unset($get_diff_after['restricted']);
                    }
                }
            }

            // media assignment cases
            if ($log->subject_type == 'media') {
                 // user id trace
                 if ( isset($get_diff_before['user_id']) && !isset($get_diff_after['user_id']) ) {
                   unset($get_diff_before['user_id']);
                 }
                if ( empty($get_diff_before['description']) && isset($get_diff_after['description']) && $get_diff_after['description'] == 'undefined') {
                   unset($get_diff_before['description']);
                   unset($get_diff_after['description']);
                 }
                 //unset($get_diff_before['user_id']);
                 unset($get_diff_after['media_type']);
            }

            foreach ($get_diff_before as $before_key => $before_value) {
              if (array_key_exists($before_key, $foreign_keys)) {
                // $get_diff_before[$before_key] = $foreign_keys[$before_key]::find(intval($before_value))->name;
                if (!is_array($before_value)) {
                  $before_value = [$before_value];
                }
               
              // case lesson fetch classes  before
              if ($log->subject_type == 'Lesson') {
                 $diff_before['shared_classes'] = str_replace('["', '', $diff_before['shared_classes']);
                 $diff_before['shared_classes'] = str_replace('"]', '', $diff_before['shared_classes']);
                 $diff_before['shared_classes'] = str_replace('"', '', $diff_before['shared_classes']);
                 $lesson_old_classes = explode(',', $diff_before['shared_classes']);
                 $before_value = $lesson_old_classes;
              }
                // case lesson fetch classes before
                
                // case course fetch classes before
              if ($log->subject_type == 'Course') {
                 $diff_before['classes'] = str_replace('["', '', $diff_before['classes']);
                 $diff_before['classes'] = str_replace('"]', '', $diff_before['classes']);
                 $diff_before['classes'] = str_replace('"', '', $diff_before['classes']);
                 $course_old_classes     = explode(',', $diff_before['classes']);
                 $before_value           = $course_old_classes;
              }
                 // case course fetch classes before

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
              if ($log->subject_type == 'Lesson') {
                 $lesson_new_classes = explode(',', $diff_after['shared_classes']);
                 $after_value = $lesson_new_classes;
              }
                // case lesson fetch classes before

              // case lesson fetch classes  before
              if ($log->subject_type == 'Course') {
                 $course_new_classes = explode(',', $diff_after['classes']);
                 $after_value = $course_new_classes;
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
            unset($get_diff_after['created_at']);
            unset($get_diff_after['updated_at']);
            unset($get_diff_after['deleted_at']);
      
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
            foreach ($only_one_data as $only_one_data_key => $only_one_data_value) 
            {
                 // start first if
                  if ( array_key_exists($only_one_data_key, $foreign_keys) && ($only_one_data_key == 'question_id' && $only_one_data_key == 'quiz_id') ) {
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
                  } // end first if
                  
                  if (array_key_exists($only_one_data_key, $foreign_keys) && ($only_one_data_key == 'question_id' || $only_one_data_key == 'quiz_id')) 
                  {
                      $new_name = __('ahmed.'.$only_one_data_key.'');

                        if ($only_one_data_key == 'question_id') {
                          $only_one_data[$new_name] = $foreign_keys[$only_one_data_key]::where('id', $only_one_data_value)->groupBy('text')->pluck('text');
                        }
                        if ($only_one_data_key == 'quiz_id') {
                          $only_one_data[$new_name] = $foreign_keys[$only_one_data_key]::where('id', $only_one_data_value)->groupBy('name')->pluck('name');
                        }

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
}
