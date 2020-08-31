<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\h5pLesson;
use Carbon\Carbon;

class H5PLessonController extends Controller
{
    public function create (Request $request)
    {
        $request->validate([
            'content_id' => 'required|exists:h5p_contents,id',
            'lesson_id' => 'required|exists:lessons,id'
        ]);
        
        $h5p_lesson = h5pLesson::where('content_id',$request->content_id)->where('lesson_id',$request->lesson_id)->first();
        if(!isset($h5p_lesson)){
            $h5p_lesson = h5pLesson::firstOrCreate([
                'content_id' => $request->content_id,
                'lesson_id' => $request->lesson_id,
                'publish_date' => Carbon::now(),
                'start_date' => Carbon::now()
            ]);
        }
        
        return HelperController::api_response_format(200,$h5p_lesson, 'Interactive content added successfully');
    }

    public function toggleVisibility(Request $request)
    {
        return $request->url();
        $request->validate([
            'content_id' => 'required|exists:h5p_contents,id',
            'lesson_id' => 'required|integer|exists:h5p_lessons,lesson_id',
        ]);

        $h5pLesson = h5pLesson::where('content_id', $request->content_id)->where('lesson_id', $request->lesson_id)->first();
        if (!isset($h5pLesson)) {
            return HelperController::api_response_format(400, null, 'Try again , Data invalid');
        }
        $h5pLesson->visible = ($h5pLesson->visible == 1) ? 0 : 1;
        $h5pLesson->save();
        return HelperController::api_response_format(200, $h5pLesson, 'Content toggled successfully');
    }
}
