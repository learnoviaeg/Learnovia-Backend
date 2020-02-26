<?php

namespace Modules\Survey\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use App\Http\Controllers\HelperController;
use App\Component;
use Modules\Survey\Entities\Survey;
use Carbon\Carbon;

class SurveyController extends Controller
{

    public function install_survey()
    {
        if (\Spatie\Permission\Models\Permission::whereName('survey/add')->first() != null) {
            return \App\Http\Controllers\HelperController::api_response_format(400, null, 'This Component is installed before');
        }

        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'survey/add','title' => 'add survey']);
        
        $role = \Spatie\Permission\Models\Role::find(1);
        $role->givePermissionTo('survey/add');
        
        Component::create([
            'name' => 'Survey',
            'module'=>'Survey',
            'model' => 'Survey',
            'type' => 3,
            'active' => 0
        ]);

        return \App\Http\Controllers\HelperController::api_response_format(200, null, 'Component Installed Successfully');
    }

    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index()
    {
        return view('survey::index');
    }

    /**
     * Show the form for creating a new resource.
     * @return Response
     */
    public function assignSuvey($id)
    {
        $survey=Survey::find($id);

        $years=[];
        $types=[];
        $levels=[];
        $classes=[];
        $segments=[];
        $courses=[];
        if($survey->years)
            $years = unserialize($survey->years);
        if($survey->types)
            $types = unserialize($survey->types);
        if($survey->levels)
            $levels = unserialize($survey->levels);
        if($survey->years)
            $segments = unserialize($survey->segments);
        if($survey->classes)
            $classes = unserialize($survey->classes);
        if($survey->courses)
            $courses = unserialize($survey->courses);
        
        $req=new Request([
            'years' => $years,
            'types' => $types,
            'segments' => $segments,
            'levels' => $levels,
            'classes' => $classes,
            'courses' => $courses,
        ]);

        $courseSegs=GradeCategoryController::getCourseSegmentWithArray($req);
        $users=Enroll::whereIn('course_segment',$courseSegs)->pluck('user_id')->unique();
        
        foreach($users as $user)
        {
            $userSurvey[]=UserSuervey::firstOrCreate([
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
            'years' => 'array',
            'years.*' => 'nullable|exists:academic_years,id',
            'types' => 'array',
            'types.*' => 'nullable|exists:academic_types,id',
            'levels' => 'array',
            'levels.*' => 'nullable|exists:levels,id',
            'classes' => 'array',
            'classes.*' => 'nullable|exists:classes,id',
            'segments' => 'array',
            'segments.*' => 'nullable|exists:segments,id',
            'courses' => 'array',
            'courses.*' => 'nullable|exists:courses,id'
        ]);

        $survey = Survey::create([
            'name' => $request->name,
            'template' => $request->template,
            'start_date' => isset($request->start_date) ? $request->start_date : Carbon::now(),
            'end_date' => isset($request->end_date) ? $request->end_date : null,
            'years' => isset($request->years) ? serialize($request->years) : null,
            'types' => isset($request->types) ? serialize($request->types) : null,
            'levels' => isset($request->levels) ? serialize($request->levels) : null,
            'segments' => isset($request->segments) ? serialize($request->segments) : null,
            'classes' => isset($request->classes) ? serialize($request->classes) : null,
            'courses' => isset($request->courses) ? serialize($request->courses) : null,
            'created_by' => Auth::id()
        ]);

        self::assignSuvey($survey->id);

        return HelperController::api_response_format(200, $survey, 'Survey Created and assigned Successfully');
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Response
     */
    public function show($id)
    {
        return view('survey::show');
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Response
     */
    public function edit($id)
    {
        return view('survey::edit');
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Response
     */
    public function destroy($id)
    {
        //
    }
}
