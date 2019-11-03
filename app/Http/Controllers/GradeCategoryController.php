<?php

namespace App\Http\Controllers;

use App\GradeCategory;
use App\CourseSegment;
use App\AcademicYearType;
use App\ClassLevel;
use App\YearLevel;
use App\SegmentClass;
use Illuminate\Http\Request;

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
        $course_segment_id = CourseSegment::GetWithClassAndCourse($request->class_id,$request->course_id);
        if(isset($course_segment_id)){
        $grade_category = GradeCategory::create([
            'name' => $request->name,
            'course_segment_id' => $course_segment_id->id,
            'parent' => $request->parent,
            'aggregation' => $request->aggregation,
            'aggregatedOnlyGraded' => $request->aggregatedOnlyGraded,
            'hidden' =>(isset($request->hidden))?$request->hidden :0 ,
        ]);
       /* if($request->filled('hidden')){
            $grade_category->hidden = $request->hidden;
            $grade_category->save();
        }*/
        return HelperController::api_response_format(200, $grade_category, 'Grade Category is created successfully');
    }
        return HelperController::api_response_format(404,null , 'this class didnot have course segment');

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

        $jop = (new \App\Jobs\addgradecategory([1,2,3],$request->grades));
        dispatch($jop);
        return HelperController::api_response_format(200, null, 'Grade Category is created successfully');

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
            'id'=>'required|exists:grade_categories,id',
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
        if($request->filled('hidden')){
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
}
