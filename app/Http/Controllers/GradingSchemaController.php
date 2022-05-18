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
     * Create grading schema
     *
     * @return \Illuminate\Http\Response
     */
    public function store(GradingSchemaRequest $gradingSchemaRequest){
        $gradingSchema = GradingSchema::create([
            'name'=>$gradingSchemaRequest->name
        ]);

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

        $gradingSchemaService = new GradingSchemaService();

        $gradeSchemaDefault = $gradingSchemaService->importGradeSchemaDefault($gradingSchemaRequest['grade_categories'],null,$gradingSchema->id,true);
        $results = $gradingSchemaService->importGradeSchema($gradingSchemaRequest['grade_categories'],$courses,null,true);

        dd($results);
        return response()->json(['message' => __('messages.grade_category.add'), 'body' => null ], 200);
    }
}