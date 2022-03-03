<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Attendance;
use Auth;
use App\Enroll;
use App\UserGrader;
use App\GradeCategory;
use App\AttendanceLevel;
use App\AttendanceCourse;

class AttendanceController extends Controller
{
    public function __construct()
    {
        $this->middleware(['permission:attendance/add'],   ['only' => ['store']]);
        $this->middleware(['permission:attendance/delete'],   ['only' => ['destroy']]);
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

        if(isset($request->years))
            $attendance->whereIn('year_id',$request->years);

        if(isset($request->types))
            $attendance->whereIn('type_id',$request->types);

        if(isset($request->segments))
            $attendance->whereIn('segment_id',$request->segments);

        if(isset($request->start_date))
            $attendance->where('start_date','>=', $request->start_date);

        if(isset($request->end_date))
            $attendance->where('end_date','<', $request->end_date);

            // return $attendance->with('levels')->get();

        $all=[];
        foreach($attendance->cursor() as $attendeence){
            $callback = function ($qu) use ($request,$attendeence) {
                $qu->whereIn('id',$attendeence->courses->pluck('id')->toArray());
                if(isset($request->courses))
                    $qu->whereIn('id',$request->courses);
            };
            $callback2 = function ($q) use ($request) {
                if(isset($request->levels))
                    $q->whereIn('level_id',$request->levels);
            };
            $check=Attendance::whereId($attendeence->id)->whereHas('levels.courses', $callback)->whereHas('levels',$callback2)
            ->with(['levels'=>$callback2,'levels.courses' => $callback , 'attendanceStatus'])->first();
            if(isset($check))
                $all[]=$check;
        }

        return HelperController::api_response_format(200 , collect($all)->paginate(HelperController::GetPaginate($request)) , __('messages.attendance.list'));
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
                        'created_by' => Auth::id(),
                        'attendance_status' => 1
                    ]);

                    $attendance->levels()->sync($attend['level_id'],false); //b t3mel duplicate

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
                        'max'    => ($request->is_graded ==1) ? $request->max_grade :null,
                        'weight_adjust' => ((bool) $request->is_graded == false) ? 1 : 0,
                        'weights' => ((bool) $request->is_graded == false) ? 0 : null,
                    ]);

                    // $gradeCat->index=GradeCategory::where('parent',$gradeCat->parent)->max('index')+1;
                    // $gradeCat->save();

                    $users = Enroll::where('role_id',3)->where('course',$attend['course_id'])->pluck('user_id');
                    foreach($users as $user_id)
                    {
                        UserGrader::firstOrCreate([
                            'user_id'   => $user_id,
                            'item_type' => 'Item',
                            'item_id'   => $gradeCat->id
                        ],
                        [
                            'grade'     => null
                        ]);
                    } 
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

        $callback = function ($qu) use ($attend) {
            $qu->whereIn('id',$attend->courses->pluck('id')->toArray());
        };

        $attendance=Attendance::whereId($id)->whereHas('levels.courses', $callback)->with(['levels.courses' => $callback, 'attendanceStatus'])->first();

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
            'attendance' => 'array',
            'attendance.*.level_id' => 'exists:levels,id',
            // 'attendance.*.level_id.*' => 'exists:levels,id', //will be array
            'attendance.*.course_id' => 'exists:courses,id',
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

        $attendance->update([
            'name' => isset($request->name) ? $request->name : $attendance->name,
            'attendance_type' => isset($request->attendance_type) ? $request->attendance_type : $attendance->attendance_type,
            'year_id' => isset($request->year_id) ? $request->year_id :  $attendance->year_id,
            'type_id' => isset($request->type_id) ? $request->type_id : $attendance->type_id,
            'segment_id' => isset($request->segment_id)? $request->segment_id : $attendance->segment_id,
            'is_graded' => isset($request->is_graded) ? $request->is_graded : $attendance->is_graded,
            'start_date' =>  isset($request->start_date) ? $request->start_date : $attendance->start_date,
            'end_date' => isset($request->end_date) ? $request->end_date : $attendance->end_date,
            'min_grade' =>  isset($request->min_grade) ? $request->min_grade : $attendance->min_grade,
            'gradeToPass' => isset($request->gradeToPass) ? $request->gradeToPass: $attendance->gradeToPass,
            'max_grade' => isset($request->max_grade) ? $request->max_grade : $attendance->max_grade,
            'created_by' => $attendance->created_by 
        ]);

        $gradeCat = GradeCategory::where('instance_type','Attendance') -> where('type','item')
                        ->where('instance_id',$attendance->id)->update([
            'name' => isset($request->name) ? $request->name : $attendance->name,
            'max' => isset($request->max_grade) ? $request->max_grade : $attendance->max_grade,
            'weight_adjust' => ((bool) $request->is_graded == false) ? 1 : 0,
            'weights' => ((bool) $request->is_graded == false) ? 0 : null,
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
