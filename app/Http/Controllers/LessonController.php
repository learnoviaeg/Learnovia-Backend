<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Lesson;
class LessonController extends Controller
{
    public function addLesson(Request $request)
    {
        //Abdulwahab AddLesson
    //     $request->validate([
    //         'name' => 'required',
    //         'index' => 'required',
    //         'course_segment_id' => 'required|exists:course_segments,id',
    //     ]);
        
    //     $lesson = Lesson::create([
    //         'name' => $request->name,
    //         'index' => $request->index,
    //         'course_segment_id' => $request->course_segment_id,
            
    //     ]);
    //     return HelperController::api_response_format(201, $lesson, 'lesson Created Successfully');
     }
}
