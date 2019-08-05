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

        $request->validate([
            'year' => 'exists:academic_years,id',
            'type' => 'required|exists:academic_types,id',
            'level' => 'required|exists:levels,id',
            'class' => 'required|exists:classes,id',
            'segment' => 'exists:segments,id',
        ]);
        if ($request->year) {
            $academic_year_type = AcademicYearType::checkRelation($request->year, $request->type);
        }
        else
        {
            $academic_year_type = AcademicYear::Get_current();
        }
        $year_level = YearLevel::checkRelation($academic_year_type->id, $request->level);
        $class_level = ClassLevel::checkRelation($request->class, $year_level->id);
        if ($request->segment) {
            $segment_class = SegmentClass::checkRelation($class_level->id, $request->segment);
        }
        else
        {
            $segment_class = Segment::Get_current();
        }
            $course_segment = CourseSegment::where('segment_class_id', $segment_class->id)->get();
        return $course_segment;
    }
}
