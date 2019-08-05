<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Lesson;
use App\Course;
use App\CourseSegment;
use App\attachment;

class LessonController extends Controller
{

    public function AddLesson(Request $request)
    {
        $request->validate([
            'name' => 'required|array',
            'name.*' => 'required|string',
            'image' => 'array',
            'image.*' => 'mimes:jpeg,jpg,png,gif|max:10000',
            'description' => 'array',
            'description.*' => 'string'
        ]);
        $segments = HelperController::Get_Course_segment_Course($request);
        if (!$segments['result'])
            return HelperController::api_response_format(400, $segments['value']);
        foreach ($request->name as $key => $name) {
            //dd($request->image[$key]);
            $lessons_in_CourseSegment = Lesson::where('course_segment_id', $segments['value']->id)->max('index');
            $Next_index = $lessons_in_CourseSegment + 1;
            $lesson = Lesson::create([
                'name' => $name,
                'course_segment_id' => $segments['value']->id,
                'index' => $Next_index
            ]);
            if (isset($request->image[$key])) {
                $lesson->image = attachment::upload_attachment($request->image[$key], 'lesson', '')->path;
            }
            if (isset($request->description[$key])) {
                $lesson->description = $request->description[$key];
            }
            $lesson->save();
        }
        return HelperController::api_response_format(200, $segments['value']->lessons);
    }


    public function ShowLesson(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:lessons,id',
        ]);
        $lesson = Lesson::find($request->id);
        return HelperController::api_response_format(200, $lesson);
    }


    public function deleteLesson(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:lessons,id',

        ]);
        $lesson = Lesson::find($request->id);
        $lesson->delete();
        $lessons = Lesson::whereCourse_segment_id($lesson->course_segment_id)->where('index', '>', $lesson->index)->get();
        foreach ($lessons as $temp) {
            $temp->index = $temp->index - 1;
            $temp->save();
        }
        return HelperController::api_response_format(200, null, 'Lesson is deleted Successfully');
    }


    public function updateLesson(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'id'  => 'required|exists:lessons,id',
        ]);
        $lesson = Lesson::find($request->id);
        $lesson->name = $request->name;
        $lesson->save();

        return HelperController::api_response_format(200, $lesson, 'Lesson is updated Successfully');
    }


    public function sortDown($lesson_id, $index)
    {
        $lesson_index = Lesson::where('id', $lesson_id)->pluck('index')->first();
        $all_lessons = Lesson::Get_lessons_per_CourseSegment_from_lessonID($lesson_id);
        foreach ($all_lessons as $lesson) {
            if ($lesson->index > $lesson_index || $lesson->index < $index) {
                continue;
            }
            if ($lesson->index  !=  $lesson_index) {
                $lesson->update([
                    'index' => $lesson->index + 1
                ]);
            } else {
                $lesson->update([
                    'index' => $index
                ]);
            }
        }
        return $all_lessons;
    }
    public function SortUp($lesson_id, $index)
    {

        $lesson_index = Lesson::where('id', $lesson_id)->pluck('index')->first();
        $all_lessons = Lesson::Get_lessons_per_CourseSegment_from_lessonID($lesson_id);
        foreach ($all_lessons as $lesson) {
            if ($lesson->index > $index || $lesson->index < $lesson_index) {
                continue;
            } elseif ($lesson->index  !=  $lesson_index) {
                $lesson->update([
                    'index' => $lesson->index - 1
                ]);
            } else {
                $lesson->update([
                    'index' => $index
                ]);
            }
        }
        return $all_lessons;
    }




    public function Sorting(Request $request)
    {
        $request->validate([
            'lesson_id' => 'required|integer|exists:lessons,id',
            'index' => 'required|integer'
        ]);
        $lesson_index = Lesson::where('id', $request->Lesson_id)->pluck('index')->first();

        if ($lesson_index > $request->index) {
            $lessons = $this->sortDown($request->lesson_id, $request->index);
        } else {
            $lessons = $this->SortUp($request->lesson_id, $request->index);
        }
        return HelperController::api_response_format(200, $lessons, ' Successfully');
    }
}
