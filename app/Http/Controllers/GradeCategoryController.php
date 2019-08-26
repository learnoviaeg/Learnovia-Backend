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
            'parent' => 'exists:grade_categories,id',
            'aggregation' => 'nullable|integer',
            'aggregatedOnlyGraded' => 'nullable|integer',
            'hidden' => 'nullable|boolean',
            'class_id'=>'required|exists:classes,id'
        ]);
        $course_segment_id = CourseSegment::GetWithClassAndCourse($request->class_id,$request->course_id);
        $grade_category = [
            'name' => $request->name,
            'course_segment_id' => $course_segment_id->id,
        ];
        if($request->filled('parent')){
            $grade_category['parent']=$request->parent;
        }
        if($request->filled('aggregation')){
            $grade_category['aggregation']=$request->aggregation;
        }
        if($request->filled('aggregatedOnlyGraded')){
            $grade_category['aggregatedOnlyGraded']=$request->aggregatedOnlyGraded;
        }
        if($request->filled('hidden')){
            $grade_category['hidden']=$request->parent;
        }
        $GradeCat=GradeCategory::create($grade_category);
        
        return HelperController::api_response_format(200, $GradeCat, 'Grade Category is created successfully');
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
            'hidden' => 'integer',
        ]);
        $grade_category = GradeCategory::find($request->id);
        $grade_category->name = $request->name;
        if ($request->filled('course_segment_id')) {
            $grade_category->course_segment_id = $request->course_segment_id;
        }
        if ($request->filled('parent')) {
        $grade_category->parent = $request->parent;
        }
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
}
