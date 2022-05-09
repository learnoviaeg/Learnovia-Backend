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
use App\Lesson as Lessonmodel;
use App\AuditLog;
use App\Course;
use App\Segment;

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
        'publish_date','collect_marks',
        'visible','index','seen_number', 'grade_pass' , 'questions_mark'
    ];
    protected $table = 'quiz_lessons';
    protected $appends = ['started','user_seen_number','Status', 'token_attempts', 'ended'];

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
            $quiz_lesson = QuizLesson::whereId($this->id)->first();
            $user_quiz = userQuiz::where('user_id', Auth::id())->where('quiz_lesson_id', $this->id)->pluck('id');
            $user_quiz_asnwer = userQuizAnswer::whereIn('user_quiz_id',$user_quiz)->get();
            if(isset($user_quiz) && $quiz_lesson->max_attemp >= count($user_quiz) && count($user_quiz)!=0 && !in_array(NULL,$user_quiz_asnwer->pluck('force_submit')->toArray())){
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
        $lesson_id    = $new->lesson_id;
        $course_id    = Lessonmodel::where('id', $lesson_id)->first()->course_id;
        $segment_id   = Course::where('id', $course_id)->first()->segment_id;
        $segment      = Segment::where('id', $segment_id)->first();
        $academic_year_id = $segment->academic_year_id;

        AuditLog::where(['subject_type' => 'quiz', 'subject_id' => $new->quiz_id])->update([
            'year_id' => $academic_year_id
        ]);

        return $academic_year_id;
    }
    // end function get name and value attribute

    // start function get name and value f attribute
    public static function get_type_name($old, $new)
    {
        $lesson_id    = $new->lesson_id;
        $course_id    = Lessonmodel::where('id', $lesson_id)->first()->course_id;
        $segment_id   = Course::where('id', $course_id)->first()->segment_id;
        $segment      = Segment::where('id', $segment_id)->first();
        $academic_type_id = $segment->academic_type_id;

        AuditLog::where(['subject_type' => 'quiz', 'subject_id' => $new->quiz_id])->update([
            'type_id' => $academic_type_id
        ]);

        return $academic_type_id;
    }
    // end function get name and value attribute

    // start function get name and value f attribute
    public static function get_level_name($old, $new)
    {
        $lesson_id    = $new->lesson_id;
        $course_id    = Lessonmodel::where('id', $lesson_id)->first()->course_id;
        $level_id   = Course::where('id', $course_id)->first()->level_id;

        AuditLog::where(['subject_type' => 'quiz', 'subject_id' => $new->quiz_id])->update([
            'level_id' => $level_id
        ]);

        return $level_id;
    }
    // end function get name and value attribute

    // start function get name and value f attribute
    public static function get_class_name($old, $new)
    {
        $lesson_id    = $new->lesson_id;
        $lesson       = Lessonmodel::where('id', $lesson_id)->first();
        $classes      = $lesson['shared_classes']->pluck('id');

        AuditLog::where(['subject_type' => 'quiz', 'subject_id' => $new->quiz_id])->update([
            'class_id' => $classes
        ]);

        return $classes;
    }
    // end function get name and value attribute

    // start function get name and value f attribute
    public static function get_segment_name($old, $new)
    {
        $lesson_id    = $new->lesson_id;
        $course_id    = Lessonmodel::where('id', $lesson_id)->first()->course_id;
        $segment_id = Course::where('id', $course_id)->first()->segment_id;

        AuditLog::where(['subject_type' => 'quiz', 'subject_id' => $new->quiz_id])->update([
            'segment_id' => $segment_id
        ]);
        return $segment_id;
    }
    // end function get name and value attribute

    // start function get name and value f attribute
    public static function get_course_name($old, $new)
    {
       // $lessons_id   = QuizLesson::where('quiz_id', $new->quiz_id)->pluck('lesson_id');
        $lesson_id    = $new->lesson_id;
        $course_id  = Lessonmodel::where('id', $lesson_id)->first()->course_id;
        AuditLog::where(['subject_type' => 'quiz', 'subject_id' => $new->quiz_id])->update([
            'course_id' => $course_id
        ]);
        return $course_id;
    }
    // end function get name and value attribute
}

