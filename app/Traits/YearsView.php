<?php

namespace App\Traits;

use App\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Route;

trait YearsView
{
    public static function bootYearsView()
    {
	      // $name   = Route::currentRouteName();
	      // $action = Route::currentRouteAction();
        if ( \Request::is('api/years')  && \Request::isMethod('get') )  { 
            $model      = 'AcademicYear';
            $subject_id = 0;
            $notes      = 'all';
            self::store_years('viewed', $model, $subject_id, $notes);
        }

        if ( \Request::is('api/years/*')  && \Request::isMethod('get') )  {
            $model       = 'AcademicYear';
            $subject_id  = substr(request()->getRequestUri(), 11);
            $subject_id  = intval($subject_id);
            $notes       = 'specific';
            self::store_years('viewed', $model, $subject_id, $notes);
        }
    }

    protected static function store_years($description, $model, $subject_id, $notes)
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
