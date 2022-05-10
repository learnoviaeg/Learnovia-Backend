<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Modules\Assigments\Entities\AssignmentLesson;
use Modules\Assigments\Entities\UserAssigment;
use Modules\QuestionBank\Entities\userQuizAnswer;
use Modules\QuestionBank\Entities\userQuiz;
use Modules\QuestionBank\Entities\QuizLesson;
use Carbon\Carbon;
use App\Traits\Auditable;
use App\Course;
use App\Segment;
use Illuminate\Database\Eloquent\SoftDeletes;

class Timeline extends Model
{
    use SoftDeletes;
    
    protected $fillable = [
        'item_id', 'name','start_date','due_date','publish_date','course_id','class_id','level_id','lesson_id','type','visible','overwrite_user_id'
    ];

    protected $appends = ['started','status'];

    public function getStartedAttribute(){
        $started = true;
        if((Auth::user()->can('site/course/student') && $this->publish_date > Carbon::now()) || (Auth::user()->can('site/course/student') && $this->start_date > Carbon::now()))
            $started = false;

        return $started;
    }

    public function getStatusAttribute(){

        //student statuses
        if(Auth::user()->can('site/course/student')){
            $status = __('messages.status.not_submitted');

            if($this->type == 'assignment'){
                $assigLessonID = AssignmentLesson::where('assignment_id', $this->item_id)->where('lesson_id', $this->lesson_id)->first();
                $user_assigment = UserAssigment::where('assignment_lesson_id', $assigLessonID->id)->where('user_id',Auth::id())->whereNotNull('submit_date')->first();
                if(isset($user_assigment)){
                    $status = __('messages.status.submitted');//submitted
                    if(isset($user_assigment->grade))
                        $status = __('messages.status.graded');//graded
                }
            }

            if($this->type == 'quiz'){
                $quiz_lesson = QuizLesson::where('quiz_id', $this->item_id)->where('lesson_id', $this->lesson_id)->first();
                if(isset($quiz_lesson)){
                    $user_quiz = userQuiz::where('user_id', Auth::id())->where('quiz_lesson_id', $quiz_lesson->id)->pluck('id');
                    $user_quiz_asnwer = userQuizAnswer::whereIn('user_quiz_id',$user_quiz)->get();
                    if(isset($user_quiz) && $quiz_lesson->max_attemp >= count($user_quiz) && count($user_quiz)!=0 &&  !in_array(NULL,$user_quiz_asnwer->pluck('force_submit')->toArray())){
                        $status = __('messages.status.submitted');//submitted

                        if(!in_array(NULL,$user_quiz_asnwer->pluck('user_grade')->toArray(),true))
                            $status = __('messages.status.graded');//graded
                    }
                }
            }
        }

        if(!Auth::user()->can('site/course/student')){
            $status = __('messages.status.no_answers');

            if($this->type == 'assignment'){
                $assigLessonID = AssignmentLesson::where('assignment_id', $this->item_id)->where('lesson_id', $this->lesson_id)->first();
                $user_assigment = UserAssigment::where('assignment_lesson_id', $assigLessonID->id)->whereNotNull('submit_date')->get();
                if(($user_assigment)->count() != 0){
                if(count($user_assigment) > 0)
                    $status = __('messages.status.not_graded');//not_graded

                if(count($user_assigment) > 0 && !in_array(NULL,$user_assigment->toArray(),true))
                    $status = __('messages.status.graded');//graded
                }
            }

            if($this->type == 'quiz'){
                $quiz_lesson = QuizLesson::where('quiz_id', $this->item_id)->where('lesson_id', $this->lesson_id)->first();
                if(isset($quiz_lesson)){
                    $user_quiz = userQuiz::where('quiz_lesson_id', $quiz_lesson->id)->pluck('id');
                    $user_quiz_asnwer = userQuizAnswer::whereIn('user_quiz_id',$user_quiz)->where('force_submit',1)->pluck('user_grade');

                    if(count($user_quiz_asnwer) > 0)
                        $status = __('messages.status.not_graded');//not_graded

                    if(count($user_quiz_asnwer) > 0 && !in_array(NULL,$user_quiz_asnwer->toArray(),true))
                        $status = __('messages.status.graded');//graded
                }
            }
        }

        return $status;
    }

    public function class(){
        return $this->belongsTo('App\Classes');
    }

    public function course(){
        return $this->belongsTo('App\Course');
    }

    public function level(){
        return $this->belongsTo('App\Level');
    }

    public function item(){
        return $this->morphTo('item' , 'type', 'item_id');
    }
    
}
