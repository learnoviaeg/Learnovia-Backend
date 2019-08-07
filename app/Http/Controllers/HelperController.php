<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\AcademicYearType;
use App\YearLevel;
use App\ClassLevel;
use App\SegmentClass;
use App\AcademicYear;
use App\CourseSegment;
use App\Segment;
use Validator;

class HelperController extends Controller
{
    public static function api_response_format($code, $body = [], $message = [])
    {
        return response()->json([
            'message' => $message,
            'body' => $body
        ], $code);
    }

    public static function NOTFOUND()
    {
        return response()->json([
            'message' => 'NotFOund',
            'body' => []
        ], 404);
    }

    public static function Get_Course_segment($request)
    {
        $rules = [
            'year' => 'exists:academic_years,id',
            'type' => 'required|exists:academic_types,id',
            'level' => 'required|exists:levels,id',
            'class' => 'required|exists:classes,id',
            'segment' => 'exists:segments,id'
        ];
        $validator = Validator::make($request->all() , $rules);
        if($validator->fails())
            return $validator->errors();
        $year = AcademicYear::Get_current()->id ;
        $segment = Segment::Get_current()->id;
        if ($request->filled('year')) {
            $year = $request->year ;
        }
        if ($request->filled('segment')) {
            $segment = $request->segment ;
        }
        $academic_year_type = AcademicYearType::checkRelation($year, $request->type);
        $year_level = YearLevel::checkRelation($academic_year_type->id, $request->level);
        $class_level = ClassLevel::checkRelation($request->class, $year_level->id);
        $segment_class = SegmentClass::checkRelation($class_level->id, $segment);
        $course_segment = CourseSegment::where('segment_class_id', $segment_class->id)->get();
        return $course_segment;
    }

    public static function Get_Course_segment_By_Course($request)
    {
        $rules = [
            'year' => 'exists:academic_years,id',
            'type' => 'required|exists:academic_types,id',
            'level' => 'required|exists:levels,id',
            'class' => 'required|exists:classes,id',
            'segment' => 'exists:segments,id',
            'course' => 'exists:courses,id'
        ];
        $validator = Validator::make($request->all() , $rules);

        if($validator->fails())
            return $validator->errors();
        $year = AcademicYear::Get_current()->id ;
        $segment = Segment::Get_current()->id;
        if ($request->filled('year')) {
            $year = $request->year ;
        }
        if ($request->filled('segment')) {
            $segment = $request->segment ;
        }
        $academic_year_type = AcademicYearType::checkRelation($year, $request->type);
        $year_level = YearLevel::checkRelation($academic_year_type->id, $request->level);
        $class_level = ClassLevel::checkRelation($request->class, $year_level->id);
        $segment_class = SegmentClass::checkRelation($class_level->id, $segment);
        $course_segment = CourseSegment::where('segment_class_id', $segment_class->id)->where('course_id',$request->course)->pluck('id');
        return $course_segment;
    }

    public static function Get_segment_class($request)
    {
        $rules = [
            'year' => 'exists:academic_years,id',
            'type' => 'required|exists:academic_types,id',
            'level' => 'required|exists:levels,id',
            'class' => 'required|exists:classes,id',
            'segment' => 'exists:segments,id',
        ];
        $validator = Validator::make($request->all() , $rules);
        if($validator->fails())
            return $validator->errors();
        $year = AcademicYear::Get_current()->id ;
        $segment = Segment::Get_current()->id;
        if ($request->filled('year')) {
            $year = $request->year ;
        }
        if ($request->filled('segment')) {
            $segment = $request->segment ;
        }
        $academic_year_type = AcademicYearType::checkRelation($year, $request->type);
        $year_level = YearLevel::checkRelation($academic_year_type->id, $request->level);
        $class_level = ClassLevel::checkRelation($request->class, $year_level->id);
        $segment_class = SegmentClass::checkRelation($class_level->id, $segment);
        return $segment_class;
    }
}
