<?php

namespace Modules\Attendance\Http\Controllers;

use App\Component;
use App\GradeCategory;
use App\Http\Controllers\HelperController;
use App\Http\Controllers\GradeCategoryController;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Attendance\Entities\Attendance;
use Modules\Attendance\Entities\AttendanceLog;
use Modules\Attendance\Entities\AttendanceSession;

use App\Enroll;
use App\Level;
use App\CourseSegment;
use Illuminate\Support\Facades\DB;
use Modules\Attendance\Entities\AttendanceStatus;
use Modules\Attendance\Jobs\AttendanceGradeItems;
use Modules\Attendance\Jobs\AttendanceSessionsJob;
use stdClass;

class AttendanceSessionController extends Controller
{
    public function install()
    {
        if (\Spatie\Permission\Models\Permission::whereName('attendance/add')->first() != null) {
            return \App\Http\Controllers\HelperController::api_response_format(400, null, 'This Component is installed before');
        }

        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'attendance/add', 'title' => 'add attendance']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'attendance/add-log', 'title' => 'add attendance log']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'attendance/get-users-in-attendance', 'title' => 'get all users in attendance']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'attendance/get-attendance', 'title' => 'get attendance','dashboard' => 1]);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'attendance/delete-attendance', 'title' => 'delete attendance']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'attendance/edit-attendance', 'title' => 'edit attendance']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'attendance/get-users-in-session', 'title' => 'get all users in session']);


        $role = \Spatie\Permission\Models\Role::find(1);
        $role->givePermissionTo('attendance/add');
        $role->givePermissionTo('attendance/add-log');
        $role->givePermissionTo('attendance/get-users-in-attendance');
        $role->givePermissionTo('attendance/get-users-in-session');


        Component::create([
            'name' => 'Attendance',
            'module' => 'Attendance',
            'model' => 'Attendance',
            'type' => 2,
            'active' => 1
        ]);

        return \App\Http\Controllers\HelperController::api_response_format(200, null, 'Component Installed Successfully');
    }

    public function createSession(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'object' => 'required|array',
            'object.*.class_id' => 'required|exists:classes,id',
            'object.*.course_id' => 'required|exists:courses,id',
            'graded' => 'required|in:0,1',
            'object.*.grade_category_id' => 'required_if:graded,==,1|exists:grade_categories,id',
            'object.*.grade_max'=>'required_if:graded,==,1|integer|min:1',
            'object.*.from' => 'required|array',
            'object.*.to' => 'array|required',
            'object.*.from.*' => 'required_with:object.*.to.*|date_format:H:i',
            'object.*.to.*' => 'required_with:object.*.from.*|date_format:H:i|after:object.*.from.*',
            'object.*.start_date' => 'required',
            'object.*.end_date' => 'required|after:object.*.start_datefrom',
            'type' => 'in:daily,per_session',
            'day' => 'array|required_if:type,==,per_session',
            'day.*'=>'required|string|in:sunday,monday,tuesday,wednesday,thursday'
        ]);
        if($request->type=="daily"){
            $request->day = ['sunday','monday','tuesday','wednesday','thursday'];
        }
                foreach($request->object as $object){  
                    $grade_item = ($request->graded == 1)? 
                      GradeCategory::find( $object['grade_category_id'])
                      ->GradeItems()->create(['name' => 'Attendance','grademin' => '0', 'grademax' => $object['grade_max'] , 'weight' => 0])->id:null;
                      $day_before = date( 'Y-m-d H:i:s', strtotime( $object['start_date'] . ' -1 day' ) );
                    foreach($request->day as $day)
                    {
                    $temp_start = date('Y-m-d H:i:s', strtotime("next ".$day, strtotime($day_before))); 
                    while(Carbon::parse($temp_start)->format('Y-m-d H:i:s') <= Carbon::parse($object['end_date'])->format('Y-m-d H:i:s')){
                        if (count($object['from']) != count($object['to'])) {
                            return HelperController::api_response_format(400, null, 'invalid size of from , to ');
                        }
                        foreach($object['from']  as $key => $from)
                        {
                            
                        $sessions=AttendanceSession::firstOrCreate([
                            'taker_id' => Auth::id(),
                            'name' => $request->name,
                            'type' => $request->type,
                            'course_id'=>$object['course_id'],
                            'class_id' => $object['class_id'],
                            'start_date' => Carbon::parse($temp_start)->format('Y-m-d H:i:s'),
                            'from' => $object['from'][$key],
                            'to' => $object['to'][$key],
                            'graded' => $request->graded,
                            'grade_item_id' => $grade_item
                        ]);
                    }
                        $temp_start= Carbon::parse($temp_start)->addDays(7);
                    }
                }
            }
            return HelperController::api_response_format(200,null ,'Sessions Created Successfully');


    
    }
}