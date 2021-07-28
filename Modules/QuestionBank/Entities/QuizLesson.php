<?php

namespace Modules\QuestionBank\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Modules\QuestionBank\Entities\QuizOverride;
use App\UserSeen;

class QuizLesson extends Model
{
    protected $fillable = [
        'quiz_id',
        'lesson_id',
        'start_date',
        'due_date',
        'max_attemp',
        'grading_method_id',
        'grade',
        'grade_category_id',
        'publish_date',
        'visible','index','seen_number', 'grade_pass' , 'questions_mark', 'grade_by_user'
    ];
    protected $table = 'quiz_lessons';
    protected $appends = ['started','user_seen_number','Status'];

    public function getStartedAttribute(){
        $started = true;
        $override = QuizOverride::where('user_id',Auth::user()->id)->where('quiz_lesson_id',$this->id)->first();
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
            $user_seen = UserSeen::where('type','quiz')->where('item_id',$this->quiz_id)->where('lesson_id',$this->lesson_id)->count();
            
        return $user_seen;  
    }

    public function getStatusAttribute(){

        //student statuses
        if(Auth::user()->can('site/course/student')){
            $status = __('messages.status.not_submitted');

            $user_quiz = userQuiz::where('user_id', Auth::id())->where('quiz_lesson_id', $this->id)->pluck('id');
            $user_quiz_asnwer = userQuizAnswer::whereIn('user_quiz_id',$user_quiz)->get();
            if(isset($user_quiz) && $this->max_attemp == count($user_quiz) && !in_array(NULL,$user_quiz_asnwer->pluck('force_submit')->toArray())){
                $status = __('messages.status.submitted');//submitted
                
                if(!in_array(NULL,$user_quiz_asnwer->pluck('user_grade')->toArray(),true))
                    $status = __('messages.status.graded');//graded 
            }
        }

        if(!Auth::user()->can('site/course/student')){
            $status = __('messages.status.no_answers');

            $user_quiz = userQuiz::where('quiz_lesson_id', $this->id)->pluck('id');
            $user_quiz_asnwer = userQuizAnswer::whereIn('user_quiz_id',$user_quiz)->where('force_submit',1)->pluck('user_grade');
            
            if(count($user_quiz_asnwer) > 0)
                $status = __('messages.status.not_graded');//not_graded

            if(count($user_quiz_asnwer) > 0 && !in_array(NULL,$user_quiz_asnwer->toArray(),true))
                $status = __('messages.status.graded');//graded
        }

        return $status;
    }

    public function quiz()
    {
        return $this->belongsTo('Modules\QuestionBank\Entities\quiz', 'quiz_id', 'id');
    }
    public function lesson()
    {
        return $this->belongsTo('App\Lesson', 'lesson_id', 'id');
    }
    public function grading_method()
    {
        return $this->belongsTo('App\GradingMethod', 'grading_method_id', 'id');
    }

    public function getGradingMethodIdAttribute($value)
    {
        $content= json_decode($value);
        return $content;
    }
}
