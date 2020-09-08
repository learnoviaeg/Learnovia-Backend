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
            'grade_item_id' => 'required_if:graded,==,1|exists:grade_items,id',
            'object.*.from' => 'date_format:H:i|required',
            'object.*.to' => 'required_if:type,==,per_session|date_format:H:i|after:object.*.from',
            'object.*.start_date' => 'required',
            'object.*.end_date' => 'required',
            'type' => 'in:daily,per_session'
        ]);

        if(count($request->object) > 0){
            foreach($request->object as $object){       
                    $temp_start = Carbon::parse($object['start_date']);
                    while(Carbon::parse($temp_start)->format('Y-m-d H:i:s') <= Carbon::parse($object['end_date'])->format('Y-m-d H:i:s')){
                        $sessions=AttendanceSession::firstOrCreate([
                            'taker_id' => Auth::id(),
                            'name' => $request->name,
                            'type' => $request->type,
                            'course_id'=>$object['course_id'],
                            'class_id' => $object['class_id'],
                            'start_date' => Carbon::parse($temp_start)->format('Y-m-d H:i:s'),
                            'from' => $object['from'],
                            'to' => isset($object['to']) ? $object['to'] : Carbon::parse($object['from'])->addHour(1),
                            'graded' => $request->graded,
                            'grade_item_id' => ($request->graded == 1) ? $request->grade_item_id : null
                        ]);
                        $temp_start= Carbon::parse($temp_start)->addDays(7);
                    }
            }
            return HelperController::api_response_format(200, 'Attendance Created Successfully');
        }
    }
}