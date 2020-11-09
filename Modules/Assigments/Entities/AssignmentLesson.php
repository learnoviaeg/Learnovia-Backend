<?php

namespace Modules\Assigments\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Modules\Assigments\Entities\assignmentOverride;
class AssignmentLesson extends Model
{
    protected $fillable = ['assignment_id','lesson_id','publish_date','visible', 'start_date', 'due_date', 'is_graded', 'grade_category', 'mark', 'scale_id', 'allow_attachment'];

    protected $appends = ['started'];

    public function getStartedAttribute(){
        $started = true;
        $override = assignmentOverride::where('user_id',Auth::user()->id)->where('assignment_lesson_id',$this->id)->first();
        if($override != null){
            $this->start_date = $override->start_date;
            $this->due_date = $override->due_date;
        }
        if((Auth::user()->can('site/course/student') && $this->publish_date > Carbon::now()) || (Auth::user()->can('site/course/student') && $this->start_date > Carbon::now()))
            $started = false;

        return $started;  
    }
    public function Assignment()
    {
        return $this->hasMany('Modules\Assigments\Entities\assignment', 'id', 'assignment_id');
    }
    public function UserAssignment()
    {
        return $this->hasMany('Modules\Assigments\Entities\UserAssigment', 'id', 'assignment_lesson_id');
    }
}

