<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\AcademicYearType;
use App\YearLevel;
use App\ClassLevel;
use App\SegmentClass;
use App\CourseSegment;

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
        $academic_year_type = AcademicYearType::checkRelation($request->year,$request->type);
        $year_level = YearLevel::checkRelation($academic_year_type->id, $request->level);
        $class_level = ClassLevel::checkRelation($request->class ,$year_level->id);
        $segment_class = SegmentClass::checkRelation($class_level->id, $request->segment);
        $course_segment= CourseSegment::where( 'segment_class_id',$segment_class->id)->get();
        return $course_segment;
    }

    public static function Get_class_LEVELS($request)
    {
        $academic_year_type = AcademicYearType::checkRelation($request->year,$request->type);
        $year_level = YearLevel::checkRelation($academic_year_type->id, $request->level);
        $class_level = ClassLevel::checkRelation($request->class ,$year_level->id);
        return $class_level;
    }
}
