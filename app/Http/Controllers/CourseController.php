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
            'segment_class_id' => $segmentClass->id
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
            'type'=>'nullable|in:category,year,type,level,class,segment'
        ]);
        if (isset($request->id))
        {
            return HelperController::api_response_format(200, Course::with('category')->whereId($request->id)->first());
        }
        else if($request->type == 'category')
        {
            $request->validate([
                'category_id'=>'required|exists:categories,id',
            ]);

            $courses=Course::where('category_id',$request->category_id)->get();

            return HelperController::api_response_format(200, $courses);
        }
        else if($request->type == 'year')
        {
            $request->validate([
                'year_id'=>'required|exists:academic_years,id',
            ]);

            $course=array();
            $course[]=CourseController::get_course_by_year_type($request->type,$request->year_id);
            return HelperController::api_response_format(200, $course);

        }
        else if($request->type == 'type')
        {
            $request->validate([
                'type_id'=>'required|exists:academic_types,id',
            ]);

            $course=array();
            $course[]=CourseController::get_course_by_year_type($request->type,$request->type_id);
            return HelperController::api_response_format(200, $course);
        }
        else if($request->type == 'level')
        {
            $request->validate([
                'level_id'=>'required|exists:levels,id',
            ]);

            $year_level=YearLevel::where('level_id',$request->level_id)->get('id');

            $class_level=array();
            foreach ($year_level as $year)
            {
                $class_level[]=ClassLevel::where('year_level_id',$year->id)->pluck('id');
            }

            $segment_class=array();
            foreach ($class_level as $class)
            {
                foreach($class as $c)
                {
                    $segment_class[]=SegmentClass::where('class_level_id',$c)->pluck('id');
                }
            }

            $course_id=array();
            foreach ($segment_class as $segment)
            {
                foreach($segment as $s)
                {
                    $course_id[]=CourseSegment::where('segment_class_id',$s)->pluck('course_id');
                }
            }

            $course=collect([]);
            foreach ($course_id as $cour)
            {
                foreach($cour as $cc)
                {
                    $course->push(Course::where('id',$cc)->get());
                }
            }
            $typeyearcourse=array();
            $typeyearcourse[]= $course->unique();
            return HelperController::api_response_format(200, $typeyearcourse);

        }
        else if($request->type == 'class')
        {
            $request->validate([
                'class_id'=>'required|exists:classes,id',
            ]);

            $class_level=ClassLevel::where('class_id',$request->class_id)->get('id');

            $segment_class=array();
            foreach ($class_level as $class)
            {
                 $segment_class[]=SegmentClass::where('class_level_id',$class->id)->pluck('id');
            }

            $course_id=array();
            foreach ($segment_class as $segment)
            {
                foreach($segment as $s)
                {
                    $course_id[]=CourseSegment::where('segment_class_id',$s)->pluck('course_id');
                }
            }

            $course=collect([]);
            foreach ($course_id as $cour)
            {
                foreach($cour as $cc)
                {
                    $course->push(Course::where('id',$cc)->get());
                }
            }
            $typeyearcourse=array();
            $typeyearcourse[]= $course->unique();
            return HelperController::api_response_format(200, $typeyearcourse);
        }
        else if($request->type == 'segment')
        {
            $request->validate([
                'segment_id'=>'required|exists:segments,id',
            ]);

            $segment_class=SegmentClass::where('segment_id',$request->segment_id)->pluck('id');


            $course_id=array();
            foreach ($segment_class as $segment)
            {
                $course_id[]=CourseSegment::where('segment_class_id',$segment)->pluck('course_id');
            }

            $course=collect([]);
            foreach ($course_id as $cour)
            {
                foreach($cour as $cc)
                {
                    $course->push(Course::where('id',$cc)->get());
                }
            }
            $typeyearcourse=array();
            $typeyearcourse[]= $course->unique();
            return HelperController::api_response_format(200, $typeyearcourse);

        }
        else {
            return HelperController::api_response_format(200, Course::with('category')->get());
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
        $courses = [];
        foreach ($request->user()->enroll as $enroll) {
            $courses[] = $enroll->CourseSegment->courses;
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
        $clase['course'] = Course::find($request->course_id);
        return HelperController::api_response_format(200 , $clase);
    }


    public function get_course_by_year_type($type,$id)
    {

        if($type == 'type')
        {
            $academic_year_type=AcademicYearType::where('academic_type_id',$id)->get('id');
        }
        else if($type == 'year')
        {
            $academic_year_type=AcademicYearType::where('academic_year_id',$id)->get('id');
        }

        $year_level=array();
        foreach($academic_year_type as $type)
        {
            $year_level[]=YearLevel::where('academic_year_type_id',$type->id)->pluck('id');
        }

        $class_level=array();
        foreach ($year_level as $year)
        {
            foreach($year as $y)
            {
                $class_level[]=ClassLevel::where('year_level_id',$y)->pluck('id');
            }
        }

        $segment_class=array();
        foreach ($class_level as $class)
        {
            foreach($class as $c)
            {
                $segment_class[]=SegmentClass::where('class_level_id',$c)->pluck('id');
            }
        }

        $course_id=array();
        foreach ($segment_class as $segment)
        {
            foreach($segment as $s)
            {
                $course_id[]=CourseSegment::where('segment_class_id',$s)->pluck('course_id');
            }
        }

        $course=collect([]);
        foreach ($course_id as $cour)
        {
            foreach($cour as $cc)
            {
                $course->push(Course::where('id',$cc)->get());
            }
        }
        $typeyearcourse=array();
        $typeyearcourse[]= $course->unique();
        return $typeyearcourse;
    }
}
