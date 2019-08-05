<?php

namespace App\Http\Controllers;

use App\AcademicYearType;
use App\ClassLevel;
use App\Course;
use App\CourseSegment;
use App\Lesson;
use App\SegmentClass;
use App\Component;
use App\YearLevel;
use Illuminate\Http\Request;
use App\Enroll;

class CourseController extends Controller
{
    public static function add(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'category' => 'required|exists:categories,id',
            'year' => 'required|exists:academic_years,id',
            'type' => 'required|exists:academic_types,id',
            'level' => 'required|exists:levels,id',
            'class' => 'required|exists:classes,id',
            'segment' => 'required|exists:segments,id',
            'no_of_lessons' => 'integer'
        ]);
        $no_of_lessons = 4;
        $course = Course::create([
            'name' => $request->name,
            'category_id' => $request->category,
        ]);
        $yeartype = AcademicYearType::checkRelation($request->year, $request->type);
        $yearlevel = YearLevel::checkRelation($yeartype->id, $request->level);
        $classLevel = ClassLevel::checkRelation($request->class, $yearlevel->id);
        $segmentClass = SegmentClass::checkRelation($classLevel->id, $request->segment);
        $courseSegment = CourseSegment::create([
            'course_id' => $course->id,
            'segment_class_id' => $segmentClass->id,
            'is_active' => 1
        ]);
        if ($request->filled('no_of_lessons')) {
            $no_of_lessons = $request->no_of_lessons;
        }

        for ($i = 1; $i <= $no_of_lessons; $i++) {
            $courseSegment->lessons()->create([
                'name' => 'Lesson ' . $i,
                'index' => $i,
            ]);
        }
        return HelperController::api_response_format(201, $course, 'Course Created Successfully');
    }

    public function update(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'category' => 'required|exists:categories,id',
            'id' => 'required|exists:courses,id',
            'year' => 'exists:academic_years,id',
            'type' => 'exists:academic_types,id|required_with:year',
            'level' => 'exists:levels,id|required_with:year',
            'class' => 'exists:classes,id|required_with:year',
            'segment' => 'exists:segments,id|required_with:year',
        ]);

        $course = Course::find($request->id);
        $course->name = $request->name;
        $course->category_id = $request->category;
        $course->save();
        if ($request->filled('year')) {
            $oldyearType = AcademicYearType::checkRelation($course->courseSegments[0]->segmentClasses[0]->segments[0]->Segment_class[0]->classes[0]->classlevel->yearLevels[0]->yearType[0]->academicyear[0]->id, $course->courseSegments[0]->segmentClasses[0]->segments[0]->Segment_class[0]->classes[0]->classlevel->yearLevels[0]->yearType[0]->academictype[0]->id);
            $newyearType = AcademicYearType::checkRelation($request->year, $request->type);

            $oldyearLevel = YearLevel::checkRelation($oldyearType->id, $course->courseSegments[0]->segmentClasses[0]->segments[0]->Segment_class[0]->classes[0]->classlevel->yearLevels[0]->levels[0]->id);
            $newyearLevel = YearLevel::checkRelation($newyearType->id, $request->level);

            $oldClassLevel = ClassLevel::checkRelation($course->courseSegments[0]->segmentClasses[0]->segments[0]->Segment_class[0]->classes[0]->id, $oldyearLevel->id);
            $newClassLevel = ClassLevel::checkRelation($course->courseSegments[0]->segmentClasses[0]->segments[0]->Segment_class[0]->classes[0]->id, $newyearLevel->id);

            $oldsegmentClass = SegmentClass::checkRelation($oldClassLevel->id, $course->courseSegments[0]->segmentClasses[0]->segments[0]->id);
            $newsegmentClass = SegmentClass::checkRelation($newClassLevel->id, $course->courseSegments[0]->segmentClasses[0]->segments[0]->id);

            $oldCourseSegment = CourseSegment::checkRelation($oldsegmentClass->id, $course->id);
            $oldCourseSegment->delete();
            $newCourseSegment = CourseSegment::checkRelation($newsegmentClass->id, $course->id);
        }
        return HelperController::api_response_format(200, $course, 'Course Updated Successfully');
    }

    public function get(Request $request)
    {

        $request->validate([
            'id' => 'exists:courses,id',
            'category_id' => 'nullable|exists:categories,id',
            'year_id' => 'nullable|exists:academic_years,id',
            'type_id' => 'nullable|exists:academic_types,id',
            'level_id' => 'nullable|exists:levels,id',
            'class_id' => 'nullable|exists:classes,id',
            'segment_id' => 'nullable|exists:segments,id',
        ]);
        if (isset($request->id))
        {
            return HelperController::api_response_format(200, Course::with('category')->whereId($request->id)->first());
        }
        else if(isset($request->category_id))
        {
            $courses=Course::where('category_id',$request->category_id)->get();
            return HelperController::api_response_format(200, $courses);
        }
        else {
            $course= CourseController::get_course_by_year_type($request);
            if(!isset($course))
            {
                return HelperController::api_response_format(200, Course::with('category')->get());
            }
            else
            {
                $returncourse=array();
                foreach($course as $cc)
                {
                    $returncourse[]=Course::where('id',$cc)->first();
                }
                return HelperController::api_response_format(200, $returncourse);
            }
        }
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

    public function MyCourses(Request $request)
    {
        $i = 0 ;
        $courses = [];
        foreach ($request->user()->enroll as $enroll) {
            if(in_array($enroll->CourseSegment->courses[0] , $courses))
                continue;
            $courses[$i] = $enroll->CourseSegment->courses[0];
            $courses[$i]['category'] = $enroll->CourseSegment->courses[0]->category;
        }
        return HelperController::api_response_format(200, $courses);
    }
    public function GetUserCourseLessons(Request $request)
    {
        $request->validate([
            'course_id' => 'required|exists:course_segments,course_id'
        ]);
        $CourseSeg=Enroll::where('user_id',$request->user()->id)->pluck('course_segment');
        $seggg=array();
        foreach ($CourseSeg as $cour) {
            $check=CourseSegment::where('course_id',$request->course_id)->where('id',$cour)->pluck('id')->first();
            if($check!=null)
            {
                $seggg[]=$check;
            }
        }
        $CourseSeg=array();
        foreach($seggg as $segggg){
            $CourseSeg[]=CourseSegment::where('id',$segggg)->get();
        }
        $clase=array();
        $lessons=null;
        $i = 0 ;
        $lessoncounter=array();
        $comp=Component::where('type',1)->get();
        foreach($CourseSeg as $seg)
        {
            $lessons= $seg->first()->lessons;
            foreach ($seg->first()->segmentClasses as $key => $segmentClas) {
                # code...
                foreach ($segmentClas->classLevel as $key => $classlev) {
                    # code...
                    foreach ($classlev->classes as $key => $class) {
                        # code...
                        $clase[$i]=$class;
                        $clase[$i]->lessons = $lessons;
                        foreach($clase[$i]->lessons as $lessonn)
                        {
                            $lessoncounter=Lesson::find($lessonn->id);
                            foreach($comp as $com)
                            {
                                $lessonn[$com->name]= $lessoncounter->module($com->module,$com->model)->get();
                            }
                        }
                        $i++;
                    }
                }
            }
        }
        //$clase['course'] = Course::find($request->course_id);
        return HelperController::api_response_format(200 , $clase);
    }


    public function get_course_by_year_type(Request $request)
    {
        $academic_year_type=array();
        $academic_year_type=null;
        if(isset($request->type_id) && !isset($request->year_id))
        {
            $academic_year_type[]=AcademicYearType::where('academic_type_id',$request->type_id)->pluck('id');
        }
        else if(!isset($request->type_id) && isset($request->year_id))
        {
            $academic_year_type[]=AcademicYearType::where('academic_year_id',$request->year_id)->pluck('id');
        }
        else if(isset($request->type_id) && isset($request->year_id))
        {
            $academic_year_type[] = AcademicYearType::checkRelation($request->year_id, $request->type_id)->id;
        }
        return CourseController::get_year_level($request,$academic_year_type);

    }

    public static function get_year_level(Request $request ,$academic_year_type)
    {
        $year_level=array();
        $year_level=null;
        if(isset($request->level_id) && !isset($academic_year_type))
        {
            $year_level[]=YearLevel::where('level_id',$request->level_id)->pluck('id');
        }
        else if(!isset($request->level_id) && isset($academic_year_type))
        {
            foreach($academic_year_type as $ac)
            {
                $year_level[]=YearLevel::where('academic_year_type_id',$ac)->pluck('id');
            }
        }
        else if(isset($request->level_id) && isset($academic_year_type))
        {
            foreach($academic_year_type as $ac)
            {
                $year_level[]=YearLevel::checkRelation($ac,$request->level_id)->id;
            }
        }

        return CourseController::get_class_level($request,$year_level);
    }

    public static function get_class_level(Request $request ,$year_level)
    {
        $class_level=array();
        $class_level=null;
        if(isset($request->class_id) && !isset($year_level))
        {
            $class_level[]=ClassLevel::where('class_id',$request->class_id)->pluck('id');
        }
        else if(!isset($request->class_id) && isset($year_level))
        {
            foreach($year_level as $ac)
            {
                $class_level[]=ClassLevel::where('year_level_id',$ac)->pluck('id');
            }
        }
        else if(isset($request->class_id) && isset($year_level))
        {
            foreach($year_level as $ac)
            {
                $class_level[]=ClassLevel::checkRelation($request->class_id,$ac)->id;
            }
        }
        return CourseController::get_segment_class_level($request,$class_level);
    }

    public static function get_segment_class_level(Request $request ,$class_level)
    {
        $segment_class=array();
        $segment_class=null;
        if(isset($request->segment_id) && !isset($class_level))
        {
            $segment_class[]=SegmentClass::where('segment_id',$request->segment_id)->pluck('id');
        }
        else if(!isset($request->segment_id) && isset($class_level))
        {
            foreach($class_level as $ac)
            {
                $segment_class[]=SegmentClass::where('class_level_id',$ac)->pluck('id');
            }
        }
        else if(isset($request->segment_id) && isset($class_level))
        {
            foreach($class_level as $ac)
            {
                $segment_class[]=SegmentClass::checkRelation($ac,$request->segment_id)->id;
            }
        }

        return CourseController::get_course_segment_level($segment_class);
    }

    public static function get_course_segment_level($segment_class)
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
        return $course_segment;
    }

}
