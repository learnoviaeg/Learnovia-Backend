<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Attendance;
use Auth;
use App\GradeCategory;
use App\AttendanceLevel;
use App\AttendanceCourse;

class AttendanceController extends Controller
{
    public function __construct()
    {
        $this->middleware(['permission:attendance/add'],   ['only' => ['store']]);
        $this->middleware(['permission:attendance/delete'],   ['only' => ['delete']]);
        $this->middleware(['permission:attendance/edit'],   ['only' => ['update']]);
        $this->middleware(['permission:attendance/viewAllAttendance'],   ['only' => ['index','show']]);
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
            'start_date' => 'date',
            'end_date' => 'date|after:start_date',
        ]);
        $attendance=Attendance::where('id', '!=', null);

        if(isset($request->attendance_type))
            $attendance->where('attendance_type',$request->attendance_type);

        if(isset($request->year_id))
            $attendance->where('year_id',$request->year_id);

        if(isset($request->type_id))
            $attendance->where('type_id',$request->type_id);

        // if(isset($request->level_id))
        //     $attendance->where('level_id',$request->level_id);

        if(isset($request->segment_id))
            $attendance->where('segment_id',$request->segment_id);

        // if(isset($request->course_id))
        //     $attendance->where('course_id',$request->course_id);

        // if(isset($request->grade_cat_id))
        //     $attendance->where('grade_cat_id',$request->grade_cat_id);

        if(isset($request->start_date))
            $attendance->where('start_date','>=', $request->start_date);

        if(isset($request->end_date))
            $attendance->where('end_date','<', $request->end_date);

        $all=$attendance->with(['levels.courses','gradeCategory'])->get();
        foreach($all as $attendeence){
            foreach($attendeence['levels'] as $attend){
                foreach($attend->courses as $key => $attendCourse)
                {
                    if(!in_array($attendCourse->id, $attendeence->courses->pluck('id')->toArray()))
                        unset($attend->courses[$key]);
                }
            }
        }

        return HelperController::api_response_format(200 , $all->paginate(HelperController::GetPaginate($request)) , __('messages.attendance.list'));
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
            'max_grade' => 'required_if:is_graded,==,1',
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
                        'is_graded' => $request->is_graded,
                        'start_date' =>  $request->start_date,
                        'end_date' => $request->end_date,
                        'min_grade' =>  ($request->is_graded==1) ? $request->min_grade : null,
                        'gradeToPass' => ($request->is_graded==1) ? $request->gradeToPass : null,
                        'max_grade' => ($request->is_graded==1) ? $request->max_grade : null ,
                        'created_by' => Auth::id()
                    ]);

                    $attendance->levels()->sync($attend['level_id'],false); //b t3mel duplicate
                    // dd($attend['level_id']);
                    // $dd=AttendanceLevel::firstOrCreate([
                    //     'attendance_id' => $attendance->id,
                    //     'level_id' => $attend['level_id'],
                    // ]);
                    // dd($dd);

                    AttendanceCourse::firstOrCreate([
                        'course_id' => $attend['course_id'],
                        'grade_cat_id' => ($request->is_graded == 1) ? $attend['grade_cat_id']: $top_parent_category->id,
                        'attendance_id' => $attendance->id
                    ]);

                    $gradeCat = GradeCategory::firstOrCreate([
                        'name' => $request->name,
                        'course_id' => $attend['course_id'],
                        'instance_id' =>$attendance->id,
                        'instance_type' => 'Attendance',
                        'item_type' => 'Attendance',
                        'type' => 'item',
                        'parent' => ($request->is_graded ==1) ? $attend['grade_cat_id']: $top_parent_category->id,
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
        $attend=Attendance::find($id);
        // $attendance=Attendance::whereId($id)->with('levels.courses')->whereHas('levels.courses',
        // function($query) use($attend){
        //         return $query->whereIn('id',$attend->courses->pluck('id')->toArray());
        // })->get();
        $attendance=Attendance::whereId($id)->with(['levels.courses','gradeCategory'])->first();
        foreach($attendance['levels'] as $attend)
        {
            foreach($attend['courses']as $key => $attendCourse)
            {
                if(!in_array($attendCourse->id, $attendance->courses->pluck('id')->toArray()))
                    unset($attend['courses'][$key]);
            }
        }
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
            'attendance' => 'array|required',
            'attendance.*.level_id' => 'required|exists:levels,id',
            // 'attendance.*.level_id.*' => 'exists:levels,id', //will be array
            'attendance.*.course_id' => 'required|exists:courses,id',
            // 'attendance.*.course_id.*' => 'exists:courses,id', //will be array
            'attendance.*.grade_cat_id' => 'required_if:is_graded,==,1|exists:grade_categories,id',
            'is_graded' => 'in:0,1',
            'start_date' => 'date',
            'end_date' => 'date|after:start_date',
            'min_grade' => 'nullable',
            'gradeToPass' => 'nullable',
            'max_grade' => 'nullable',
        ]);

        $attendance=Attendance::find($id);
        // dd($attendance->attendance_type);

        foreach($request->attendance as $attend)
        {
            // foreach($attend['level_id'] as $level)
            // {
                // foreach($attend['course_id'] as $course)
                // {
                    $top_parent_category = GradeCategory::where('course_id',$attend['course_id'])->whereNull('parent')->where('type','category')->first();
                    $attendance->updateOrCreate([
                        'name' => isset($request->name) ? $request->name : $attendance->name,
                        'attendance_type' => isset($request->attendance_type) ? $request->attendance_type : $attendance->attendance_type,
                        'year_id' => isset($request->year_id) ? $request->year_id :  $attendance->year_id,
                        'type_id' => isset($request->type_id) ? $request->type_id : $attendance->type_id,
                        'segment_id' => isset($request->segment_id)? $request->segment_id : $attendance->segment_id
                    ],[
                        'is_graded' => isset($request->is_graded) ? $request->is_graded : $attendance->is_graded,
                        'start_date' =>  isset($request->start_date) ? $request->start_date : $attendance->start_date,
                        'end_date' => isset($request->end_date) ? $request->end_date : $attendance->end_date,
                        'min_grade' =>  isset($request->min_grade) ? $request->min_grade : $attendance->min_grade,
                        'gradeToPass' => isset($request->gradeToPass) ? $request->gradeToPass: $attendance->gradeToPass,
                        'max_grade' => isset($request->max_grade) ? $request->max_grade : $attendance->max_grade,
                        'created_by' => $attendance->created_by 
                    ]);

                    // $attendance->levels()->attach($attend['level_id']); //b t3mel duplicate
                    // AttendanceLevel::updateOrCreate([
                    //     'level_id' => $attend['level_id'],
                    //     'attendance_id' => $attendance->id
                    // ]);

                    // AttendanceCourse::updateOrCreate([
                    //     'course_id' => $attend['course_id'],
                    //     'grade_cat_id' => isset($attend['grade_cat_id']) ? $attend['grade_cat_id']: $top_parent_category->id,
                    //     'attendance_id' => $attendance->id
                    // ]);

                    $gradeCat = GradeCategory::where('instance_type','Attendance') -> where('type','item')
                                    ->where('instance_id',$attendance->id)->update([
                        'name' => isset($request->name) ? $request->name : $attendance->name,
                        'max' => isset($request->max_grade) ? $request->max_grade : $attendance->max_grade,
                        'weight_adjust' => ((bool) $request->is_graded == false) ? 1 : 0,
                        'weights' => ((bool) $request->is_graded == false) ? 0 : null,
                    ]);
                // }
            // }
        }

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
