<?php

namespace App\Http\Controllers\Api\V1\LogsFiltertion;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\User;
use Auth;
use App\AuditLog;

class FetchOneLogApiController extends Controller
{
    public function fetch_logs(AuditLog $log)
    {
    	$data = $log->properties;
    	if ($log->action == 'updated') {
    		$before = $log->before;
    		//$diff = array_diff_assoc( array($before), array($data) );
    		//$diff->makeHidden(['created_at', 'updted_at', 'deleted_at']); // hide some attrs
    		return response()->json(['data' => $data, 'before' => $before, 'status_code' => 200], 200);
    	}else{
    		return response()->json(['data' => $data, 'status_code' => 200], 200);
    	}
    }
}
