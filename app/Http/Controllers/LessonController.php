<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Lesson;
use App\CourseSegment;

class LessonController extends Controller
{

    public function AddLesson(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'course_segment_id' => 'required|exists:course_segments,id',
        ]);
        $Lessons_array = array();
        array_push(
            $Lessons_array,
            array(
                'name' =>  $request['name'],
                'course_segment_id' => $request['course_segment_id'],
            )
        );

        foreach ($Lessons_array as $input) {
            for ($x = 0; $x < count($input); $x++) {
                $lessons_in_CourseSegment = Lesson::where('course_segment_id', $request->course_segment_id)->max('index');
                $Next_index = $lessons_in_CourseSegment + 1;
                $lesson = Lesson::create([
                    'name' => $input['name'][$x],
                    'course_segment_id' => $input['course_segment_id'][$x],
                    'index' => $Next_index
                ]);
            }
        }
        return HelperController::api_response_format(201, $lesson, 'Lesson is Created Successfully');
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
        return HelperController::api_response_format(200, null, 'Lesson is deleted Successfully');
    }


    public function updateLesson(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'id'  => 'required|exists:lessons,id',
            'course_segment_id' => 'exists:course_segments,id',
        ]);
        $lesson = Lesson::find($request->id);
        $lesson->name = $request->name;
        if ($request->filled('course_segment_id')) {
            $lesson->course_segment_id = $request->course_segment_id;
        }
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
