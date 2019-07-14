<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CourseSegment extends Model
{
    protected $fillable = ['course_id' , 'segment_class_id'];

    public static function GetCoursesByCourseSegment ($user_id)
    {
     $check = self::where('id', $user_id);
     return $check;
 
    }
    public static function GetCoursesBysegment_class ($user_id)
    {
     $check = self::where('segment_class_id', $user_id);
     return $check;
 
    }

    public function courses()
    {
        return $this->hasMany('App\Course','id','course_id');
    } 

    public static function getidfromcourse($course_id)
    {
        return self::where('course_id', $course_id)->pluck('id')->first();
    
    }
}
