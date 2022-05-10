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

class LogsFiltertionController extends Controller
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
        $pagination = isset($request->paginate) ? $request->paginate : 50;
        // chain attributes

        if ( $request->start_date == null && $request->end_date == null && $user_id == null && $action == null && $model == null && $role_id == null && $year_id == null && $type_id == null && $level_id == null && $class_id == null && $segment_id == null && $course_id == null ) {
          $yesterday =  date("Y-m-d h:i:s", strtotime( '-1 days' ));
          $right_now =  date("Y-m-d H:i:s");
          $start_date = $yesterday;
          $end_date   = $right_now;
        }
        
        if ( $request->start_date == null && $request->end_date == null && $user_id != null && $action != null && $model != null && $role_id != null && $year_id != null && $type_id != null && $level_id != null && $class_id != null && $segment_id != null && $course_id != null ) {
          $start_date = $first_created_at;
          $end_date   = date("Y-m-d H:i:s");
        }

        if ($request->start_date != null && $request->end_date != null) {
          $start_date = $request->start_date;
          $end_date   = $request->end_date;
        }
        if ($request->start_date == null && $request->end_date != null) {
          $start_date = $first_created_at;
          $end_date   = $request->end_date;
        }
        if ($request->start_date != null && $request->end_date == null) {
          $start_date = $request->start_date;
          $end_date   = date("Y-m-d H:i:s");
        }

    	// default case 1
        if ($user_id == null && $action == null && $model == null && $role_id == null) {
            // fetch logs default time (1 day from now)
            $notNeeeded = ['userQuizAnswer', 'userQuiz', 'Material', 'CourseItem', 'UserCourseItem', 'FileLesson', 'pageLesson', 'MediaLesson', 'QuizLesson', 'AssignmentLesson'];
            $data = AuditLog::whereNotIn('subject_type', $notNeeeded)
                                      //->orderBy('created_at', 'DESC')
                                      ->where('created_at', '>=', $start_date)
                                      ->where('created_at', '<=', $end_date)
                                      ->select('id', 'action','subject_type', 'subject_id', 'user_id', 'created_at', 'host');
        }

        // case 2
        if ($user_id != null && $action == null && $model == null && $role_id == null) {
            // fetch logs related with this user
          $notNeeeded = ['userQuizAnswer', 'userQuiz', 'Material', 'CourseItem', 'UserCourseItem', 'FileLesson', 'pageLesson', 'MediaLesson', 'QuizLesson', 'AssignmentLesson'];
            $data = AuditLog::whereNotIn('subject_type', $notNeeeded)
                                      //->orderBy('created_at', 'DESC')
                                      ->where('created_at', '>=', $start_date)
                                      ->where('created_at', '<=', $end_date)
                                      ->select('id', 'action','subject_type', 'subject_id', 'user_id', 'created_at', 'host');
            //->get();
        }

    	// case 4
        if ($user_id == null && $action != null && $model == null && $role_id == null) {
    		// fetch logs related with this action
          $notNeeeded = ['userQuizAnswer', 'userQuiz', 'Material', 'CourseItem', 'UserCourseItem', 'FileLesson', 'pageLesson', 'MediaLesson', 'QuizLesson', 'AssignmentLesson'];
            $data = AuditLog::whereNotIn('subject_type', $notNeeeded)
            //->orderBy('created_at', 'DESC')
                                      ->where('created_at', '>=', $start_date)
                                      ->where('created_at', '<=', $end_date)
                                      ->select('id', 'action','subject_type', 'subject_id', 'user_id', 'created_at', 'host');
            //->get();
    	}

        // case 6
        if ($user_id == null && $action == null && $model != null && $role_id == null) {
            // fetch logs related with this model
          $notNeeeded = ['userQuizAnswer', 'userQuiz', 'Material', 'CourseItem', 'UserCourseItem', 'FileLesson', 'pageLesson', 'MediaLesson', 'QuizLesson', 'AssignmentLesson'];
            $data = AuditLog::whereNotIn('subject_type', $notNeeeded)
            //->orderBy('created_at', 'DESC')
            ->where('created_at', '>=', $start_date)
                                      ->where('created_at', '<=', $end_date)
                                      ->select('id', 'action','subject_type', 'subject_id', 'user_id', 'created_at', 'host');
                                               //->get();
                                              // ->paginate(Paginate::GetPaginate($request));
        }

    	// case 8
        if ($user_id != null && $action != null && $model == null && $role_id == null) {
    		// fetch logs related with this user and this action
          $notNeeeded = ['userQuizAnswer', 'userQuiz', 'Material', 'CourseItem', 'UserCourseItem', 'FileLesson', 'pageLesson', 'MediaLesson', 'QuizLesson', 'AssignmentLesson'];
            $data = AuditLog::whereNotIn('subject_type', $notNeeeded)
            //->orderBy('created_at', 'DESC')
                                                   ->where('created_at', '>=', $start_date)
                                      ->where('created_at', '<=', $end_date)
                                      ->select('id', 'action','subject_type', 'subject_id', 'user_id', 'created_at', 'host');
                                                   //->get();
    	}

        // case 9
        if ($user_id != null && $action == null && $model != null && $role_id == null) {
            // fetch logs related with this user and this model
          $notNeeeded = ['userQuizAnswer', 'userQuiz', 'Material', 'CourseItem', 'UserCourseItem', 'FileLesson', 'pageLesson', 'MediaLesson', 'QuizLesson', 'AssignmentLesson'];
            $data = AuditLog::whereNotIn('subject_type', $notNeeeded)
            //->orderBy('created_at', 'DESC')
                                                   ->where('created_at', '>=', $start_date)
                                      ->where('created_at', '<=', $end_date)
                                      ->select('id', 'action','subject_type', 'subject_id', 'user_id', 'created_at', 'host');
                                                   //->get();
        }
        
        // search with role only
        if ($user_id == null && $action == null && $model == null && $role_id != null) {
            // fetch logs related with this user and this role
          $notNeeeded = ['userQuizAnswer', 'userQuiz', 'Material', 'CourseItem', 'UserCourseItem', 'FileLesson', 'pageLesson', 'MediaLesson', 'QuizLesson', 'AssignmentLesson'];
          
            $data = AuditLog::whereNotIn('subject_type', $notNeeeded)
            //->orderBy('created_at', 'DESC')
                                                   ->where('created_at', '>=', $start_date)
                                      ->where('created_at', '<=', $end_date)
                                      ->select('id', 'action','subject_type', 'subject_id', 'user_id', 'created_at', 'host');
                                                   //->get();
        }

        // search with role and user
        if ($user_id != null && $action == null && $model == null && $role_id != null) {
            // fetch logs related with this user and this role
          $notNeeeded = ['userQuizAnswer', 'userQuiz', 'Material', 'CourseItem', 'UserCourseItem', 'FileLesson', 'pageLesson', 'MediaLesson', 'QuizLesson', 'AssignmentLesson'];
            $data = AuditLog::whereNotIn('subject_type', $notNeeeded)
            //->orderBy('created_at', 'DESC')
                                                   ->where('created_at', '>=', $start_date)
                                      ->where('created_at', '<=', $end_date)
                                      ->select('id', 'action','subject_type', 'subject_id', 'user_id', 'created_at', 'host');
                                                   //->get();
        }

        // search with role and model
        if ($user_id == null && $action == null && $model != null && $role_id != null) {
            // fetch logs related with this user and this role
          $notNeeeded = ['userQuizAnswer', 'userQuiz', 'Material', 'CourseItem', 'UserCourseItem', 'FileLesson', 'pageLesson', 'MediaLesson', 'QuizLesson', 'AssignmentLesson'];
            $data = AuditLog::whereNotIn('subject_type', $notNeeeded)
            //->orderBy('created_at', 'DESC')
                                                   ->where('created_at', '>=', $start_date)
                                      ->where('created_at', '<=', $end_date)
                                      ->select('id', 'action','subject_type', 'subject_id', 'user_id', 'created_at', 'host');
                                                   //->get();
        }

        // search with role and action
        if ($user_id == null && $action != null && $model == null && $role_id != null) {
            // fetch logs related with this user and this role
          $notNeeeded = ['userQuizAnswer', 'userQuiz', 'Material', 'CourseItem', 'UserCourseItem', 'FileLesson', 'pageLesson', 'MediaLesson', 'QuizLesson', 'AssignmentLesson'];
            $data = AuditLog::whereNotIn('subject_type', $notNeeeded)
            //->orderBy('created_at', 'DESC')
                                                   ->where('created_at', '>=', $start_date)
                                      ->where('created_at', '<=', $end_date)
                                      ->select('id', 'action','subject_type', 'subject_id', 'user_id', 'created_at', 'host');
                                                   //->get();
        }

        // search with role and action and model
        if ($user_id == null && $action != null && $model != null && $role_id != null) {
            // fetch logs related with this user and this role
          $notNeeeded = ['userQuizAnswer', 'userQuiz', 'Material', 'CourseItem', 'UserCourseItem', 'FileLesson', 'pageLesson', 'MediaLesson', 'QuizLesson', 'AssignmentLesson'];
            $data = AuditLog::whereNotIn('subject_type', $notNeeeded)
            //->orderBy('created_at', 'DESC')
                                                   ->where('created_at', '>=', $start_date)
                                      ->where('created_at', '<=', $end_date)
                                      ->select('id', 'action','subject_type', 'subject_id', 'user_id', 'created_at', 'host');
                                                   //->get();
        }

        // search with role and action and user
        if ($user_id != null && $action != null && $model == null && $role_id != null) {
            // fetch logs related with this user and this role
          $notNeeeded = ['userQuizAnswer', 'userQuiz', 'Material', 'CourseItem', 'UserCourseItem', 'FileLesson', 'pageLesson', 'MediaLesson', 'QuizLesson', 'AssignmentLesson'];
            $data = AuditLog::whereNotIn('subject_type', $notNeeeded)
            //->orderBy('created_at', 'DESC')
                                      ->where('created_at', '>=', $start_date)
                                      ->where('created_at', '<=', $end_date)
                                      ->select('id', 'action','subject_type', 'subject_id', 'user_id', 'created_at', 'host');
                                                   //->get();
        }

        // search with role and model and user
        if ($user_id != null && $action == null && $model != null && $role_id != null) {
            // fetch logs related with this user and this role
          $notNeeeded = ['userQuizAnswer', 'userQuiz', 'Material', 'CourseItem', 'UserCourseItem', 'FileLesson', 'pageLesson', 'MediaLesson', 'QuizLesson', 'AssignmentLesson'];
            $data = AuditLog::whereNotIn('subject_type', $notNeeeded)
            //->orderBy('created_at', 'DESC')
                                      ->where('created_at', '>=', $start_date)
                                      ->where('created_at', '<=', $end_date)
                                      ->select('id', 'action','subject_type', 'subject_id', 'user_id', 'created_at', 'host');
                                                   //->get();
        }


        // search with role and model and user and action
        if ($user_id != null && $action != null && $model != null && $role_id != null) {
            // fetch logs related with this user and this role
          $notNeeeded = ['userQuizAnswer', 'userQuiz', 'Material', 'CourseItem', 'UserCourseItem', 'FileLesson', 'pageLesson', 'MediaLesson', 'QuizLesson', 'AssignmentLesson'];
            $data = AuditLog::whereNotIn('subject_type', $notNeeeded)
            //->orderBy('created_at', 'DESC')
                                      ->where('created_at', '>=', $start_date)
                                      ->where('created_at', '<=', $end_date)
                                      ->select('id', 'action','subject_type', 'subject_id', 'user_id', 'created_at', 'host');
                                                   //->get();
        }

        // case 10
        if ($user_id == null && $action != null && $model != null && $role_id == null) {
            // fetch logs related with this action and this model
          $notNeeeded = ['userQuizAnswer', 'userQuiz', 'Material', 'CourseItem', 'UserCourseItem', 'FileLesson', 'pageLesson', 'MediaLesson', 'QuizLesson', 'AssignmentLesson'];
            $data = AuditLog::whereNotIn('subject_type', $notNeeeded)
            //->orderBy('created_at', 'DESC')
                                      ->where('created_at', '>=', $start_date)
                                      ->where('created_at', '<=', $end_date)
                                      ->select('id', 'action','subject_type', 'subject_id', 'user_id', 'created_at', 'host');
                                                   //->get();
        }

        // case 14
        if ($user_id != null && $action != null && $model != null && $role_id == null) {
            //fetch logs related with this user and this model and this action 
          $notNeeeded = ['userQuizAnswer', 'userQuiz', 'Material', 'CourseItem', 'UserCourseItem', 'FileLesson', 'pageLesson', 'MediaLesson', 'QuizLesson', 'AssignmentLesson'];
            $data = AuditLog::whereNotIn('subject_type', $notNeeeded)
            //->orderBy('created_at', 'DESC')
                                      ->where('created_at', '>=', $start_date)
                                      ->where('created_at', '<=', $end_date)
                                      ->select('id', 'action','subject_type', 'subject_id', 'user_id', 'created_at', 'host');
                                                   //->get();
        }

        return $this->chain_filteration($data, $year_id, $type_id, $level_id, $class_id, $segment_id, $course_id, $pagination, $request, $start_date, $end_date);
        // return response()->json(['data' => $data, 'status_code' => 200], 200);
    }

    public function chain_filteration($data, $year_id, $type_id, $level_id, $class_id, $segment_id, $course_id, $pagination, $request, $start_date, $end_date)
    {
        // filter with none
        if ($year_id == null && $type_id == null && $level_id == null && $class_id == null && $segment_id == null && $course_id == null) {
            return $this->filter_with_none($data, $pagination, $request, $start_date, $end_date);
        }
        // case serach with year
        elseif ($year_id != null && $type_id == null && $level_id == null && $class_id == null && $segment_id == null && $course_id == null) {
            return $this->filter_with_year($data, $year_id, $pagination, $request, $start_date, $end_date);
        }
        // case serach with type
        elseif ($year_id != null && $type_id != null && $level_id == null && $class_id == null && $segment_id == null && $course_id == null) {
            return $this->filter_with_type($data, $year_id, $type_id, $pagination, $request, $start_date, $end_date);
        }
        // case serach with level
        elseif ($year_id != null && $type_id != null && $level_id != null && $class_id == null && $segment_id == null && $course_id == null) {
            return $this->filter_with_level($data, $year_id, $type_id, $level_id, $pagination, $request, $start_date, $end_date);
        }
        // case serach with class
        elseif ($year_id != null && $type_id != null && $level_id != null && $class_id != null && $segment_id == null && $course_id == null) {
            return $this->filter_with_class($data, $year_id, $type_id, $level_id, $class_id, $pagination, $request, $start_date, $end_date);
        }
        // case serach with segment
        elseif ($year_id != null && $type_id != null && $level_id != null && $class_id != null && $segment_id != null && $course_id == null) {
            return $this->filter_with_segment($data, $year_id, $type_id, $level_id, $class_id, $segment_id, $pagination, $request, $start_date, $end_date);
        }
        // case serach with course
        elseif ($year_id != null && $type_id != null && $level_id != null && $class_id != null && $segment_id != null && $course_id != null) {
            //return 'aa';
            return $this->filter_with_course($data, $year_id, $type_id, $level_id, $class_id, $segment_id, $course_id, $pagination, $request, $start_date, $end_date);
        }
        else{
            return 'another';
        }
    }

    // no chain filter selected
    public function filter_with_none($data, $pagination, $request, $start_date, $end_date)
    {
        // return $start_date;
        $yesterday =  date("Y-m-d h:i:s", strtotime( '-1 days' ));
        $right_now =  date("Y-m-d H:i:s");
        // search with current year
        $year_id   = AcademicYear::Get_current()->id;
        $chain_ids = AuditLog::where('year_id', 'like', "%{$year_id}%")->pluck('id')->toArray();

        $data =  $data->whereIn('id', $chain_ids)
                      //->where('created_at', '>=', $yesterday)->where('created_at', '<=', $right_now)
                      ->skip(($request->paginate * ($request->page - 1)))
                      ->take($request->paginate)
                      ->orderBy('created_at', 'DESC')
                      ->paginate($request->paginate);
        foreach ($data as $key => $value) {
            $value['description'] = 'Item in module ( '. $value->subject_type .' ) has been ( '. $value->action .' ) by ( '. $value->user->firstname. ' )';
            $value['since']       = $value->created_at->diffForHumans();
            $value['username']    = $value->user->fullname;

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

            if (array_key_exists($value->subject_type, $names_array)) {
              $model = $names_array[$value->subject_type];
            }else{
              $nameSpace = '\\app\\';
              $model     = $nameSpace.$value->subject_type; 
            }
            if ($value->subject_type == 'Enroll') {
              $value['item_name']   = $model::withTrashed()->where('id', $value->subject_id)->first()->user->fullname; 
              $value['item_id']     = $model::withTrashed()->where('id', $value->subject_id)->first()->user->id;    
            }elseif($value->subject_type == 'page'){
                 $value['item_name']   = $model::withTrashed()->where('id', $value->subject_id)->first()->title;
            }else{
                $value['item_name']   = $model::withTrashed()->where('id', $value->subject_id)->first()->name;    
            }
            // end item name

            $value->makeHidden('user');
        }
        if ($request->has('export') && $request->export == 1) {
            //return Excel::download(new AuditlogExport($data), 'auditlogs.xlsx');
            $filename = uniqid();
            $file     = Excel::store(new AuditlogExport($data), 'AuditLog'.$filename.'.xlsx','public');
            $file     = url(Storage::url('AuditLog'.$filename.'.xlsx'));
            return HelperController::api_response_format(201,$file, __('messages.success.link_to_file')); 
            
        }
        return response()->json(['data' => $data, 'status_code' => 200], 200);
    }

    // search with year
    public function filter_with_year($data, $year_id, $pagination, $request, $start_date, $end_date)
    {
        //$chain_ids = AuditLog::whereJsonContains('audit_logs.year_id', intval($year_id))->pluck('id')->toArray();
        $chain_ids = AuditLog::where('year_id', 'like', "%{$year_id}%")->pluck('id')->toArray();
        //$chain_data = $data->whereIn('id', $chain_ids)->unique()->paginate($pagination);
        $chain_data = $data->whereIn('id', $chain_ids)
                      //->where('created_at', '>=', $start_date)
                      //->where('created_at', '<=', $end_date)
                      ->skip(($request->paginate * ($request->page - 1)))
                      ->take($request->paginate)
                      ->orderBy('created_at', 'DESC')
                      ->paginate($request->paginate);
        foreach ($chain_data as $key => $value) {
            $value['description'] = 'Item in module ( '. $value->subject_type .' ) has been ( '. $value->action .' ) by ( '. $value->user->firstname. ' )';
            $value['since']       = $value->created_at->diffForHumans();
            $value['username']    = $value->user->fullname;

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

            if (array_key_exists($value->subject_type, $names_array)) {
              $model = $names_array[$value->subject_type];
            }else{
              $nameSpace = '\\app\\';
              $model     = $nameSpace.$value->subject_type; 
            }
            if ($value->subject_type == 'Enroll') {
              $value['item_name']   = $model::withTrashed()->where('id', $value->subject_id)->first()->user->fullname;
              $value['item_id']     = $model::withTrashed()->where('id', $value->subject_id)->first()->user->id;    
            }elseif($value->subject_type == 'page'){
                 $value['item_name']   = $model::withTrashed()->where('id', $value->subject_id)->first()->title;
            }else{
                $value['item_name']   = $model::withTrashed()->where('id', $value->subject_id)->first()->name;    
            }
            // end item name

            $value->makeHidden('user');
        }
        return response()->json(['data' => $chain_data, 'status_code' => 200], 200);
    }

     // search with type
    public function filter_with_type($data, $year_id, $type_id, $pagination, $request, $start_date, $end_date)
    {
        //$chain_ids = AuditLog::whereJsonContains('audit_logs.type_id', intval($type_id))->pluck('id')->toArray();
        $chain_ids = AuditLog::where('year_id', 'like', "%{$year_id}%")->where('type_id', 'like', "%{$type_id}%")
                            ->pluck('id')->toArray();
        //$chain_data = $data->whereIn('id', $chain_ids)->unique()->paginate($pagination);
        $chain_data = $data->whereIn('id', $chain_ids)
                      //->where('created_at', '>=', $start_date)
                      //->where('created_at', '<=', $end_date)
                      ->skip(($request->paginate * ($request->page - 1)))
                      ->take($request->paginate)
                      ->orderBy('created_at', 'DESC')
                      ->paginate($request->paginate);
        foreach ($chain_data as $key => $value) {
            $value['description'] = 'Item in module ( '. $value->subject_type .' ) has been ( '. $value->action .' ) by ( '. $value->user->firstname. ' )';
            $value['since']       = $value->created_at->diffForHumans();
            $value['username']    = $value->user->fullname;

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

            if (array_key_exists($value->subject_type, $names_array)) {
              $model = $names_array[$value->subject_type];
            }else{
              $nameSpace = '\\app\\';
              $model     = $nameSpace.$value->subject_type; 
            }
            if ($value->subject_type == 'Enroll') {
              $value['item_name']   = $model::withTrashed()->where('id', $value->subject_id)->first()->user->fullname; 
              $value['item_id']     = $model::withTrashed()->where('id', $value->subject_id)->first()->user->id;   
            }elseif($value->subject_type == 'page'){
                 $value['item_name']   = $model::withTrashed()->where('id', $value->subject_id)->first()->title;
            }else{
                $value['item_name']   = $model::withTrashed()->where('id', $value->subject_id)->first()->name;    
            }
            // end item name

            $value->makeHidden('user');
        }
        return response()->json(['data' => $chain_data, 'status_code' => 200], 200);
    }

     // search with level
    public function filter_with_level($data, $year_id, $type_id, $level_id, $pagination, $request, $start_date, $end_date)
    {
       // $chain_ids = AuditLog::whereJsonContains('audit_logs.level_id', intval($level_id))->pluck('id')->toArray();
        $chain_ids = AuditLog::where('year_id', 'like', "%{$year_id}%")->where('type_id', 'like', "%{$type_id}%")
                            ->where('level_id', 'like', "%{$level_id}%")->pluck('id')->toArray();
        //$chain_data = $data->whereIn('id', $chain_ids)->unique()->paginate($pagination);
        $chain_data = $data->whereIn('id', $chain_ids)
                      //->where('created_at', '>=', $start_date)
                      //->where('created_at', '<=', $end_date)
                      ->skip(($request->paginate * ($request->page - 1)))
                      ->take($request->paginate)
                      ->orderBy('created_at', 'DESC')
                      ->paginate($request->paginate);
        foreach ($chain_data as $key => $value) {
            $value['description'] = 'Item in module ( '. $value->subject_type .' ) has been ( '. $value->action .' ) by ( '. $value->user->firstname. ' )';
            $value['since']       = $value->created_at->diffForHumans();
            $value['username']    = $value->user->fullname;

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

            if (array_key_exists($value->subject_type, $names_array)) {
              $model = $names_array[$value->subject_type];
            }else{
              $nameSpace = '\\app\\';
              $model     = $nameSpace.$value->subject_type; 
            }
            if ($value->subject_type == 'Enroll') {
              $value['item_name']   = $model::withTrashed()->where('id', $value->subject_id)->first()->user->fullname;
              $value['item_id']     = $model::withTrashed()->where('id', $value->subject_id)->first()->user->id;    
            }elseif($value->subject_type == 'page'){
                 $value['item_name']   = $model::withTrashed()->where('id', $value->subject_id)->first()->title;
            }else{
                $value['item_name']   = $model::withTrashed()->where('id', $value->subject_id)->first()->name;    
            }
            // end item name

            $value->makeHidden('user');
        }
        return response()->json(['data' => $chain_data, 'status_code' => 200], 200);
    }

    // search with class
    public function filter_with_class($data, $year_id, $type_id, $level_id, $class_id, $pagination, $request, $start_date, $end_date)
    {
        // $chain_ids = AuditLog::whereJsonContains('audit_logs.class_id', intval($class_id))->pluck('id')->toArray();
        $chain_ids = AuditLog::where('year_id', 'like', "%{$year_id}%")->where('type_id', 'like', "%{$type_id}%")
                            ->where('level_id', 'like', "%{$level_id}%")->where('class_id', 'like', "%{$class_id}%")
                            ->pluck('id')->toArray();
        //$chain_data = $data->whereIn('id', $chain_ids)->unique()->paginate($pagination);
        $chain_data = $data->whereIn('id', $chain_ids)
                      //->where('created_at', '>=', $start_date)
                      //->where('created_at', '<=', $end_date)
                      ->skip(($request->paginate * ($request->page - 1)))
                      ->take($request->paginate)
                      ->orderBy('created_at', 'DESC')
                      ->paginate($request->paginate);
        foreach ($chain_data as $key => $value) {
            $value['description'] = 'Item in module ( '. $value->subject_type .' ) has been ( '. $value->action .' ) by ( '. $value->user->firstname. ' )';
            $value['since']       = $value->created_at->diffForHumans();
            $value['username']    = $value->user->fullname;

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

            if (array_key_exists($value->subject_type, $names_array)) {
              $model = $names_array[$value->subject_type];
            }else{
              $nameSpace = '\\app\\';
              $model     = $nameSpace.$value->subject_type; 
            }
            if ($value->subject_type == 'Enroll') {
              $value['item_name']   = $model::withTrashed()->where('id', $value->subject_id)->first()->user->fullname; 
              $value['item_id']     = $model::withTrashed()->where('id', $value->subject_id)->first()->user->id;   
            }elseif($value->subject_type == 'page'){
                 $value['item_name']   = $model::withTrashed()->where('id', $value->subject_id)->first()->title;
            }else{
                $value['item_name']   = $model::withTrashed()->where('id', $value->subject_id)->first()->name;    
            }
            // end item name

            $value->makeHidden('user');
        }
        return response()->json(['data' => $chain_data, 'status_code' => 200], 200);
    }

     // search with segment
    public function filter_with_segment($data, $year_id, $type_id, $level_id, $class_id, $segment_id, $pagination, $request, $start_date, $end_date)
    {
        // $chain_ids = AuditLog::whereJsonContains('audit_logs.segment_id', intval($segment_id))->pluck('id')->toArray();
        $chain_ids = AuditLog::where('year_id', 'like', "%{$year_id}%")->where('type_id', 'like', "%{$type_id}%")
                            ->where('level_id', 'like', "%{$level_id}%")->where('class_id', 'like', "%{$class_id}%")
                            ->where('segment_id', 'like', "%{$segment_id}%")->pluck('id')->toArray();
        //$chain_data = $data->whereIn('id', $chain_ids)->unique()->paginate($pagination);
        $chain_data = $data->whereIn('id', $chain_ids)
                      //->where('created_at', '>=', $start_date)
                      //->where('created_at', '<=', $end_date)
                      ->skip(($request->paginate * ($request->page - 1)))
                      ->take($request->paginate)
                      ->orderBy('created_at', 'DESC')
                      ->paginate($request->paginate);
        foreach ($chain_data as $key => $value) {
            $value['description'] = 'Item in module ( '. $value->subject_type .' ) has been ( '. $value->action .' ) by ( '. $value->user->firstname. ' )';
            $value['since']       = $value->created_at->diffForHumans();
            $value['username']    = $value->user->fullname;
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

            if (array_key_exists($value->subject_type, $names_array)) {
              $model = $names_array[$value->subject_type];
            }else{
              $nameSpace = '\\app\\';
              $model     = $nameSpace.$value->subject_type; 
            }
            if ($value->subject_type == 'Enroll') {
              $value['item_name']   = $model::withTrashed()->where('id', $value->subject_id)->first()->user->fullname; 
              $value['item_id']     = $model::withTrashed()->where('id', $value->subject_id)->first()->user->id;   
            }elseif($value->subject_type == 'page'){
                 $value['item_name']   = $model::withTrashed()->where('id', $value->subject_id)->first()->title;
            }else{
                $value['item_name']   = $model::withTrashed()->where('id', $value->subject_id)->first()->name;    
            }
            // end item name

            $value->makeHidden('user');
        }
        return response()->json(['data' => $chain_data, 'status_code' => 200], 200);
    }

    // search with course
    public function filter_with_course($data, $year_id, $type_id, $level_id, $class_id, $segment_id, $course_id, $pagination, $request, $start_date, $end_date)
    {
        // $chain_ids  = AuditLog::whereJsonContains('audit_logs.course_id', intval($course_id))->pluck('id')->toArray();
        $chain_ids  = AuditLog::where('year_id', 'like', "%{$year_id}%")->where('type_id', 'like', "%{$type_id}%")
                            ->where('level_id', 'like', "%{$level_id}%")->where('class_id', 'like', "%{$class_id}%")
                            ->where('segment_id', 'like', "%{$segment_id}%")->where('course_id', 'like', "%{$course_id}%")
                            ->pluck('id')->toArray();
        $chain_data = $data->whereIn('id', $chain_ids)
                      //->where('created_at', '>=', $start_date)
                      //->where('created_at', '<=', $end_date)
                      ->skip(($request->paginate * ($request->page - 1)))
                      ->take($request->paginate)
                      ->orderBy('created_at', 'DESC')
                      ->paginate($request->paginate);
        //$chain_data = $data->whereIn('id', $chain_ids)->unique();
        foreach ($chain_data as $key => $value) {
            $value['description'] = 'Item in module ( '. $value->subject_type .' ) has been ( '. $value->action .' ) by ( '. $value->user->firstname. ' )';
            $value['since']       = $value->created_at->diffForHumans();
            $value['username']    = $value->user->fullname;
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

            if (array_key_exists($value->subject_type, $names_array)) {
              $model = $names_array[$value->subject_type];
            }else{
              $nameSpace = '\\app\\';
              $model     = $nameSpace.$value->subject_type; 
            }
            if ($value->subject_type == 'Enroll') {
              $value['item_name']   = $model::withTrashed()->where('id', $value->subject_id)->first()->user->fullname; 
              $value['item_id']     = $model::withTrashed()->where('id', $value->subject_id)->first()->user->id;   
            }elseif($value->subject_type == 'page'){
                 $value['item_name']   = $model::withTrashed()->where('id', $value->subject_id)->first()->title;
            }else{
                $value['item_name']   = $model::withTrashed()->where('id', $value->subject_id)->first()->name;    
            }
            // end item name
            $value->makeHidden('user');
        }
        return response()->json(['data' => $chain_data, 'status_code' => 200], 200);
    }
}
