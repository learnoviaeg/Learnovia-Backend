<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;

class Lesson extends Model
{
    use Auditable;

    protected $fillable = ['name','course_segment_id','index' , 'image' , 'description','shared_lesson','course_id' ,'shared_classes'];

    protected $dispatchesEvents = [
        'created' => \App\Events\LessonCreatedEvent::class,
    ];

    public function courseSegment(){
        return $this->belongsTo('App\CourseSegment');
    }
    public static function Get_lessons_per_CourseSegment_from_lessonID($id){
        $lesson=self::where('id',$id)->first();
        $lessons=$lesson->courseSegment->lessons;
        return $lessons;
    }
    public function module($name,$model)
    {
        return $this->belongsToMany('Modules\\'.$name.'\Entities\\'.$model, $model.'_lessons', 'lesson_id', $model.'_id')->withPivot('publish_date','created_at');
    }
    protected $hidden = [
        'created_at','updated_at'
    ];

    public function FileLesson()
    {
        return $this->hasMany('Modules\UploadFiles\Entities\FileLesson', 'lesson_id', 'id');
    }

    public function MediaLesson()
    {
        return $this->hasMany('Modules\UploadFiles\Entities\MediaLesson', 'lesson_id', 'id');
    }

    public function QuizLesson()
    {
        return $this->hasMany('Modules\QuestionBank\Entities\QuizLesson', 'lesson_id', 'id');
    }

    public function AssignmentLesson()
    {
        return $this->hasMany('Modules\Assigments\Entities\AssignmentLesson', 'lesson_id', 'id');
    }
    
    public function H5PLesson()
    {
        return $this->hasMany('App\h5pLesson', 'lesson_id', 'id');
    }

    public function Quiz()
    {
        return $this->hasMany('Modules\QuestionBank\Entities\Quiz','id');
    }

    public function SecondaryChain(){
        return $this->hasMany('App\SecondaryChain','lesson_id' , 'id');

    }

    public function getSharedClassesAttribute($value)
    {   if($value != null){
            $content= json_decode($value);
            return Classes::whereIn('id',$content)->get();
        }
        return $value;
    }
    
    public function course()
    {
        return $this->belongsTo('App\Course','course_id','id');
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
        
        $old_count = count($old);
        if ($old_count == 0) {
            $classes = $new['shared_classes']->pluck('id');
        }else{
                $v1      = $old['shared_classes'];
                $first   = str_replace("\"", "", $v1);
                $r       = array($first);
                $move1   = trim($r[0], "[");
                $move2   = trim($move1, "]");
                $v1_edit = explode(",", $move2); 
                $intvals = array();
                foreach ($v1_edit as $key => $value) {
                    array_push($intvals, intval($value));
                }

            if ($intvals == $new['shared_classes']->pluck('id')->toArray()) {
                $classes = $new['shared_classes']->pluck('id');
            }else{
                $v2 = $new['shared_classes']->pluck('id')->toArray();
                $classes = array_merge($intvals, $v2);
            }
        }
        return $classes;
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
        $old_count = count($old);
        if ($old_count == 0) {
            $course_id = [intval($new['course_id'])];
        }else{
            if ($old['course_id'] == $new['course_id']) {
                $course_id = [intval($new['course_id'])];
            }else{
                $course_id = [intval($old['course_id']), intval($new['course_id'])];
            }
        }
        return $course_id;
    }
    // end function get name and value attribute
}
