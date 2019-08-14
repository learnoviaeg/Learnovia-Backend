<?php

namespace App\Http\Controllers;

use App\AcademicYear;
use App\Segment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\AcademicYearType;
use App\YearLevel;
use App\ClassLevel;
use App\SegmentClass;
use App\CourseSegment;
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
            'segment' => 'exists:segments,id',
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails())
            return ['result' => false, 'value' => $validator->errors()];
        $year = AcademicYear::Get_current()->id;
        $segment = Segment::Get_current()->id;
        if ($request->filled('year')) {
            $year = $request->year;
        }
        if ($request->filled('segment')) {
            $segment = $request->segment;
        }
        $academic_year_type = AcademicYearType::checkRelation($year, $request->type);
        $year_level = YearLevel::checkRelation($academic_year_type->id, $request->level);
        $class_level = ClassLevel::checkRelation($request->class, $year_level->id);
        $segment_class = SegmentClass::checkRelation($class_level->id, $segment);
        $course_segment = CourseSegment::where('segment_class_id', $segment_class->id)->get();
        return ['result' => true, 'value' => $course_segment];
    }

    public static function Get_Course_segment_Course($request)
    {
        $rules = [
            'year' => 'exists:academic_years,id',
            'type' => 'exists:academic_types,id',
            'level' => '|exists:levels,id',
            'class' => 'required|exists:classes,id',
            'segment' => 'exists:segments,id',
            'course' => 'required|exists:courses,id',
        ];
        if(!$request->filled('year') && !$request->filled('type') && !$request->filled('level') && !$request->filled('segment')){
            return ['result' => true, 'value' => CourseSegment::GetWithClassAndCourse($request->class , $request->course)];
        }
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails())
            return ['result' => false, 'value' => $validator->errors()];
        $year = AcademicYear::Get_current()->id;
        $segment = Segment::Get_current()->id;
        if ($request->filled('year')) {
            $year = $request->year;
        }
        if ($request->filled('segment')) {
            $segment = $request->segment;
        }
        $academic_year_type = AcademicYearType::checkRelation($year, $request->type);
        $year_level = YearLevel::checkRelation($academic_year_type->id, $request->level);
        $class_level = ClassLevel::checkRelation($request->class, $year_level->id);
        $segment_class = SegmentClass::checkRelation($class_level->id, $segment);
        $course_segment = CourseSegment::checkRelation($segment_class->id, $request->course);
        return ['result' => true, 'value' => $course_segment];
    }

    public static function Get_class_LEVELS($request)
    {
        $academic_year_type = AcademicYearType::checkRelation($request->year,$request->type);
        $year_level = YearLevel::checkRelation($academic_year_type->id, $request->level);
        $class_level = ClassLevel::checkRelation($request->class ,$year_level->id);
        return $class_level;
    }

    public static function GetPaginate($request)
    {
        if($request->filled('paginate')){
            return $request->paginate;
        }
        return 10 ;
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
