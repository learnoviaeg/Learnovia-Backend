<?php

namespace App\Http\Controllers;

use App\Course;
use App\CourseSegment;
use App\SegmentClass;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    public function add(Request $request){
        $request->validate([
            'name' => 'required',
            'category' => 'required|exists:categories,id',
            'segment_class_id' => 'required|exists:segment_classes,id'
        ]);

        $course = Course::create([
            'name' => $request->name ,
            'category_id' => $request->category,
        ]);
        CourseSegment::create([
            'course_id' => $course->id,
            'segment_class_id' => $request->segment_class_id
        ]);
        return HelperController::api_response_format(201 , $course , 'Course Created Successfully');
    }
    public function update(Request $request){
        $request->validate([
            'name' => 'required',
            'category' => 'required|exists:categories,id',
            'id' => 'required|exists:courses,id'
        ]);

        $course = Course::find($request->id);
        $course->name = $request->name;
        $course->category_id = $request->category;
        $course->save();
        return HelperController::api_response_format(200 , $course , 'Course Updated Successfully');
    }
    public function get(Request $request){
        $request->validate([
            'id' => 'exists:courses,id'
        ]);
        if (isset($request->id))
            return HelperController::api_response_format(200 , Course::find($request->id));
        return HelperController::api_response_format(200 , Course::all());
    }
    public function delete(Request $request){
        $request->validate([
            'id' => 'required|exists:courses,id'
        ]);
        $course = Course::find($request->id);
        $course->delete();
        return HelperController::api_response_format(200 , $course , 'Course Updated Successfully');
    }
}
