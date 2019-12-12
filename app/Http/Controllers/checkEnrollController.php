<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;
use App\Enroll;

class checkEnrollController extends Controller
{   /*
    * @Description :check if user is enrolled in this course segment.
    * @param : course segment id.
    * @return : returns true or false.
    */
    public static function checkEnrollment($course_segment_id){
        $checkEnroll = Enroll::where('user_id', Auth::user()->id)
                            ->where('course_segment', $course_segment_id)
                            ->exists();
        return $checkEnroll;
    }
  /*
    * @Description :check if user is enrolled in this course segment and of role id equal 4.
    * @param : course segment.
    * @return : returns true or false.
    */
    public static function checkEnrollmentAuthorization($courseSegment){
        $checkTeacherEnroll = Enroll::where('user_id', Auth::user()->id)
        ->where('course_segment', $courseSegment)
        ->where('role_id', 4)
        ->exists();
        return $checkTeacherEnroll;
    }
}
