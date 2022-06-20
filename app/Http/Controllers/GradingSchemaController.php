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
    public function __construct()
    {
        // $this->middleware(['permission:grade/get-scheme'],   ['only' => ['index','show']]);
        // $this->middleware('permission:grade/create-scheme', ['only' => ['store']]);      
        // $this->middleware('permission:grade/apply-scheme', ['only' => ['applyGradingSchema']]);          
    }

    /**
     * list grading schema
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request){
        return response()->json(['message' => __('messages.grade_schema.list'), 'body' => GradingSchema::get()->paginate(HelperController::GetPaginate($request)) ], 200);
    }

    public function show($id){
        $gradingSchema = GradingSchema::with(['gradeCategoryParents'])->find($id);

        $gradeCategoriesList = GradeCategory::where('grading_schema_id' ,$id)->where('type','category')->get()->toArray();
        $gradeCategories = GradeCategory::where('grading_schema_id' ,$id)->whereNull('parent')->with('Children','GradeItems')->get()->toArray();
        if($gradingSchema)
            return response()->json(['message' => __('messages.grade_schema.list'), 'body' => ['grade_categories_list'=>$gradeCategoriesList,'grade_setup'=>$gradeCategories] ], 200);
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
            'name'=>$gradingSchemaRequest->name,
            'description' => $gradingSchemaRequest->description
        ]);



        // $gradingSchemaService = new GradingSchemaService();

        // $gradeSchemaDefault = $gradingSchemaService->importGradeSchemaDefault($gradingSchemaRequest['grade_categories'],null,$gradingSchema->id,true);

        return response()->json(['message' => __('messages.grading_schema.add'), 'body' => null ], 200);
    }

    public function applyGradingSchema($id,Request $gradingSchemaRequest){
        $data = $gradingSchemaRequest->toArray();
        if(is_array($data) && count($data)>0)
        {
            foreach($data as $chain){
                $courses = Course::with('level')->where('level_id',$chain['level_id'])->get();

                if(!empty($chain['courses']))
                    $courses = Course::with('level')->whereIn('id',$chain['courses'])->get();
        
                foreach($courses as $course){
                    GradingSchemaCourse::create([
                        'course_id'=>$course->id,
                        'level_id'=>$chain['level_id'],
                        'grading_schema_id'=>$id
                    ]);
                }

                GradingSchema::whereId($id)->update(['is_drafted'=>0]);
                $gradingSchemaService = new GradingSchemaService();
                
                $categories = GradeCategory::where('grading_schema_id' ,$id)->whereNull('parent')->with('Children','GradeItems')->get();
                $results = $gradingSchemaService->importGradeSchema($categories,$courses,null,true);

                if($results)
                return response()->json(['message' => __('messages.grading_schema.add'), 'body' => null ], 200);
            }
        }else{
            return response()->json(['message' => __('messages.error.not_found'), 'body' => [] ], 400);
        }

    }

    /**
    * update grading schema
    * @return \Illuminate\Http\Response
    */
    public function update($id,GradingSchemaRequest $gradingSchemaRequest){
        
    }
}