<?php

namespace App\Http\Controllers;

use App\GradeCategory;
use App\CourseSegment;
use Illuminate\Http\Request;

class GradeCategoryController extends Controller
{

    public function AddGradeCategory(Request $request)
    {
        $request->validate([
            'name' => 'required',
           // 'course_segment_id' => 'required|exists:course_segments,id',
            'course_id' => 'required|exists:course_segments,course_id',
            'parent' => 'exists:grade_categories,id',
            'aggregation' => 'integer',
            'aggregatedOnlyGraded' => 'integer',
            'hidden'=>'integer'
            ]);
            $course_segment_id=CourseSegment::getActive_segmentfromcourse($request->course_id);
            $grade_category = GradeCategory::create([
                'name' => $request->name,
                'course_segment_id' => $course_segment_id,
                'parent' => $request->parent,
                'aggregation'=>$request->aggregation,
                'aggregatedOnlyGraded'=>$request->aggregatedOnlyGraded,
                'hidden'=>$request->hidden,
            ]);
            return HelperController::api_response_format(200, $grade_category,'Grade Category is created successfully');

    }

    
    public function GetGradeCategory(Request $request)
    {
        if($request->filled('id')){
            $gradeCategory=GradeCategory::with('Child')->where('id',$request->id)->first();}
            else{
            $gradeCategory=GradeCategory::with('Child')->get();}
        return HelperController::api_response_format(200, $gradeCategory);

    }

    public function UpdateGradeCategory(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'course_segment_id' => 'exists:course_segments,id',
            'parent' => 'exists:grade_categories,id',
            'aggregation' => 'integer',
            'aggregatedOnlyGraded' => 'integer',
            'hidden'=>'integer'
            ]);
        $grade_category= GradeCategory::find($request->id);
        $grade_category->name=$request->name;
        if($request->filled('course_segment_id')){
        $grade_category->course_segment_id=$request->course_segment_id;}
        $grade_category->parent=$request->parent;
        $grade_category->hidden=$request->hidden;
        $grade_category->save();
        return HelperController::api_response_format(200, $grade_category,'Grade Category is updated successfully');
    }

    public function deleteGradeCategory(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:grade_categories,id'
        ]);
        $gradeCategory=GradeCategory::find($request->id);
        $gradeCategory->delete();
        return HelperController::api_response_format(200, null,'Grade Category is deleted successfully');

    }
    public function MoveToParentCategory(Request $request){
        $request->validate([
            'id' => 'required|exists:grade_categories,id',
            'parent'=>'required|exists:grade_categories,id',
        ]);
        $GardeCategory=GradeCategory::find($request->id);
        $GardeCategory->update([
            'parent' => $request->parent,
        ]);
        return HelperController::api_response_format(200, $GardeCategory,'Grade Category is moved successfully');

    }
    public function GetCategoriesFromCourseSegments(Request $request){
     $grade=CourseSegment::GradeCategoryPerSegmentbyId($request->id);
     return $grade;
   
    }
}
