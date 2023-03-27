<?php

namespace App\Traits;

use App\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Route;

trait AuditableView
{
    public static function bootAuditableView()
    {
	      $name   = Route::currentRouteName();
	      // $action = Route::currentRouteAction();
	      if ($name == 'fetch_logs') {
	      	$model      = 'AuditLog';
	      	$subject_id = request()->route('log');
	      	$notes      = 'specific';
	      	self::store_log('viewed', $model, $subject_id, $notes);
	      }

	      if ($name == 'logs_filteration') {
	      	$model       = 'AuditLog';
	      	$subject_id  = 0;
	      	$notes       = 'all';
	      	self::store_log('viewed', $model, $subject_id, $notes);
	      }
    }

    protected static function store_log($description, $model, $subject_id, $notes)
    {
        AuditLog::create([
            'action'       => $description,
            'subject_id'   => $subject_id,
            'subject_type' => $model,
            'user_id'      => auth()->id() ?? null,
            'role_id'      => auth()->id() ? auth()->user()->roles->pluck('id')->toArray() : null,
            'host'         => request()->ip() ?? null,
            'properties'   => null,
            'year_id'      => null,
            'type_id'      => null,
            'level_id'     => null, 
            'class_id'     => null, 
            'segment_id'   => null, 
            'course_id'    => null, 
            'before'       => null,
            'notes'        => $notes,
        ]);
    }
}
