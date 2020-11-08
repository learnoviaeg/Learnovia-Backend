<?php

namespace Modules\QuestionBank\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
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
        'visible','index'
    ];
    protected $table = 'quiz_lessons';
    protected $appends = ['started'];

    public function getStartedAttribute(){
        $started = true;
        if((Auth::user()->can('site/course/student') && $this->publish_date > Carbon::now()) || (Auth::user()->can('site/course/student') && $this->start_date > Carbon::now()))
            $started = false;

        return $started;  
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
}
