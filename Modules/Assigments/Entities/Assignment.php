<?php

namespace Modules\Assigments\Entities;

use Illuminate\Database\Eloquent\Model;

class assignment extends Model
{
    protected $fillable = ['name', 'content', 'attachment_id'];
    public function attachment()
    {
        return $this->belongsTo('App\attachment', 'attachment_id', 'id');
    }
    public function UserAssigment()
    {
        return $this->hasMany('Modules\Assigments\Entities\UserAssigment', 'assignment_id', 'id');
    }
    public function Lesson()
    {
        return $this->belongsToMany('App\Lesson', 'assignment_lessons', 'assignment_id', 'lesson_id');
    }
}
