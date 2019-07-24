<?php

namespace Modules\Assigments\Entities;

use Illuminate\Database\Eloquent\Model;

class assignment extends Model
{
    protected $fillable = ['name','visiable','content','attachment_id','opening_date','closing_date','is_graded','grade_category','mark','scale_id','allow_attachment'];
    public function attachment()
    {
        return $this->hasOne('App\attachment', 'attachment_id', 'id');
    }
    public function UserAssigment()
    {
        return $this->hasMany('Modules\Assigments\Entities\UserAssigment', 'assignment_id', 'id');
    }
public function Lesson()
{
    return $this->belongsToMany('App\Lesson', 'assigment_lesson', 'assigment_id', 'lesson_id');
}
}
