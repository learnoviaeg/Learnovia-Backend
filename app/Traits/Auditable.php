<?php

namespace App\Traits;

use App\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

trait Auditable
{
    public static function bootAuditable()
    {
        static::created(function (Model $model) {
            self::audit('created', $model);
        });

        /*static::index(function (Model $model) {
            self::audit('index', $model);
        });*/

        static::updated(function (Model $model) {
            self::audit('updated', $model);
        });

        static::deleted(function (Model $model) {
            self::audit('deleted', $model);
        });
    }

    protected static function audit($description, $model)
    {
        $subject_model = substr(get_class($model),strripos(get_class($model),'\\')+1);
        if ($subject_model == 'Course') {
            $created_at = Carbon::now()->addSeconds(1);
        }else{
            $created_at = Carbon::now();
        }
        AuditLog::create([
            'action'       => $description,
            'subject_id'   => $model->id ?? null,
            'subject_type' => substr(get_class($model),strripos(get_class($model),'\\')+1),//get_class($model) ?? null,
            'user_id'      => auth()->id() ?? null,
            'role_id'      => auth()->id() ? auth()->user()->roles->pluck('id')->toArray() : null,
            'properties'   => $model ?? null,
            'host'         => request()->ip() ?? null,
            'year_id'      => $model->get_year_name($model->getOriginal(), $model),
            'type_id'      => $model->get_type_name($model->getOriginal(), $model),
            'level_id'     => $model->get_level_name($model->getOriginal(), $model),
            'class_id'     => $model->get_class_name($model->getOriginal(), $model),
            'segment_id'   => $model->get_segment_name($model->getOriginal(), $model), 
            'course_id'    => $model->get_course_name($model->getOriginal(), $model),
            'before'       => $model->getOriginal(),
            'created_at'   => $created_at,
        ]);
    }
}
