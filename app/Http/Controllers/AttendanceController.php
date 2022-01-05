<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Attendance;
use Auth;
use App\GradeCategory;

class AttendanceController extends Controller
{
    public function __construct()
    {
        $this->middleware(['permission:attendance/add'],   ['only' => ['store']]);
        $this->middleware(['permission:attendance/delete'],   ['only' => ['delete']]);
        $this->middleware(['permission:attendance/get'],   ['only' => ['update']]);
        $this->middleware(['permission:attendance/edit'],   ['only' => ['index','show']]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $request->validate([
            'attendance_type' => 'in:Per Session,Daily',
            'year_id' => 'exists:academic_years,id',
            'type_id' => 'exists:academic_types,id',
            'segment_id' => 'exists:segments,id',
            'level_id' => 'exists:levels,id',
            'course_id' => 'exists:courses,id',
            'grade_cat_id' => 'exists:grade_categories,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
        ]);
        $attendance=Attendance::where('id', '!=', null);

        if(isset($request->attendance_type))
            $attendance->where('attendance_type',$request->attendance_type);

        if(isset($request->year_id))
            $attendance->where('year_id',$request->year_id);

        if(isset($request->type_id))
            $attendance->where('type_id',$request->type_id);

        if(isset($request->level_id))
            $attendance->where('level_id',$request->level_id);

        if(isset($request->segment_id))
            $attendance->where('segment_id',$request->segment_id);

        if(isset($request->course_id))
            $attendance->where('course_id',$request->course_id);

        if(isset($request->grade_cat_id))
            $attendance->where('grade_cat_id',$request->grade_cat_id);

        if(isset($request->start_date))
            $attendance->where('start_date','>=', $request->start_date);

        if(isset($request->end_date))
            $attendance->where('end_date','<', $request->end_date);

        return HelperController::api_response_format(200 , $attendance->get()->paginate(HelperController::GetPaginate($request)) , __('messages.attendance.list'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'attendance_type' => 'required|in:Per Session,Daily',
            'year_id' => 'required|exists:academic_years,id',
            'type_id' => 'required|exists:academic_types,id',
            'segment_id' => 'required|exists:segments,id',
            'attendance' => 'array|required',
            'attendance.*.level_id' => 'required|exists:levels,id',
            // 'attendance.*.level_id.*' => 'exists:levels,id', //will be array
            'attendance.*.course_id' => 'required|exists:courses,id',
            // 'attendance.*.course_id.*' => 'exists:courses,id', //will be array
            'attendance.*.grade_cat_id' => 'required_if:is_graded,==,1|exists:grade_categories,id',
            'is_graded' => 'required|in:0,1',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'min_grade' => 'nullable',
            'gradeToPass' => 'nullable',
            'max_grade' => 'required',
        ]);


        foreach($request->attendance as $attend)
        {
            // foreach($attend['level_id'] as $level)
            // {
                // foreach($attend['course_id'] as $course)
                // {
                    $top_parent_category = GradeCategory::where('course_id',$attend['course_id'])->whereNull('parent')->where('type','category')->first();
                    $attendance=Attendance::firstOrCreate([
                        'name' => $request->name,
                        'attendance_type' => $request->attendance_type,
                        'year_id' => $request->year_id,
                        'type_id' => $request->type_id,
                        'segment_id' => $request->segment_id,
                        'level_id' => $attend['level_id'], //$level
                        'course_id' => $attend['course_id'], //$course
                        'is_graded' => $request->is_graded,
                        'grade_cat_id' => isset($attend['grade_cat_id']) ? $attend['grade_cat_id']: $top_parent_category->id,
                        'start_date' =>  $request->start_date,
                        'end_date' => $request->end_date,
                        'min_grade' =>  $request->min_grade,
                        'gradeToPass' => $request->gradeToPass,
                        'max_grade' => $request->max_grade,
                        'created_by' => Auth::id()
                    ]);

                    $gradeCat = GradeCategory::firstOrCreate([
                        'name' => $request->name,
                        'course_id' => $attend['course_id'],
                        'instance_id' =>$attendance->id,
                        'instance_type' => 'Attendance',
                        'item_type' => 'Attendance',
                        'type' => 'item',
                        'parent' => isset($attend['grade_cat_id']) ? $attend['grade_cat_id']: $top_parent_category->id,
                        'max'    => $request->max_grade,
                        'weight_adjust' => ((bool) $request->is_graded == false) ? 1 : 0,
                        'weights' => ((bool) $request->is_graded == false) ? 0 : null,
                    ]);
                // }
            // }
        }

        return HelperController::api_response_format(200 , null , __('messages.attendance.add'));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $attendance=Attendance::find($id);
        return HelperController::api_response_format(200 , $attendance , null);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'string',
            'attendance_type' => 'in:Per Session,Daily',
            'year_id' => 'exists:academic_years,id',
            'type_id' => 'exists:academic_types,id',
            'segment_id' => 'exists:segments,id',
            'level_id' => 'exists:levels,id',
            'course_id' => 'exists:courses,id',
            'grade_cat_id' => 'exists:grade_categories,id',
            'is_graded' => 'in:0,1',
            'start_date' => 'date',
            'end_date' => 'date|after:start_date',
            'min_grade' => 'nullable',
            'gradeToPass' => 'nullable',
            'max_grade' => 'nullable',
        ]);

        $attendance=Attendance::find($id);
        // dd($attendance->attendance_type);

        $attendance->update([
            'name' => ($request->name) ? $request->name : $attendance->name,
            'attendance_type' => ($request->attendance_type) ? $request->attendance_type : $attendance->attendance_type,
            'year_id' => ($request->year_id) ? $request->year_id : $attendance->year_id,
            'type_id' => ($request->type_id) ? $request->type_id : $attendance->type_id,
            'segment_id' => ($request->segment_id) ? $request->segment_id : $attendance->segment_id,
            'level_id' => ($request->level) ? $request->level : $attendance->level_id,
            'course_id' => ($request->course) ? $request->course : $attendance->course_id,
            'is_graded' => ($request->is_graded) ? $request->is_graded : $attendance->is_graded,
            'grade_cat_id' => ($request->grade_cat_id) ? $request->grade_cat_id : $attendance->grade_cat_id,
            'start_date' =>  ($request->start_date) ? $request->start_date : $attendance->start_date,
            'end_date' => ($request->end_date) ? $request->end_date : $attendance->end_date,
            'min_grade' =>  ($request->min_grade) ? $request->min_grade : $attendance->min_grade,
            'gradeToPass' => ($request->gradeToPass) ? $request->gradeToPass : $attendance->gradeToPass,
            'max_grade' => ($request->max_grade) ? $request->max_grade : $attendance->max_grade,
        ]);

        return HelperController::api_response_format(200 , null , __('messages.attendance.update'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $attendance=Attendance::find($id);
        $attendance->delete();

        return HelperController::api_response_format(200 , null , __('messages.attendance.delete'));
    }
}