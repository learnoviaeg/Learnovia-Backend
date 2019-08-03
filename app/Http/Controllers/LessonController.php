<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Lesson;
use App\Course;
use App\CourseSegment;

class LessonController extends Controller
{

    public function AddLesson(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'course_id'=>'required|exists:courses,id'
        ]);
        $Lessons_array = array();

        $course_segment = CourseSegment::getActive_segmentfromcourse($request->course_id);

        if($course_segment){
        array_push($Lessons_array,$request['name']);
        $lessons_in_CourseSegment = Lesson::where('course_segment_id', $course_segment)->max('index');
                $Next_index = $lessons_in_CourseSegment + 1;

        foreach ($Lessons_array as $input) {
            if(count($input)==1){
                $lesson = Lesson::create([
                    'name' => $input['0'],
                    'course_segment_id' => $course_segment,
                    'index' => $Next_index
                ]);
            }else{
            for ($x =0; $x <= (count($Lessons_array)+1); $x++) {
                $lesson = Lesson::create([
                    'name' => $input[$x],
                    'course_segment_id' => $course_segment,
                    'index' => $Next_index
                ]);

            }
        }
        }
        return HelperController::api_response_format(201, $lesson, 'Lesson is Created Successfully');
    }
    else{
        return HelperController::api_response_format(201, 'No Segment is allowed');

    }
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
            'course_id' => 'exists:courses,id',
        ]);
        $lesson = Lesson::find($request->id);
        $lesson->name = $request->name;
        if ($request->filled('course_id')) {
            $course_segment = CourseSegment::getidfromcourse($request->course_id);
            $lesson->course_segment_id = $course_segment;
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
