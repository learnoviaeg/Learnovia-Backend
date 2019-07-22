<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use DB;

class Enroll extends Model
{
    protected $fillable = ['user_id', 'username', 'course_segment', 'role_id', 'start_date', 'end_date'];

    public static function getroleid($user_id, $course_segment)
    {
        return self::where('user_id', $user_id)->where('course_segment', $course_segment)->pluck('role_id')->first();
    }

    public static function IsExist($course_segment_id, $user_id)
    {
        $check = self::where('course_segment', $course_segment_id)->where('user_id', $user_id)->pluck('id')->first();
        return $check;
    }

    public static function FindUserbyID($user_id, $course_segment)
    {
        $check = self::where('user_id', $user_id)->where('course_segment', $course_segment);
        return $check;
    }

    public static function GetCourseSegment($user_id)
    {
        $check = self::where('user_id', $user_id)->pluck('course_segment');
        return $check;
    }

    protected $hidden = [
        'created_at', 'updated_at'
    ];

    public function courseSegment()
    {
        return $this->belongsTo('App\CourseSegment', 'course_segment');
    }

    public static function Get_User_ID($Course_segment_id)
    {
        $check = self::where('course_segment', $Course_segment_id)->pluck('user_id');
        return $check;
    }
}