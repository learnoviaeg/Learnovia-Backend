<?php

namespace Modules\Assigments\Entities;

use Illuminate\Database\Eloquent\Model;

class assignment extends Model
{
    protected $fillable = ['name', 'content', 'attachment_id'];
    protected $appends = ['url' , 'url2'];
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

    public function getUrlAttribute()
    {
        if (isset($this->attachment)) {
            return 'https://docs.google.com/viewer?url=' . $this->attachment->path;
        }
    }

    public function getUrl2Attribute()
    {
        if (isset($this->attachment)) {
            return $this->attachment->path;
        }
    }
    public function assignmentLessson()
    {
        return $this->hasMany('Modules\Assigments\Entities\AssignmentLesson', 'assignment_id', 'id');
    }
}
