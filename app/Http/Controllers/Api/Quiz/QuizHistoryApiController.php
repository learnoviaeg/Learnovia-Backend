<?php

namespace App\Http\Controllers\Api\Quiz;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Auth;
use DB;
use App\AuditLog;
use Modules\QuestionBank\Entities\quiz;
use App\Http\Resources\Api\LogsFiltertion\LogsFilterResource;

class QuizHistoryApiController extends Controller
{
    public function quiz_history($id)
    {
    	$pagination = isset($request->paginate) ? $request->paginate : 15;
    	$notNeeded  = ['QuizLesson'];
    	$needed     = ['quiz', 'quiz_questions'];
    	$select     = ["id", "action", "subject_id", "subject_type", "user_id", "host", "created_at", 
    	               "item_name", "hole_description"];

    	$data       = Auditlog::where('subject_id', $id)->orWhere('item_id', $id)
    	                ->where('subject_type', 'quiz')->orWhere('item_name', 'quiz')
    	                //->whereNotIn('subject_type', $notNeeded)
    	                ->whereIn('subject_type', $needed)
    	                ->select($select)
    	                ->paginate($pagination);
    	                //->pluck('id');

    	LogsFilterResource::collection($data);
    	return response()->json([
    		'data'         => $data,
    		'status_code'  => 200,
    	]);
    }

    public function history_view_details(Auditlog $log)
    {
    	$data = $log->properties;
    	$foreign_keys = [
              'question_id'        => '\Modules\QuestionBank\Entities\Questions',
              'quiz_id'            => 'Modules\QuestionBank\Entities\quiz',
            ];
        foreach ($data as $data_key => $data_value) 
        {
            if (array_key_exists($data_key, $foreign_keys) && ($data_key == 'question_id' || $data_key == 'quiz_id')) 
            {
                    $new_name = __('ahmed.'.$data_key.'');
                    if ($data_key == 'question_id') {
                        $data[$new_name] = $foreign_keys[$data_key]::where('id', $data_value)->groupBy('text')->pluck('text');
                    }
                    if ($data_key == 'quiz_id') {
                        $data[$new_name] = $foreign_keys[$data_key]::where('id', $data_value)->groupBy('name')->pluck('name');
                    }

                    unset($data[$data_key]);
            }
        }

    	return response()->json([
    		'data'         => $data,
    		'status_code'  => 200,
    	]);
    }
}
