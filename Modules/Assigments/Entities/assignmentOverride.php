<?php

namespace Modules\Assigments\Entities;

use Illuminate\Database\Eloquent\Model;

class assignmentOverride extends Model
{
    protected $fillable = ['user_id','assignment_lesson_id','start_date','due_date'];

    public function assignmentLesson()
    {
        return $this->belongsTo('Modules\Assigments\Entities\AssignmentLesson', 'assignment_lesson_id', 'id');
    }

    public function users()
    {
        return $this->belongsTo('App\User','user_id','id');
    }
}
