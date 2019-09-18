<?php

namespace Modules\Assigments\Entities;

use Illuminate\Database\Eloquent\Model;

class AssignmentLesson extends Model
{
    protected $fillable = ['assignment_id','lesson_id','publish_date','visible'];

    public function Assignment()
    {
        return $this->hasMany('Modules\Assigments\Entities\assignment', 'id', 'assignment_id');
    }
}

