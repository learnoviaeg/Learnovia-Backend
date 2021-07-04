<?php

namespace App\Http\Controllers;

use App\GradeCategory;
use App\CourseSegment;
use App\AcademicYear;
use App\YearLevel;
use App\GradeItems;
use App\Level;
use Illuminate\Http\Request;
use App\Segment;
use stdClass;
use App\LastAction;
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
            'parent' => 'nullable|exists:grade_categories,id',
            'aggregation' => 'nullable|integer',
            'aggregatedOnlyGraded' => 'nullable|integer',
            'hidden' => 'boolean|required',
            'grademin' => 'nullable|required_if:type,==,0|integer|min:0',
            'grademax' => 'nullable|required_if:type,==,0|integer|gt:grademin',
            'type' => 'boolean|required',
            'exclude_flag' => 'boolean|nullable',
            'locked' => 'nullable|boolean',
            'weight' => 'nullable|integer'
        ]);
        ///type 1 => value
        ///type 0 => scale
        $course_segment_id = CourseSegment::GetWithClassAndCourse($request->class, $request->course);
        LastAction::lastActionInCourse($request->course);
        
        if (isset($course_segment_id)) {
            $grade_cat=GradeCategory::where('course_segment_id',$course_segment_id->id)->whereNull('parent')->get()->first();
            $segclass=CourseSegment::find($course_segment_id->id)->segmentClasses;
            $classlevel=$segclass[0]->classLevel;
            $year_level= $classlevel[0]->yearLevels;
            $grade_category = GradeCategory::create([
                'name' => $request->name,
                'course_segment_id' => $course_segment_id->id,
                'parent' => isset($request->parent) ? $request->parent : $grade_cat->id,
                'aggregation' => $request->aggregation,
                'locked' => (isset($request->locked)) ? $request->locked : null,
                'aggregatedOnlyGraded' => $request->aggregatedOnlyGraded,
                'hidden' => $request->hidden,
                'grademax' => ($request->type==0) ? $request->grademax : null,
                'grademin' => ($request->type==0) ? $request->grademin : null,
                'type' => $request->type,
                'exclude_flag' => $request->exclude_flag,
                'id_number' => $year_level[0]->id,
                'weight' => (isset($request->weight)) ? $request->weight : 0,
            ]);

            if(!isset($request->parent))
              { $grade_category->weight=100;
                $grade_category->save();}
            else
            {
                $weight = [];
                $grade_parent=GradeCategory::where('id',$request->parent)->with('Child')->get();
                $allWeight = 0;
                $check=$grade_parent[0]->child->where('weight','!=', 0);
                foreach ($check as $childs) {
                    $allWeight += $childs->weight();
                    $weight[] = $childs->weight();
                }
                if($allWeight ==0){
                    $grade_category->weight=100;
                    $grade_category->save();
                }
                if ($allWeight != 100 && $allWeight !=0) {
                    // $message = "Your grades adjusted to get 100!";
                    $gcd = GradeItems::findGCD($weight, sizeof($weight));
                    foreach ($weight as $w) {
                        $devitions[] = $w / $gcd;
                    }
                    $calculations = (100 / array_sum($devitions));
                    $count = 0;
                    foreach ($check as $childs) {
                        $childs->update(['weight' => round($devitions[$count] * $calculations, 3)]);
                        $count++;
                    }
                }
            }
            $grade_category->weight=GradeCategory::where('id',$grade_category->id)->pluck('weight')->first();

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
            'grades.*.parent' => 'nullable|exists:grade_categories,name',
            'grades.*.aggregation' => 'integer|nullable',
            'grades.*.aggregatedOnlyGraded' => 'boolean|nullable',
            'grades.*.hidden' => 'boolean|required',
            'grades.*.grademin' => 'required_if:grades.*.type,==,1|integer|min:0',
            'grades.*.grademax' => 'required_if:grades.*.type,==,1|integer|gt:grades.*.grademin',
            'grades.*.type' => 'boolean|required',
            'grades.*.exclude_flag' => 'boolean|nullable',
            'grades.*.locked' => 'boolean|required',
            'grades.*.weight' => 'integer|required_if:grades.*.exclude_flag,==,1',
            'year' => 'exists:academic_years,id',
            'type' => 'exists:academic_types,id|required_with:level',
            'level' => 'exists:levels,id|required_with:class',
            'class' => 'exists:classes,id',
            'segment' => 'exists:segments,id',
            'courses' => 'array|exists:courses,id'
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

        $grade_category=[];
        $coursesegment=GradeCategoryController::getCourseSegment($request);
        if(!$coursesegment)
            return HelperController::api_response_format(200, 'There is No Course segment available.');

        foreach($coursesegment as $courseseg)
        {
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
        if($grade_category == null)
            return HelperController::api_response_format(200, $grade_category,'No assigned Grade Categories, No Grade with this name');

        return HelperController::api_response_format(200, $grade_category,'Grade Categories Assigned.');
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
            $gradeCategory->weight = $gradeCategory->weight();
            foreach($gradeCategory->child as $chil)
            {
                 $chil->weight = $chil->weight();
                 unset($chil->Parents);
                 unset($chil->GradeItems);
                 unset($chil->Children);
            }
        } else {

            $gradeCategory = GradeCategory::with('Child')->get();
            foreach($gradeCategory as $g)
            {
                $g->weight = $g->weight();
                foreach($g->child as $chil)
                {
                     $chil->weight = $chil->weight();
                     unset($chil->Parents);
                     unset($chil->GradeItems);
                     unset($chil->Children);
                }
                unset($g->Parents);
                unset($g->GradeItems);
                unset($g->Children);
            }
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
            'grademin' => 'required_if:type,==,1|integer|min:0',
            'grademax' => 'required_if:type,==,1|integer|gt:grademin',
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
        
        LastAction::lastActionInCourse($request->course);

        $courseSegment = CourseSegment::GetWithClassAndCourse($request->class, $request->course);
        
        if ($courseSegment == null)
            return HelperController::api_response_format(200, null, 'This Course not assigned to this class');
        $grade_category = GradeCategory::where('course_segment_id', $courseSegment->id)->with('Children', 'GradeItems')
            ->first();
        $grade_category->weight=$grade_category->weight();
        unset($grade_category->Parents);
        // unset($gradeCategory->GradeItems);
        // unset($gradeCategory->Children);
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
        if(isset($year)){
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
                    $segment_id = Segment::Get_current($request->type);
                    if(isset($segment_id))
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
        return [];
    }

    public  static function getCourseSegmentWithArray(Request $request)
    {
        $year = AcademicYear::Get_current();
        if ($request->filled('year'))
            $year = AcademicYear::find($request->year);
        $YearTypes = $year->where('id', $year->id)->with(['YearType' => function ($query) use ($request) {
            if ($request->filled('type'))
                $query->whereIn('academic_type_id', $request->type);
        }, 'YearType.yearLevel' => function ($query) use ($request) {
            if ($request->filled('levels'))
                $query->whereIn('level_id', $request->levels);
        }, 'YearType.yearLevel.classLevels' => function ($query) use ($request) {
            if ($request->filled('classes'))
                $query->whereIn('class_id', $request->classes);
        }, 'YearType.yearLevel.classLevels.segmentClass' => function ($query) use ($request) {
            if ($request->filled('type')) {
                $ids = [];
                foreach ($request->type as $type){
                    $ids= Segment::Get_current($type);
                    if(isset($ids))
                        $ids=[$ids->id];
                }
                if ($request->filled('segments'))
                    $ids = $request->segments;
                $query->whereIn('segment_id', $ids);
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
            'newname' => 'required|string',
            'year' => 'exists:academic_years,id',
            'type' => 'exists:academic_types,id|required_with:level',
            'level' => 'exists:levels,id|required_with:class',
            'class' => 'exists:classes,id',
            'segment' => 'exists:segments,id',
            'courses' => 'array|exists:courses,id',
            'parent' => 'nullable|exists:grade_categories,id',
            'aggregation' => 'integer|nullable',
            'aggregatedOnlyGraded' => 'nullable|integer',
            'hidden' => 'integer',
            'grademin' => 'nullable|required_if:type_grade,==,1|integer',
            'grademax' => 'nullable|required_if:type_grade,==,1|integer',
            'type_grade' => 'boolean|required',
            'exclude_flag' => 'boolean',
            'weight' => 'integer',
            'locked' => 'nullable|boolean',
        ]);

        $grade_category=[];
        $course_segment = self::getCourseSegment($request);
        // return $course_segment;
        if (isset($course_segment)) {
                foreach($course_segment as $course)
                {
                    $segclass=CourseSegment::find($course)->segmentClasses;
                    $classlevel=$segclass[0]->classLevel;
                    $year_level= $classlevel[0]->yearLevels;
                    $gradeCat=GradeCategory::where('name',$request->name)->whereNotNull('id_number')->first();
                    // return $gradeCat;

                    if(isset($gradeCat))
                    {
                        $gradeCat->update([
                            'name' => $request->newname,
                            'course_segment_id' => $course,
                            'parent' => (isset($request->parent)) ? $request->parent : $gradeCat->parent ,
                            'aggregation' => (isset($request->aggregation)) ? $request->aggregation : $gradeCat->aggregation,
                            'aggregatedOnlyGraded' => (isset($request->aggregatedOnlyGraded)) ? $request->aggregatedOnlyGraded : $gradeCat->aggregatedOnlyGraded,
                            'hidden' => (isset($request->hidden)) ? $request->hidden : $gradeCat->hidden,
                            'weight' => (isset($request->weight)) ? $request->weight : $gradeCat->weight,
                            'grademax' => ($request->type_grade==1) ? $request->grademax : $gradeCat->grademax,
                            'grademin' => ($request->type_grade==1) ? $request->grademin : $gradeCat->grademin,
                            'type_grade' => (isset($request->type_grade)) ? $request->type_grade: 0,
                            'locked' => (isset($request->locked)) ? $request->locked :null,
                            'id_number' => $year_level[0]->id,
                            'exclude_flag' => (isset($request->exclude_flag)) ? $request->exclude_flag : $gradeCat->exclude_flag
                        ]);
                    }
                }
            return HelperController::api_response_format(200, null, 'Grade categories updated');
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
     * @param  [int] courses
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
    public function GetAllGradeCategory()
    {
        $result = array();
        $gradeCategories = GradeCategory::whereNotNull('id_number')->get();
        $i=0;
        foreach ($gradeCategories as $gradeCategory) {
            $gradeCategory->weight=$gradeCategory->weight();
            unset($gradeCategory->Parents);
            unset($gradeCategory->GradeItems);
            unset($gradeCategory->Children);
            if (!isset($result[$gradeCategory->name])) {
                $result[$i]=$gradeCategory;
                $result[$i]->levels = collect();
            }
            $temp = new stdClass();
            $temp->name = YearLevel::find($gradeCategory->id_number)->levels[0]->name;
            $temp->id = $gradeCategory->id_number;
            if (!$result[$i]->levels->contains($temp))
                $result[$i]->levels->push($temp);
            $i++;
        }
        return HelperController::api_response_format(200, $result);
    }

    public function GetAllGradeCategoryByLevels(Request $request)
    {
        $request->validate([
            'levels' => 'required|array|exists:levels,id'
        ]);
        $result = [];
        $yearL=array();
        $yearL=AcademicYear::getAllYearLevel(null,$request->levels);
        $gradeCategories = GradeCategory::whereNotNull('id_number')->get();

        foreach ($gradeCategories as $gradeCategory) {
            if(in_array($gradeCategory->id_number,$yearL->toArray()))
            {
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
            }
        return HelperController::api_response_format(200, $result);
    }

    public function getgradecat(Request $request)
    {
        $request->validate([
            'class_id' => 'required|exists:classes,id',
            'course_id' => 'required|exists:courses,id'
        ]);

        $coursesegment=CourseSegment::GetWithClassAndCourse($request->class_id,$request->course_id);
        if($coursesegment)
        {
            $gradeCategories=$coursesegment->GradeCategory()->whereNull('instance_type')->get();
            LastAction::lastActionInCourse($request->course_id);
            return HelperController::api_response_format(200, $gradeCategories);
        }
        return HelperController::api_response_format(200, null,'No available course segment');
    }

    public function getgradecatArray(Request $request)
    {
        $request->validate([
            'levels' => 'array',
            'levels' => 'exists:levels,id',
            'classes' => 'required|array',
            'classes.*' => 'required|exists:classes,id',
            'courses' => 'required|array',
            'courses.*' => 'required|exists:courses,id'
        ]);

        $coursesegment=self::getCourseSegmentWithArray($request);
        if(!isset($coursesegment))
            return HelperController::api_response_format(200, $gradeCategories,'No available course segment');
            
        $courses=CourseSegment::whereIn('id',$coursesegment)->get();
        foreach($courses as $course)
            $gradeCategories[]=$course->GradeCategory;
        return HelperController::api_response_format(200, $gradeCategories);
    }
}
