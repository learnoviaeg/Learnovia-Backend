<?php

namespace Modules\QuestionBank\Entities;

use App\GradeCategory;
use App\Scopes\OverrideQuizScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Modules\QuestionBank\Entities\QuizOverride;
use App\UserSeen;
use App\SystemSetting;
use App\UserGrader;
use App\Traits\Auditable;

class QuizLesson extends Model
{
    use Auditable;

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
    protected $appends = ['started','user_seen_number','Status', 'token_attempts', 'ended'];

    protected $dispatchesEvents = [
        'updated' => \App\Events\updateQuizAndQuizLessonEvent::class,
    ];

    public function getStartedAttribute(){

        $started = true;

        if(count($this->override) > 0){

            $override = $this->override->first();
            $this->start_date = $override->start_date;
        }

        if((Auth::user()->can('site/course/student') && $this->publish_date > Carbon::now()) || (Auth::user()->can('site/course/student') && $this->start_date > Carbon::now())){
            $started = false;
        }

        return $started;
    }

    public function getEndedAttribute(){

        $ended = false;

        if(count($this->override) > 0){
            $override = $this->override->first();
            $this->due_date = $override->due_date;
        }

        if((Auth::user()->can('site/course/student') && $this->due_date < Carbon::now())){
            $ended = true;
        }

        return $ended;
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
            if(isset($user_quiz) && !in_array(NULL,$user_quiz_asnwer->pluck('force_submit')->toArray())){
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

    public function getTokenAttemptsAttribute()
    {
        $user_quiz = userQuiz::where('user_id', Auth::id())->where('quiz_lesson_id', $this->id)->count();
        return $user_quiz;
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

    public function userGrader()
    {
        return $this->hasManyThrough(UserGrader::class, GradeCategory::class, 'instance_id','item_id','quiz_id')->where('instance_type','Quiz');
    }

    public function getGradingMethodIdAttribute($value)
    {
        $content= json_decode($value);
        if(is_null($value))
            $content = [];
        return $content;
    }

    public function getGradePassAttribute()
    {
        $content = $this->attributes['grade_pass'];
        $grade_pass = SystemSetting::where('key','Quiz grade to pass')->first();
        if(isset($grade_pass)){
            $percentage = (float)$grade_pass->data / 100;
            if(isset($this->attributes['questions_mark']) && $this->attributes['questions_mark'] != 0)
                $content = $this->attributes['questions_mark'] * $percentage;
        }

        return (double) $content;
    }

    public function user_quiz()
    {
        return  $this->hasMany('Modules\QuestionBank\Entities\userQuiz','quiz_lesson_id', 'id');
    }

    public function override()
    {
        return  $this->hasMany('Modules\QuestionBank\Entities\QuizOverride','quiz_lesson_id', 'id');
    }

    public static function boot()
    {
        parent::boot();
        static::addGlobalScope(new OverrideQuizScope);
    }

     // start function get name and value f attribute
    public static function get_year_name($old, $new)
    {
        return null;
    }
    // end function get name and value attribute

    // start function get name and value f attribute
    public static function get_type_name($old, $new)
    {
        return null;
    }
    // end function get name and value attribute

    // start function get name and value f attribute
    public static function get_level_name($old, $new)
    {
        return null;
    }
    // end function get name and value attribute

    // start function get name and value f attribute
    public static function get_class_name($old, $new)
    {
        return null;
    }
    // end function get name and value attribute

    // start function get name and value f attribute
    public static function get_segment_name($old, $new)
    {
        return null;
    }
    // end function get name and value attribute

    // start function get name and value f attribute
    public static function get_course_name($old, $new)
    {
        return null;
    }
    // end function get name and value attribute
}

