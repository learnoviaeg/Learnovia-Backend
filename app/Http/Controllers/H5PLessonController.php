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
}
