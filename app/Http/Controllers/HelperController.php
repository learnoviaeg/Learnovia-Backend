<?php

namespace App\Http\Controllers;

use App\AcademicYear;
use App\Segment;
use App\AcademicYearType;
use App\YearLevel;
use App\ClassLevel;
use App\SegmentClass;
use App\CourseSegment;
use Validator;

class HelperController extends Controller
{
    /**
     * response formate
     *
     * @param  [array/object] body
     * @param  [int] $code
     * @param  [string] message
     * @return [objects] all params
    */
    public static function api_response_format($code, $body = [], $message = [])
    {
        return response()->json([
            'message' => $message,
            'body' => $body
        ], $code);
    }

    /**
     * NOT Found
     *
     * @return [string] NOTFound, [] body, 404
    */
    public static function NOTFOUND()
    {
        return response()->json([
            'message' => 'NotFOund',
            'body' => []
        ], 404);
    }

    /**
     * get paginate
     *
     * @param  [int] paginate
     * @return [int] if there is no paginate by default return 10
    */
    public static function GetPaginate($request)
    {
        $request->validate([
            'paginate' => 'integer',
        ]);

        if($request->filled('paginate')){
            return $request->paginate;
        }
        return 10 ;
    }

    /**
     * get segment class
     *
     * @param  [int] year, course, type, level, class, segment
     * @return [objects] segment classes in this tree
    */
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

        if ($request->filled('year'))
            $year = $request->year ;
        else
        {
            $year = AcademicYear::Get_current();
            if($year == null)
                return null;
            else
                $year=$year->id;
        }

        if ($request->filled('segment'))
            $segment = $request->segment;
        else
        {
            $segment = Segment::Get_current($request->type);
            if($segment == null)
                return null;
            else
                $segment=$segment->id;
        }

        $academic_year_type = AcademicYearType::checkRelation($year, $request->type);
        $year_level = YearLevel::checkRelation($academic_year_type->id, $request->level);
        $class_level = ClassLevel::checkRelation($request->class, $year_level->id);
        $segment_class = SegmentClass::checkRelation($class_level->id, $segment);
        return $segment_class;
    }
}
