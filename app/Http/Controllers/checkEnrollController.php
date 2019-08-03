<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;
use App\Enroll;
use App\User;
use App\Lesson;


class checkEnrollController extends Controller
{
    public static function checkEnrollment($course_segment_id){
        $checkEnroll = Enroll::where('user_id', Auth::user()->id)
                            ->where('course_segment', $course_segment_id)
                            ->exists();
        return $checkEnroll;
    }

    public static function checkEnrollmentAuthorization($courseSegment){
        $checkTeacherEnroll = Enroll::where('user_id', Auth::user()->id)
        ->where('course_segment', $courseSegment)
        ->where('role_id', 4)
        ->exists();

        return $checkTeacherEnroll;
    }


    public static function userNotifyEnrollment($lesson_id){
        $lesson = Lesson::find($lesson_id);
        $lesson->courseSegment->enrolls;
        $users = $lesson->courseSegment->enrolls->where('role_id' , 4);
        foreach($users as $user){
            User::notify([
                'message' => 'New File added',
                'from' => Auth::user()->id,
                'to' => $user->id,
                'course_id' => $lesson->courseSegment->courses[0]->id,
                'type' => 'annoucn'
            ]);
        }
    }
}
