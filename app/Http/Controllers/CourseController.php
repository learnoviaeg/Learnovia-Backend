<?php

namespace App\Http\Controllers;

use App\AcademicYearType;
use App\ClassLevel;
use App\Course;
use App\CourseSegment;
use App\SegmentClass;
use App\YearLevel;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    public static function add(Request $request)
    {
        $request->validate([
            'name'      => 'required',
            'category'  => 'required|exists:categories,id',
            'year'      => 'required|exists:academic_years,id',
            'type'      => 'required|exists:academic_types,id',
            'level'     => 'required|exists:levels,id',
            'class'     => 'required|exists:classes,id',
            'segment'   => 'required|exists:segments,id',
        ]);

        $course_id=Course::findByName($request->name);
        if($course_id){
            return HelperController::api_response_format(201, $course_id, 'Course already exist');
        }
        else {
            $course = Course::create([
                'name' => $request->name,
                'category_id' => $request->category,
            ]);
            $yeartype = AcademicYearType::checkRelation($request->year, $request->type);
            $yearlevel = YearLevel::checkRelation($yeartype->id, $request->level);
            $classLevel = ClassLevel::checkRelation($request->class, $yearlevel->id);
            $segmentClass = SegmentClass::checkRelation($classLevel->id , $request->segment);
            CourseSegment::create([
                'course_id' => $course->id,
                'segment_class_id' => $segmentClass->id
            ]);
            return HelperController::api_response_format(201, $course, 'Course Created Successfully');
        }
    }

    public function update(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'category' => 'required|exists:categories,id',
            'id' => 'required|exists:courses,id'
        ]);

        $course = Course::find($request->id);
        $course->name = $request->name;
        $course->category_id = $request->category;
        $course->save();
        return HelperController::api_response_format(200, $course, 'Course Updated Successfully');
    }

    public function get(Request $request)
    {
        $request->validate([
            'id' => 'exists:courses,id'
        ]);
        if (isset($request->id))
            return HelperController::api_response_format(200, Course::find($request->id));
        return HelperController::api_response_format(200, Course::all());
    }

    public function delete(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:courses,id'
        ]);
        $course = Course::find($request->id);
        $course->delete();
        return HelperController::api_response_format(200, $course, 'Course Updated Successfully');
    }
}
