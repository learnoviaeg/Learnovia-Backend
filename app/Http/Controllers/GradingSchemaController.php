<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Segment;
use App\GradingSchema;
use App\GradingSchemaCourse;
use App\Course;
use App\Level;
use App\GradeCategory;
use App\Services\GradingSchemaService;
use stdClass;
use App\Http\Requests\GradingSchemaRequest;

class GradingSchemaController extends Controller
{

    /**
     * list grading schema
     *
     * @return \Illuminate\Http\Response
     */
    public function index(){

        return response()->json(['message' => __('messages.grade_schema.list'), 'body' => GradingSchema::get() ], 200);
    }

    public function show($id){
        $gradingSchema = GradingSchema::with(['gradeCategoryParents'])->find($id);

        if($gradingSchema)
            return response()->json(['message' => __('messages.grade_schema.list'), 'body' => $gradingSchema ], 200);
        else
            return response()->json(['message' => __('messages.error.not_found'), 'body' => [] ], 400);

    }

    /**
     * Create grading schema
     *
     * @return \Illuminate\Http\Response
     */
    public function store(GradingSchemaRequest $gradingSchemaRequest){
        $gradingSchema = GradingSchema::create([
            'name'=>$gradingSchemaRequest->name
        ]);



        $gradingSchemaService = new GradingSchemaService();

        $gradeSchemaDefault = $gradingSchemaService->importGradeSchemaDefault($gradingSchemaRequest['grade_categories'],null,$gradingSchema->id,true);

        return response()->json(['message' => __('messages.grading_schema.add'), 'body' => null ], 200);
    }

    public function applyGradingSchema(Request $gradingSchemaRequest){
        if(!empty($gradingSchemaRequest->courses)){
            $courses = Course::with('level')->whereIn('id',$gradingSchemaRequest->courses)->get();
        }
        else
        {
            $courses = Course::with('level')->where('level_id',$gradingSchemaRequest->level_id)->get();
        }

        foreach($courses as $course){
            GradingSchemaCourse::create([
                'course_id'=>$course->id,
                'level_id'=>$gradingSchemaRequest->level_id,
                'grading_schema_id'=>$gradingSchema->id
            ]);
        }

        $results = $gradingSchemaService->importGradeSchema($gradingSchemaRequest['grade_categories'],$courses,null,true);
    }

    /**
    * update grading schema
    * @return \Illuminate\Http\Response
    */
    public function update(GradingSchemaRequest $gradingSchemaRequest){
        
    }
}