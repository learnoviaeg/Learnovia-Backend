<?php

namespace App\Http\Controllers;

use App\GradeCategory;
use App\CourseSegment;
use App\AcademicYear;
use App\Course;
use App\Classes;
use App\AcademicYearType;
use App\ClassLevel;
use App\YearLevel;
use App\SegmentClass;
use Illuminate\Http\Request;
use App\Segment;

class GradeCategoryController extends Controller
{

    public function AddGradeCategory(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'course_id' => 'required|exists:course_segments,course_id',
            'class_id' => 'required|exists:classes,id',
            'parent' => 'exists:grade_categories,id',
            'aggregation' => 'integer',
            'aggregatedOnlyGraded' => 'integer',
            'hidden' => 'integer'
        ]);
        $course_segment_id = CourseSegment::GetWithClassAndCourse($request->class_id, $request->course_id);
        if (isset($course_segment_id)) {
            $grade_category = GradeCategory::create([
                'name' => $request->name,
                'course_segment_id' => $course_segment_id->id,
                'parent' => $request->parent,
                'aggregation' => $request->aggregation,
                'aggregatedOnlyGraded' => $request->aggregatedOnlyGraded,
                'hidden' => (isset($request->hidden)) ? $request->hidden : 0,
            ]);
            /* if($request->filled('hidden')){
                 $grade_category->hidden = $request->hidden;
                 $grade_category->save();
             }*/
            return HelperController::api_response_format(200, $grade_category, 'Grade Category is created successfully');
        }
        return HelperController::api_response_format(404, null, 'this class didnot have course segment');

    }

    public function addBulkGradeCategories(Request $request)
    {
        $request->validate([
            'grades' => 'required|array',
            'grades.*.name' => 'required|string',
            'grades.*.parent' => 'integer|exists:grade_categories,id',
            'grades.*.aggregation' => 'integer',
            'grades.*.aggregatedOnlyGraded' => 'boolean',
            'grades.*.hidden' => 'boolean',
        ]);
        $jop = (new \App\Jobs\addgradecategory($this->getCourseSegment($request), $request->grades));
        dispatch($jop);
        return HelperController::api_response_format(200, null, 'Grade Category is created successfully');

    }
    public function deleteBulkGradeCategories(Request $request)
    {
        $request->validate([
            'id_number' => 'required|exists:year_levels,id',
            'name' => 'required|string|exists:grade_categories,name']);
        $course_segments = $this->getCourseSegment($request);
        GradeCategory::whereIn('course_segment_id', $course_segments)
            ->where('id_number', $request->id_number)
            ->where('name', $request->name)->delete();
        return HelperController::api_response_format(200, null, 'Grade Category is deleted successfully');
    }

    public function GetGradeCategory(Request $request)
    {
        if ($request->filled('id')) {
            $gradeCategory = GradeCategory::with('Child')->where('id', $request->id)->first();
        } else {
            $gradeCategory = GradeCategory::with('Child')->get();
        }
        return HelperController::api_response_format(200, $gradeCategory);
    }

    public function UpdateGradeCategory(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:grade_categories,id',
            'name' => 'required',
            'course_segment_id' => 'exists:course_segments,id',
            'parent' => 'exists:grade_categories,id',
            'aggregation' => 'integer',
            'aggregatedOnlyGraded' => 'integer',
            'hidden' => 'integer'
        ]);
        $grade_category = GradeCategory::find($request->id);
        $grade_category->name = $request->name;
        if ($request->filled('course_segment_id')) {
            $grade_category->course_segment_id = $request->course_segment_id;
        }
        $grade_category->parent = $request->parent;
        if ($request->filled('hidden')) {
            $grade_category->hidden = $request->hidden;
        }
        $grade_category->save();
        return HelperController::api_response_format(200, $grade_category, 'Grade Category is updated successfully');
    }

    public function deleteGradeCategory(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:grade_categories,id'
        ]);
        $gradeCategory = GradeCategory::find($request->id);
        $gradeCategory->delete();
        return HelperController::api_response_format(200, null, 'Grade Category is deleted successfully');
    }

    public function MoveToParentCategory(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:grade_categories,id',
            'parent' => 'required|exists:grade_categories,id',
        ]);
        $GardeCategory = GradeCategory::find($request->id);
        $GardeCategory->update([
            'parent' => $request->parent,
        ]);
        return HelperController::api_response_format(200, $GardeCategory, 'Grade Category is moved successfully');
    }

    public function GetCategoriesFromCourseSegments(Request $request)
    {
        $grade = CourseSegment::GradeCategoryPerSegmentbyId($request->id);
        return $grade;
    }

    public function Get_Tree(Request $request)
    {
        $course_segment = HelperController::Get_Course_segment_Course($request);
        if ($course_segment['result'] == false) {
            return HelperController::api_response_format(400, null, $course_segment['value']);
        }
        if ($course_segment['value'] == null) {
            return HelperController::api_response_format(400, null, 'No Course active in segment');
        }
        $course_segment = $course_segment['value'];
        $grade_category = GradeCategory::with(['Child', 'GradeItems', 'Child.GradeItems'])->where('course_segment_id', $course_segment->id)->get();
        return HelperController::api_response_format(200, $grade_category);
    }

    public function getCourseSegment(Request $request)
    {
        $year = AcademicYear::Get_current();
        if ($request->filled('year'))
            $year = AcademicYear::find($request->year);
        $YearTypes = $year->where('id', $year->id)->with(['YearType' => function ($query) use ($request) {
            if ($request->filled('type'))
                $query->where('academic_type_id', $request->type);
        }, 'YearType.yearLevel' => function ($query) use ($request) {
            if ($request->filled('level'))
                $query->where('level_id', $request->level);
        }, 'YearType.yearLevel.classLevels' => function ($query) use ($request) {
            if ($request->filled('class'))
                $query->where('class_id', $request->class);
        }, 'YearType.yearLevel.classLevels.segmentClass' => function ($query) use ($request) {
            if ($request->filled('type')) {
                $segment_id = Segment::Get_current($request->type)->id;
                if ($request->filled('segment'))
                    $segment_id = $request->segment;
                $query->where('segment_id', $segment_id);
            }
        }, 'YearType.yearLevel.classLevels.segmentClass.courseSegment' => function ($query) use ($request) {
            if ($request->filled('course'))
                $query->where('course_id', $request->course);
        }])->get()->pluck('YearType')[0];
        $array = collect();
        if (count($YearTypes) > 0) {
            $YearTypes = $YearTypes->pluck('yearLevel');
            if (count($YearTypes) > 0) {
                $YearTypes = $YearTypes[0]->pluck('classLevels');
                if (count($YearTypes) > 0) {
                    $YearTypes = $YearTypes[0]->pluck('segmentClass');
                    if (count($YearTypes) > 0) {
                        $YearTypes = $YearTypes[0]->pluck('courseSegment');
                        foreach ($YearTypes as $courseSegment) {
                            foreach ($courseSegment as $value) {
                                $array->push($value->id);
                            }
                        }
                    }
                }
            }
        }
        return $array;
    }

    public function bulkupdate(Request $request)
    {
        $request->validate([
            'name' => 'required|exists:grade_categories,name',
            'id_number' => 'required|exists:grade_categories,id_number',
            'newname' => 'required|string'
        ]);

        $data=array();
        $course_segment=self::getCourseSegment($request);
        if(isset($course_segment)){

            GradeCategory::whereIn('course_segment_id',$course_segment)->where('name',$request->name)->where('id_number',$request->id_number)->update(array('name' => $request->newname));
            $data= GradeCategory::whereIn('course_segment_id',$course_segment)->where('name',$request->newname)->where('id_number',$request->id_number)->get();
            return HelperController::api_response_format(200, $data,'Updated Grade categories');
        }
        else{
              return HelperController::api_response_format(200, 'There is No Course segment available.');
        }
    }

    public function GetGradeCategoryTree(Request $request)
    {
        $gradeCategories=collect();
        $courses_segment=self::getCourseSegment($request);
        if(isset($courses_segment))
        {
            $names=collect();
            foreach ($courses_segment as $courses_seg) {
                $course = CourseSegment::find($courses_seg);
                $gradeCategories->push($course->GradeCategory);
                foreach($gradeCategories as $gradecategory)
                    foreach($gradecategory as $GC)
                    {
                        $level=YearLevel::find($GC->id_number);
                        $lev=$level->levels[0]->name;
                        $names->push(['name'=>$GC->name,'id_number'=>$GC->id_number,'level'=>$lev]);
                    }
            }
        $all = $names->unique()->sortBy('id_number');
        $alls=$all->values();
        return HelperController::api_response_format(200, $alls);
        }
        return HelperController::api_response_format(200, 'There is No Course segment available.');
    }

    public function GetAllGradeCategory(Request $request)
    {
        $gradeCategories=collect();
        $courses_segment=self::getCourseSegment($request);
        if(isset($courses_segment))
        {
            $names=collect();
            foreach ($courses_segment as $courses_seg) {
                $course = CourseSegment::find($courses_seg);
                $gradeCategories->push($course->GradeCategory);
                foreach($gradeCategories as $grades)
                {
                    if($grades->isEmpty())
                        continue;
                    foreach($grades as $grade)
                    {
                        $level=YearLevel::find($grade->id_number);
                        $yearlevels=$level->classLevels;
                        foreach($yearlevels as $Yclass)
                        {
                            $classes[]=Classes::find($Yclass->class_id);
                            foreach($classes as $class)
                                $ClassesName[]=$class->name;
                        }
                        $lev=$level->levels[0]->name;
                        $course = CourseSegment::find($courses_seg);

                        $course_id=$course->course_id;
                        $course=Course::find($course_id);

                        $names->push(['name'=>$grade->name,'id_number'=>$grade->id_number,'level'=>$lev,'course'=>$course->name,'class'=>array_values(array_unique($ClassesName))]);
                    }
                }
            }

        return HelperController::api_response_format(200, $names);
        }
        return HelperController::api_response_format(200, 'There is No Course segment available.');
    }
}
