<?php

namespace Modules\Survey\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use App\Http\Controllers\HelperController;
use App\Http\Controllers\GradeCategoryController;
use App\Component;
use App\Segment;
use App\AcademicYear;
use App\Enroll;
use Auth;
use Modules\Survey\Entities\Survey;
use Modules\Survey\Entities\UserSurvey;
use Carbon\Carbon;

class SurveyController extends Controller
{

    public function install_survey()
    {
        if (\Spatie\Permission\Models\Permission::whereName('survey/add')->first() != null) {
            return \App\Http\Controllers\HelperController::api_response_format(400, null, 'This Component is installed before');
        }

        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'survey/add','title' => 'add survey']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'survey/submit','title' => 'submit']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'survey/my-surveys','title' => 'get my surveys', 'dashboard' => 1, 'icon' => 'statistics']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'survey/view-all-submissions','title' => 'view all submissions']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'template/get','title' => 'get template']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'survey/assigned-surveys','title' => 'get assigned surveys']);

        $role = \Spatie\Permission\Models\Role::find(1);
        $role->givePermissionTo('survey/add');
        $role->givePermissionTo('survey/submit');
        $role->givePermissionTo('survey/my-surveys');
        $role->givePermissionTo('survey/view-all-submissions');
        $role->givePermissionTo('template/get');
        $role->givePermissionTo('survey/assigned-surveys');
        
        Component::create([
            'name' => 'Survey',
            'module'=>'Survey',
            'model' => 'Survey',
            'type' => 4,
            'active' => 0
        ]);

        return \App\Http\Controllers\HelperController::api_response_format(200, null, 'Component Installed Successfully');
    }

    public function assignSuvey($id)
    {
        $survey=Survey::find($id);

        $req=new Request([
            'year' => $survey->year,
            'type' => $survey->types,
            'segments' => $survey->segments,
            'levels' => $survey->levels,
            'classes' => $survey->classes,
            'courses' => $survey->courses,
        ]);
        $courseSegs=GradeCategoryController::getCourseSegmentWithArray($req);
        if($courseSegs != null)
            $users=Enroll::whereIn('course_segment',$courseSegs->toArray())->pluck('user_id')->unique();
        
        foreach($users as $user)
        {
            $userSurvey[]=UserSurvey::firstOrCreate([
                'user_id' => $user,
                'survey_id' => $id
            ]);
        }
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|min:3',
            'start_date' => 'date',
            'end_date' => 'after:' . Carbon::now(),
            'template' => 'required|integer|boolean',
            'template_id' => 'exists:surveys,id,template,1',
            'year' => 'nullable|exists:academic_years,id',
            'types' => 'array',
            'types.*' => 'nullable|exists:academic_types,id',
            'levels' => 'array',
            'levels.*' => 'nullable|exists:levels,id',
            'classes' => 'array',
            'classes.*' => 'nullable|exists:classes,id',
            'segments' => 'array',
            'segments.*' => 'nullable|exists:segments,id',
            'courses' => 'array',
            'courses.*' => 'nullable|exists:courses,id',
            'Quest' => 'array',
            'Quest.*' => 'integer|exists:questions,id',
        ]);

        $survey = Survey::create([
            'name' => $request->name,
            'template' => $request->template,
            'start_date' => isset($request->start_date) ? $request->start_date : Carbon::now(),
            'end_date' => isset($request->end_date) ? $request->end_date : null,
            'year' => isset($request->year) ? $request->year : null,
            'types' => isset($request->types) ? serialize($request->types) : null,
            'levels' => isset($request->levels) ? serialize($request->levels) : null,
            'segments' => isset($request->segments) ? serialize($request->segments) : null,
            'classes' => isset($request->classes) ? serialize($request->classes) : null,
            'courses' => isset($request->courses) ? serialize($request->courses) : null,
            'created_by' => Auth::id()
        ]);

        $request['survey_id']=$survey->id;
        self::assignSuvey($survey->id);
        $QSC= new QuestionSurveyController();
        $QSC->QuestionSurvey($request);

        return HelperController::api_response_format(200, $survey, 'Survey Created and assigned Successfully');
    }
   
    public function get_template(Request $request)
    {
        $request->validate([
            'template_id' => 'exists:surveys,id,template,1',
        ]);
        $templates = Survey::where('template',1)->with(['Question.question_type','Question.question_category','Question.question_answer'])->get();
        if($request->filled('template_id'))
            $templates = Survey::where('id',$request->template_id)->with('Question.question_type','Question.question_category','Question.question_answer')->first();
        return HelperController::api_response_format(200, $templates, 'Templates .....');
    }
}
