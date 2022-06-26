<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Segment;
use Auth;
use App\GradingSchema;
use App\GradingSchemaCourse;
use App\GradingSchemaLevel;
use App\Course;
use App\Level;
use App\GradeCategory;
use App\GradingSchemaScale;
use App\course_scales;
use App\Services\GradingSchemaService;
use stdClass;
use App\Http\Requests\GradingSchemaRequest;

class GradingSchemaController extends Controller
{
    public function __construct()
    {
        $this->middleware(['permission:grade/get-scheme'],   ['only' => ['index','show']]);
        $this->middleware('permission:grade/create-scheme', ['only' => ['store']]);      
        $this->middleware('permission:grade/apply-scheme', ['only' => ['applyGradingSchema']]);          
    }

    /**
     * list grading schema
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request){

        $grading=GradingSchema::where('id','!=',null);
        $all=[];
        foreach($grading->cursor() as $grade){
            $callback = function ($qu) use ($request,$grade) {
                $qu->whereIn('id',$grade->courses->pluck('id')->toArray());
                if(isset($request->courses))
                    $qu->whereIn('id',$request->courses);
            };
            $callback2 = function ($q) use ($request) {
                if(isset($request->levels))
                    $q->whereIn('level_id',$request->levels);
            };
            $check=GradingSchema::whereId($grade->id)//->whereHas('levels.courses', $callback)->whereHas('levels',$callback2)
                ->with(['levels'=>$callback2,'levels.courses' => $callback ,'GradingSchemaLevel.segment.academicType','GradingSchemaLevel.segment.academicYear'])->first();
            if(isset($check))
                $all[]=$check;
        }

        return response()->json(['message' => __('messages.grade_schema.list'), 'body' => collect($all)->paginate(HelperController::GetPaginate($request)) ], 200);
    }

    public function show($id){

        $gradingSchema = GradingSchema::find($id);

        $callback = function ($qu) use ($gradingSchema) {
            $qu->whereIn('id',$gradingSchema->courses->pluck('id')->toArray());
        };

        $gradingSchema = GradingSchema::whereId($id)//->whereHas('levels.courses',$callback)
            ->with(['levels.courses' => $callback,'gradeCategoryParents','GradingSchemaLevel.segment','GradingSchemaLevel.segment.academicType','GradingSchemaLevel.segment.academicYear','scales.details'])
            ->first();

        $gradeCategoriesList = GradeCategory::where('grading_schema_id' ,$id)->where('type','category')->get()->toArray();
        $gradeCategories = GradeCategory::where('grading_schema_id' ,$id)->whereNull('parent')->with('Children','GradeItems')->get()->toArray();
        if($gradingSchema)
            return response()->json(['message' => __('messages.grade_schema.list'), 'body' => ['grade_categories_list'=>$gradeCategoriesList,'grade_setup'=>$gradeCategories,'scheme_details' => $gradingSchema] ], 200);
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

        foreach($gradingSchemaRequest->chain as $chain){
            $courses = Course::with('level')->where('segment_id',$chain['segment_id'])->where('level_id',$chain['level_id'])->get();

                if(!empty($chain['courses'])){
                    
                }
                    $courses = Course::with('level')->where('segment_id',$chain['segment_id'])->whereIn('id',$chain['courses'])->get();

                foreach($courses as $course){
                    GradingSchemaCourse::firstOrCreate([
                        'course_id'=>$course->id,
                        'grading_schema_id'=>$gradingSchema->id
                    ]);
                }
                
                GradingSchemaLevel::firstOrCreate([
                    'level_id'=>$chain['level_id'],
                    'segment_id'=>$chain['segment_id'],
                    'grading_schema_id'=>$gradingSchema->id
                ]);
        }

        if($gradingSchemaRequest->grade_schema_reference){
            $gradeSchema = GradingSchema::with('gradeCategoryParents')->whereId($gradingSchemaRequest->grade_schema_reference)->first()->toArray();

            $gradingSchemaService = new GradingSchemaService();
            $gradeSchemaDefault = $gradingSchemaService->importGradeSchemaDefault($gradeSchema['grade_category_parents'],null,$gradingSchema->id,true);
        }

        return response()->json(['message' => __('messages.grading_schema.add'), 'body' => null ], 200);
    }

    public function applyGradingSchema($id){
        
                // $courses = Course::with('level')->where('segment_id',$chain['segment_id'])->where('level_id',$chain['level_id'])->get();
                $courses = GradingSchema::where('id',$id)//->whereHas('levels.courses', $callback)
                ->with(['courses'])->first()->courses;
                //from here assign login
                GradingSchema::whereId($id)->update(['is_drafted'=>0]);
                $gradingSchemaService = new GradingSchemaService();
                
                $categories = GradeCategory::where('grading_schema_id' ,$id)->whereNull('parent')->with('Children','GradeItems')->get();
                $results[] = $gradingSchemaService->importGradeSchema($categories,$courses,null,true);
                
            if($results)
                return response()->json(['message' => __('messages.grading_schema.add'), 'body' => null ], 200);
    }

    public function applyScale($id,Request $request){
        $request = $request->toArray();
        if(isset($request['scales']) && is_array($request['scales'])){
            $courses = GradingSchemaCourse::where('grading_schema_id',$id)->get()->pluck('course_id');
            foreach($request['scales'] as $scale){
                GradingSchemaScale::updateOrCreate([
                    'scale_id'=>$scale,
                    'grading_schema_id'=>$id
                ]);

                foreach($courses as $courseId){
                    course_scales::firstOrCreate([
                        'course_id' => $courseId,
                        'scale_id' => $scale,
                    ]);
                }
            }

            return response()->json(['message' => __('messages.grading_schema.scales_assigned'), 'body' => null ], 200);
        }
    }

    /**
    * update grading schema
    * @return \Illuminate\Http\Response
    */
    public function update($id,GradingSchemaRequest $gradingSchemaRequest){
        
    }

    public function destroy($id)
    {
        $gradingScheme=GradingSchema::find($id);
        if($gradingScheme->is_drafted)
        {
            if(Auth::user()->can('grade/delete-scheme'))
            {
                GradeCategory::where('grading_schema_id',$id)->delete();
                $gradingScheme->delete();
                
                return response()->json(['message' => __('messages.grading_schema.delete'), 'body' => null ], 200);
            }
        }
        else if(Auth::user()->can('grade/force-delete-scheme'))
        {
            $gradeCategories=GradeCategory::where('grading_schema_id',$id)->pluck('id');
            GradeCategory::whereIn('reference_category_id',$gradeCategories)->delete();
            GradeCategory::where('grading_schema_id',$id)->delete();
            $gradingScheme->delete();

            return response()->json(['message' => __('messages.grading_schema.delete'), 'body' => null ], 200);
        }
        return response()->json(['message' => __('messages.grading_schema.canNot_delete'), 'body' => null ], 200);
    }
}