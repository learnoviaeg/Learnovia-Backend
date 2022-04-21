<?php

namespace App\Http\Controllers\Api\V1\LogsFiltertion;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\AuditLog;
use App\Http\Controllers\HelperController;

class LogsFiltertionDropdownController extends Controller
{
    
     // search with type
    public function logs_models_dropdown()
    {
        //$chain_ids = AuditLog::whereJsonContains('audit_logs.type_id', intval($type_id))->pluck('id')->toArray();
        $data       = AuditLog::pluck('subject_type');
        $chain_data = $data->unique();
        
        return response()->json([
        	'data' => array($chain_data), 
        	'status_code' => 200,
        ], 200);

    }
}
