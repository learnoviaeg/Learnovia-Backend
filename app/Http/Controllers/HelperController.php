<?php

namespace App\Http\Controllers;
use App\AcademicYearType;
use App\ClassLevel;
use App\Course;
use App\CourseSegment;
use App\Lesson;
use App\SegmentClass;
use App\YearLevel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class HelperController extends Controller
{
    public static function api_response_format($code, $body = [], $message = [])
    {
        return response()->json([
            'message' => $message,
            'body' => $body
        ], $code);
    }
    public static function get_course_by_any(Request $request,$key)
    {
        $alldata=HelperController::get_course_by_year_type($request,$key);
        return $alldata;
    }
    public static function get_course_by_year_type(Request $request,$key)
    {

        $academic_year_type=array();
        $academic_year_type=null;
        if(isset($request->type_id[$key]) && !isset($request->year_id[$key]))
        {
            $academic_year_type[]=AcademicYearType::where('academic_type_id',$request->type_id[$key])->pluck('id');
        }
        else if(!isset($request->type_id[$key]) && isset($request->year_id[$key]))
        {
            $academic_year_type[]=AcademicYearType::where('academic_year_id',$request->year_id[$key])->pluck('id');
        }
        else if(isset($request->type_id[$key]) && isset($request->year_id[$key]))
        {
            $academic_year_type[] = AcademicYearType::checkRelation($request->year_id[$key], $request->type_id[$key])->id;
        }
        $alldata['academic_year_type']=$academic_year_type;
        return HelperController::get_year_level($request,$academic_year_type,$alldata,$key);

    }

    public static function get_year_level(Request $request ,$academic_year_type,$alldata,$key)
    {
        $year_level=array();
        $year_level=null;
        if(isset($request->level_id[$key]) && !isset($academic_year_type))
        {
            $year_level[]=YearLevel::where('level_id',$request->level_id[$key])->pluck('id');
        }
        else if(!isset($request->level_id[$key]) && isset($academic_year_type))
        {
            foreach($academic_year_type as $ac)
            {
                $year_level[]=YearLevel::where('academic_year_type_id',$ac)->pluck('id');
            }
        }
        else if(isset($request->level_id[$key]) && isset($academic_year_type))
        {
            foreach($academic_year_type as $ac)
            {
                $year_level[]=YearLevel::checkRelation($ac,$request->level_id[$key])->id;
            }
        }
        $alldata['year_level']=$year_level;
        return HelperController::get_class_level($request,$year_level,$alldata,$key);
    }

    public static function get_class_level(Request $request ,$year_level,$alldata,$key)
    {
        $class_level=array();
        $class_level=null;
        if(isset($request->class_id[$key]) && !isset($year_level))
        {
            $class_level[]=ClassLevel::where('class_id',$request->class_id[$key])->pluck('id');
        }
        else if(!isset($request->class_id[$key]) && isset($year_level))
        {
            foreach($year_level as $ac)
            {
                $class_level[]=ClassLevel::where('year_level_id',$ac)->pluck('id');
            }
        }
        else if(isset($request->class_id[$key]) && isset($year_level))
        {
            foreach($year_level as $ac)
            {
                $class_level[]=ClassLevel::checkRelation($request->class_id[$key],$ac)->id;
            }
        }
        $alldata['class_level']=$class_level;
        return HelperController::get_segment_class_level($request,$class_level,$alldata,$key);
    }

    public static function get_segment_class_level(Request $request ,$class_level,$alldata,$key)
    {
        $segment_class=array();
        $segment_class=null;
        if(isset($request->segment_id[$key]) && !isset($class_level))
        {
            $segment_class[]=SegmentClass::where('segment_id',$request->segment_id[$key])->pluck('id');
        }
        else if(!isset($request->segment_id[$key]) && isset($class_level))
        {
            foreach($class_level as $ac)
            {
                $segment_class[]=SegmentClass::where('class_level_id',$ac)->pluck('id');
            }
        }
        else if(isset($request->segment_id[$key]) && isset($class_level))
        {
            foreach($class_level as $ac)
            {
                $segment_class[]=SegmentClass::checkRelation($ac,$request->segment_id[$key])->id;
            }
        }
        $alldata['segment_class']=$segment_class;
        return HelperController::get_course_segment_level($segment_class,$alldata,$key);
    }

    public static function get_course_segment_level($segment_class,$alldata,$key)
    {
        $course_segment=array();
        $course_segment=null;
        if(isset($segment_class))
        {
            foreach ($segment_class as $sc)
            {
                $course_segment[]=CourseSegment::where('segment_class_id',$sc)->pluck('course_id');
            }
        }
        $alldata['course_segment']=$course_segment;
        return $alldata;
    }


    public static function NOTFOUND()
    {
        return response()->json([
            'message' => 'NotFOund',
            'body' => []
        ], 404);
    }
}
