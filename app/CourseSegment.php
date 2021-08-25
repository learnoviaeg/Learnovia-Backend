<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CourseSegment extends Model
{
    protected $fillable = ['course_id', 'segment_class_id', 'is_active', 'letter', 'letter_id', 'start_date', 'end_date'];

    public static function GetCoursesByCourseSegment($user_id)
    {
        $check = self::where('id', $user_id);
        return $check;
    }

    public static function GetCourseSegmentId($segment_class_id)
    {
        $check = self::where('segment_class_id', $segment_class_id)->pluck('id');
        return $check;
    }

    public static function GetCoursesBysegment_class($user_id)
    {
        $check = self::where('segment_class_id', $user_id);
        return $check;
    }

    public static function getidfromcourse($course_id)
    {
        return self::where('course_id', $course_id)->pluck('id');
    }

    public static function getActive_segmentfromcourse($course_id)
    {
        return self::where('course_id', $course_id)->where('is_active', '1')->pluck('id')->first();
    }

    public function courses()
    {
        return $this->hasMany('App\Course', 'id', 'course_id');
    }

    public function optionalCourses()
    {
        return $this->hasMany('App\Course', 'id', 'course_id')->whereMandatory(0);
    }

    public function users()
    {
        return $this->hasMany('App\User');
    }

    public function segmentClasses()
    {
        return $this->hasMany('App\SegmentClass', 'id', 'segment_class_id');
    }

    public function lessons()
    {
        return $this->hasMany('App\Lesson' , 'course_segment_id' , 'id')->orderBy('index');
    }

    public function Enroll()
    {
        return $this->hasMany('App\Enroll', 'course_segment', 'id');
    }
    public function teachersEnroll()
    {
        return $this->hasMany('App\Enroll', 'course_segment', 'id')->where('role_id', 4);
    }

    public static function checkRelation($segmentClass, $course)
    {
        $courseSegment = self::whereCourse_id($course)->whereSegment_class_id($segmentClass)->first();
        if ($courseSegment == null) {
            $courseSegment = self::create([
                'course_id' => $course,
                'segment_class_id' => $segmentClass,
            ]);
        }
        return $courseSegment;
    }

    Public function GradeCategory()
    {
        return $this->hasMany('App\GradeCategory');
    }

    Public static function GradeCategoryPerSegmentbyId($id)
    {
        $GradeCategoriesInSegment = self::find($id)->with('GradeCategory')->first();
        foreach ($GradeCategoriesInSegment->GradeCategory as $cat) {
            $cat->Child;
        }
        return $GradeCategoriesInSegment;
    }

    protected $hidden = [
        'created_at', 'updated_at'
    ];

    public static function GetWithClassAndCourse($class_id, $course_id)
    {
        return CourseSegment::Join('segment_classes', 'segment_classes.id', 'course_segments.segment_class_id')
            ->Join('class_levels', 'class_levels.id', 'segment_classes.class_level_id')
            ->where('class_levels.class_id', '=', $class_id)
            ->where('course_segments.course_id', '=', $course_id)
            ->where('course_segments.is_active', '=', 1)
            ->first(['course_segments.id', 'course_segments.course_id']);
    }
    public static function GetWithClass($class_id ){
        return CourseSegment::Join('segment_classes' , 'segment_classes.id' , 'course_segments.segment_class_id')
            ->Join('class_levels' , 'class_levels.id' , 'segment_classes.class_level_id')
            ->where('class_levels.class_id' , '=' , $class_id)
            ->where('course_segments.is_active' , '=' , 1)
            ->first(['course_segments.id' , 'course_segments.course_id']);
    }
}
