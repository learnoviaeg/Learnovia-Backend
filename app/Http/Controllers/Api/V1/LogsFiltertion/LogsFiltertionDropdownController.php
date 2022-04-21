<?php

namespace App\Http\Controllers\Api\V1\LogsFiltertion;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\AuditLog;
use App\Http\Controllers\HelperController;

class LogsFiltertionDropdownController extends Controller
{    
    public function logs_models_dropdown()
    {
        $data       = AuditLog::pluck('subject_type');
        $chain_data = $data->unique();
        $models_arr = array();

        foreach ($chain_data as $key => $value) {
        	if (in_array($value, $models_arr)) {
        		continue;
        	}else{
        		array_push($models_arr, $value);
        	}
        }
        
        return response()->json([
        	'data' => $models_arr, 
        	'status_code' => 200,
        ], 200);
    }

    public function logs_actions_dropdown()
    {
        $data       = AuditLog::pluck('action');
        $chain_data = $data->unique();
        $actions_arr = array();

        foreach ($chain_data as $key => $value) {
        	if (in_array($value, $actions_arr)) {
        		continue;
        	}else{
        		array_push($actions_arr, $value);
        	}
        }
        
        return response()->json([
        	'data' => $actions_arr, 
        	'status_code' => 200,
        ], 200);
    }
}
