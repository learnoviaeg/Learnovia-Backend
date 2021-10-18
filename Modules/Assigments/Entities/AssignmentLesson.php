<?php

namespace Modules\Assigments\Entities;
use App\Scopes\overrideAssignmentScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Modules\Assigments\Entities\assignmentOverride;
use App\UserSeen;

class AssignmentLesson extends Model
{
    protected $fillable = ['assignment_id','lesson_id','allow_edit_answer','publish_date','visible', 'start_date', 'due_date', 'is_graded', 'grade_category', 'mark', 'scale_id', 'allow_attachment','seen_number'];

    protected $appends = ['started','user_seen_number','Status'];

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

    public function getUserSeenNumberAttribute(){

        $user_seen = 0;
        if($this->seen_number != 0)
            $user_seen = UserSeen::where('type','assignment')->where('item_id',$this->assignment_id)->where('lesson_id',$this->lesson_id)->count();
            
        return $user_seen;  
    }

    public function getStatusAttribute(){

        //student statuses
        if(Auth::user()->can('site/course/student')){
            $status = __('messages.status.not_submitted');

            $user_assigment = UserAssigment::where('assignment_lesson_id', $this->id)->where('user_id',Auth::id())->whereNotNull('submit_date')->first();
            if(isset($user_assigment)){
                $status = __('messages.status.submitted');//submitted
                if(isset($user_assigment->grade))
                    $status = __('messages.status.graded');//graded
            }
        }

        if(!Auth::user()->can('site/course/student')){
            $status = __('messages.status.no_answers');

            $user_assigment = UserAssigment::where('assignment_lesson_id', $this->id)->whereNotNull('submit_date')->pluck('grade');
            if(count($user_assigment) > 0)
                $status = __('messages.status.not_graded');//not_graded

            if(count($user_assigment) > 0 && !in_array(NULL,$user_assigment->toArray(),true))
                $status = __('messages.status.graded');//graded
        }

        return $status;
    }

    public function Assignment()
    {
        return $this->hasMany('Modules\Assigments\Entities\assignment', 'id', 'assignment_id');
    }
    public function UserAssignment()
    {
        return $this->hasMany('Modules\Assigments\Entities\UserAssigment', 'id', 'assignment_lesson_id');
    }

    public function assignmentOverride()
    {
        return  $this->hasMany('Modules\Assigments\Entities\assignmentOverride','assignment_lesson_id', 'id');
    }

    public function lesson()
    {
        return $this->belongsTo('App\Lesson', 'lesson_id', 'id');
    }
    
    public static function boot() 
    {
        parent::boot();
        static::addGlobalScope(new overrideAssignmentScope);
    }
}

