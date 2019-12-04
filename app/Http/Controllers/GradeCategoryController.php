<?php

namespace App\Http\Controllers;

use App\GradeCategory;
use App\CourseSegment;
use App\AcademicYear;
use App\YearLevel;
use Illuminate\Http\Request;
use App\Segment;
use stdClass;

class GradeCategoryController extends Controller
{
    /**
     * Add Grade Category
     *
     * @param  [string] name
     * @param  [int] course
     * @param  [int] class
     * @param  [int] parent
     * @param  [int] aggregation
     * @param  [int] hidden
     * @param  [int] aggregatedOnlyGraded
     * @return [string] Grade Category is created successfully and the object
     * @return if there is no course[string] this class didnot have course segment
     */
    public function AddGradeCategory(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'course' => 'required|exists:course_segments,course_id',
            'class' => 'required|exists:classes,id',
            'parent' => 'exists:grade_categories,id',
            'aggregation' => 'integer',
            'aggregatedOnlyGraded' => 'integer',
            'hidden' => 'integer',
            'id_number' => 'integer',
            'grademin' => 'required_if:type,==,1|integer',
            'grademax' => 'required_if:type,==,1|integer',
            'type' => 'boolean|required',
            'exclude_flag' => 'boolean'
        ]);
        $course_segment_id = CourseSegment::GetWithClassAndCourse($request->class, $request->course);
        if (isset($course_segment_id)) {
            $grade_category = GradeCategory::create([
                'name' => $request->name,
                'course_segment_id' => $course_segment_id->id,
                'parent' => $request->parent,
                'id_number' => (isset($request->id_number)) ? $request->id_number : null,
                'aggregation' => $request->aggregation,
                'aggregatedOnlyGraded' => $request->aggregatedOnlyGraded,
                'hidden' => (isset($request->hidden)) ? $request->hidden : 0,
                'grademax' => ($request->type==1) ? $request->grademax : null,
                'grademin' => ($request->type==1) ? $request->grademin : null,
                'type' => $request->type,
                'exclude_flag' => (isset($request->exclude_flag)) ? $request->exclude_flag : 0
            ]);

            return HelperController::api_response_format(200, $grade_category, 'Grade Category is created successfully');
        }
        return HelperController::api_response_format(404, null, 'this class didnot have course segment');
    }

    /**
     * Add bulk Grade Category
     *
     * @param  [array] grades[name]
     * @param  [array] grades[parent]
     * @param  [array] grades[aggregation]
     * @param  [array] grades[aggregatedOnlyGraded]
     * @param  [array] grades[hidden]
     * @return [string] Grade Category is created successfully
     */
    public function addBulkGradeCategories(Request $request)
    {
        $request->validate([
            'grades' => 'required|array',
            'grades.*.name' => 'required|string',
            'grades.*.parent' => 'integer|exists:grade_categories,id',
            'grades.*.aggregation' => 'integer',
            'grades.*.aggregatedOnlyGraded' => 'boolean',
            'grades.*.hidden' => 'boolean',
            'grades.*.grademin' => 'required_if:grades.*.type,==,1|integer',
            'grades.*.grademax' => 'required_if:grades.*.type,==,1|integer',
            'grades.*.type' => 'boolean|required',
            'grades.*.exclude_flag' => 'boolean'
        ]);
        $jop = (new \App\Jobs\addgradecategory($this->getCourseSegment($request), $request->grades));
        dispatch($jop);
        return HelperController::api_response_format(200, null, 'Grade Category is created successfully');
    }

    public function AssignBulkGradeCategory(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'year' => 'exists:academic_years,id',
            'type' => 'exists:academic_types,id|required_with:level',
            'level' => 'exists:levels,id|required_with:class',
            'class' => 'exists:classes,id',
            'segment' => 'exists:segments,id',
            'courses' => 'array|exists:courses,id'
        ]);

        $coursesegment=GradeCategoryController::getCourseSegment($request);
        if(!$coursesegment)
            return HelperController::api_response_format(200, 'There is No Course segment available.');
            
        // return $coursesegment;
        foreach($coursesegment as $courseseg)
        {
            // $year_level_tree=CourseSegment::where('id',$courseseg)->with(['segmentClasses.classLevel.yearLevels' => function ($query) use ($request){
            //     $query->pluck('id')->first();
            // }])->get();
            $segclass=CourseSegment::find($courseseg)->segmentClasses;
            $classlevel=$segclass[0]->classLevel;
            $year_level= $classlevel[0]->yearLevels;
            $gradeCat=GradeCategory::where('name',$request->name)->whereNotNull('id_number')->first();
            if(isset($gradeCat))
            {
                $grade_category[] = GradeCategory::firstOrCreate([
                    'name' => $gradeCat->name,
                    'course_segment_id' => $courseseg,
                    'parent' => $gradeCat->parent,
                    'id_number' => $year_level[0]->id,
                    'aggregation' => $gradeCat->aggregation,
                    'aggregatedOnlyGraded' => $gradeCat->aggregatedOnlyGraded,
                    'hidden' => $gradeCat->hidden,
                    'grademax' => $gradeCat->grademax,
                    'grademin' => $gradeCat->grademin,
                    'type' => $gradeCat->type,
                    'exclude_flag' => $gradeCat->exclude_flag
                ]); 
            }
        }
        return HelperController::api_response_format(200, $grade_category,'Grade Category Assigned.');
    }

    /**
     * delete bulk Grade Category
     *
     * @param  [string] name
     * @param  [int] id_number
     * @return [string] Grade Category is deleted successfully
     */
    public function deleteBulkGradeCategories(Request $request)
    {
        $request->validate([
            'id' => 'nullable|exists:year_levels,id',
            'name' => 'required|string|exists:grade_categories,name'
        ]);
        $gradeCategory = GradeCategory::whereNotNull('id_number');
        if($request->filled('id'))
            $gradeCategory->where('id_number', $request->id);
        $gradeCategory->where('name', $request->name);
        $gradeCategory->delete();
        return HelperController::api_response_format(200, null, 'Grade Category is deleted successfully');
    }

    /**
     * get Grade Category
     *
     * @param  [int] id
     * @return [object] Grade Categories with child
     */
    public function GetGradeCategory(Request $request)
    {
        if ($request->filled('id')) {
            $gradeCategory = GradeCategory::with('Child')->where('id', $request->id)->first();
        } else {
            $gradeCategory = GradeCategory::with('Child')->get();
        }
        return HelperController::api_response_format(200, $gradeCategory);
    }

    /**
     * update Grade Category
     *
     * @param  [int] id
     * @param  [string] name
     * @param  [int] course_segment_id
     * @param  [int] aggregation
     * @param  [int] aggregatedOnlyGraded
     * @param  [int] hidden
     * @param  [int] parent
     * @return [string] Grade Category is updated successfully and the object
     */
    public function UpdateGradeCategory(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:grade_categories,id',
            'name' => 'required',
            'course_segment_id' => 'exists:course_segments,id',
            'parent' => 'exists:grade_categories,id',
            'aggregation' => 'integer',
            'aggregatedOnlyGraded' => 'integer',
            'hidden' => 'integer',
            'grademin' => 'required_if:type,==,1|integer',
            'grademax' => 'required_if:type,==,1|integer',
            'type' => 'boolean|required',
            'exclude_flag' => 'boolean'
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
        if ($request->filled('exclude_flag')) {
            $grade_category->exclude_flag = $request->exclude_flag;
        }
        if(isset($request->type) && $request->type==1)
        {
            $grade_category->grademin = $request->grademin;
            $grade_category->grademax = $request->grademax;
        }
        $grade_category->save();
        return HelperController::api_response_format(200, $grade_category, 'Grade Category is updated successfully');
    }

    /**
     * delete Grade Category
     *
     * @param  [int] id
     * @return [string] Grade Category is deleted successfully
     */
    public function deleteGradeCategory(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:grade_categories,id'
        ]);
        $gradeCategory = GradeCategory::find($request->id);
        $gradeCategory->delete();
        return HelperController::api_response_format(200, null, 'Grade Category is deleted successfully');
    }

    /**
     * Move Category
     *
     * @param  [int] id
     * @param  [int] parent
     * @return [string] Grade Category is moved successfully
     */
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

    /**
     * get Category from course_segments
     *
     * @param  [int] id
     * @return [object] Grade Categories In Segments
     */
    public function GetCategoriesFromCourseSegments(Request $request)
    {
        $grade = CourseSegment::GradeCategoryPerSegmentbyId($request->id);
        return $grade;
    }

    /**
     * get Category with Tree
     * @param  [int] class
     * @param  [int] course
     * @return if there is no course segment or disactives [string] No Course active in segment
     * @return if there is [string] Get grade category with child
     */
    public function Get_Tree(Request $request)
    {
        set_time_limit(0);
        $request->validate([
            'course' => 'required|exists:courses,id',
            'class'  => 'required|exists:classes,id'
        ]);
        $courseSegment = CourseSegment::GetWithClassAndCourse($request->class, $request->course);
        if ($courseSegment == null)
            return HelperController::api_response_format(200, null, 'This Course not assigned to this class');
        $grade_category = GradeCategory::where('course_segment_id', $courseSegment->id)->with('Children', 'GradeItems')
            ->first();
        return HelperController::api_response_format(200, $grade_category);
    }

    /**
     * get course_segments with/without any param chain
     *
     * @param  [int] year
     * @param  [int] type
     * @param  [int] level
     * @param  [int] class
     * @param  [int] course
     * @param  [int] segment
     * @return [object] Grade Categories In Segments
     */
    public  static function getCourseSegment(Request $request)
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
        }, 'YearType.yearLevel.classLevels.segmentClass.courseSegment' => function ($query)  use ($request) {
            if ($request->filled('courses'))
                $query->whereIn('course_id', $request->courses);
            if ($request->filled('typical'))
                $query->where('typical', $request->typical);
        }])->get()->pluck('YearType')[0];
        $array = collect();
        if (count($YearTypes) > 0) {
            $YearTypes = $YearTypes->pluck('yearLevel');
            if (count($YearTypes) > 0) {
                for ($i = 0; $i < count($YearTypes); $i++) {
                    $classes = $YearTypes[$i]->pluck('classLevels');
                    if (count($classes) > 0) {
                        for ($j = 0; $j < count($classes); $j++) {
                            $segments = $classes[$j]->pluck('segmentClass');
                            if (count($segments) > 0) {
                                for ($k = 0; $k < count($segments); $k++) {
                                    $courseSegments = $segments[$k]->pluck('courseSegment');

                                    foreach ($courseSegments as $courseSegment) {
                                        foreach ($courseSegment as $value) {
                                            $array->push($value->id);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        return $array;
    }

    /**
     * bulk update grade
     *
     * @param  [string] name
     * @param  [int] id_number
     * @param  [string] newname
     * @param  [int] year
     * @param  [int] type
     * @param  [int] level
     * @param  [int] class
     * @param  [int] course
     * @param  [int] segment
     * @return [object] Updated Grade categories
     */
    public function bulkupdate(Request $request)
    {
        $request->validate([
            'name' => 'required|exists:grade_categories,name',
            'id_number' => 'required|exists:grade_categories,id_number',
            'newname' => 'required|string'
        ]);

        $data = array();
        $course_segment = self::getCourseSegment($request);
        if (isset($course_segment)) {
            GradeCategory::whereIn('course_segment_id', $course_segment)->where('name', $request->name)->where('id_number', $request->id_number)->update(array('name' => $request->newname));
            $data = GradeCategory::whereIn('course_segment_id', $course_segment)->where('name', $request->newname)->where('id_number', $request->id_number)->get();
            return HelperController::api_response_format(200, $data, 'Updated Grade categories');
        } else {
            return HelperController::api_response_format(200, 'There is No Course segment available.');
        }
    }

    /**
     * Get grade category with chain
     *
     * @param  [int] year
     * @param  [int] type
     * @param  [int] level
     * @param  [int] class
     * @param  [int] course
     * @param  [int] segment
     * @return if there is no course segment [string] There is No Course segment available.
     * @return [objects] grade categories
     */
    public function GetGradeCategoryTree(Request $request)
    {
        $gradeCategories = collect();
        $courses_segment = self::getCourseSegment($request);
        if (isset($courses_segment)) {
            $names = collect();
            foreach ($courses_segment as $courses_seg) {
                $course = CourseSegment::find($courses_seg);
                $gradeCategories->push($course->GradeCategory);
                foreach ($gradeCategories as $gradecategory)
                    foreach ($gradecategory as $GC) {
                        $level = YearLevel::find($GC->id_number);
                        $lev = $level->levels[0]->name;
                        $names->push(['name' => $GC->name, 'id_number' => $GC->id_number, 'level' => $lev]);
                    }
            }
            $all = $names->unique()->sortBy('id_number');
            $alls = $all->values();
            return HelperController::api_response_format(200, $alls);
        }
        return HelperController::api_response_format(200, 'There is No Course segment available.');
    }

    /**
     * Get all grade category with chain
     *
     * @param  [int] year
     * @param  [int] type
     * @param  [int] level
     * @param  [int] class
     * @param  [int] course
     * @param  [int] segment
     * @return if there is no course segment [string] There is No Course segment available.
     * @return [objects] grade categories
     */
    public function GetAllGradeCategory(Request $request)
    {
        $result = [];
        $gradeCategories = GradeCategory::whereNotNull('id_number')->get();
        foreach ($gradeCategories as $gradeCategory) {
            if (!isset($result[$gradeCategory->name])) {
                $result[$gradeCategory->name] = $gradeCategory;
                $result[$gradeCategory->name]->levels = collect();
            }
            $temp = new stdClass();
            $temp->name = YearLevel::find($gradeCategory->id_number)->levels[0]->name;
            $temp->id = $gradeCategory->id_number;
            if (!$result[$gradeCategory->name]->levels->contains($temp))
                $result[$gradeCategory->name]->levels->push($temp);
        }
        return HelperController::api_response_format(200, $result);
    }
}
