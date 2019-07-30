<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;
use App\Enroll;
use App\Lesson;
use App\Component;

class CalendarController extends Controller
{
    public function Calendar (Request $request)
    {
        $user_id=Auth::user()->id;
        $CourseSeg=Enroll::where('user_id',$user_id)->pluck('course_segment');

        $Lessons=array();
        foreach ($CourseSeg as $cour) {
            $checkLesson=Lesson::where('course_segment_id',$cour)->get();
            if($checkLesson!=null)
            {
                $Lessons[]=$checkLesson;
            }
        }
        $comp=Component::where('type',1)->get();
        foreach($Lessons as $less)
        {
            foreach($less as $les)
            {
                foreach($comp as $com)
                {
                    $les[$com->name]= $les->module($com->module,$com->model)->get();
                }

            }

        }
        return $Lessons;

    }


}
