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
use Validator;
use App\Enroll;
use App\Level;
use App\CourseSegment;
use Illuminate\Support\Facades\DB;
use Modules\Attendance\Entities\AttendanceStatus;
use Modules\Attendance\Jobs\AttendanceGradeItems;
use Modules\Attendance\Jobs\AttendanceSessionsJob;
use stdClass;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use App\Exports\AttendnaceExport;
use Illuminate\Support\Str;


class AttendanceSessionController extends Controller
{
    public function install()
    {
        if (\Spatie\Permission\Models\Permission::whereName('attendance/add-session')->first() != null) {
            return \App\Http\Controllers\HelperController::api_response_format(400, null, 'This Component is installed before');
        }
        
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'attendance/add-session', 'title' => 'Add Sessions']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'attendance/add-log', 'title' => 'Take attendnace']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'attendance/get-attendance', 'title' => 'All Sessions','dashboard' => 1,'icon'=> 'Attendance']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'attendance/get-daily', 'title' => 'Daily','dashboard' => 1,'icon'=> 'Attendance']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'attendance/delete-attendance', 'title' => 'Delete Session']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'attendance/edit-attendance', 'title' => 'Edit Session']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'attendance/get-users-in-session', 'title' => 'Get students in session']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'attendance/export', 'title' => 'Export attendnace']);

        $teacher_permissions=['attendance/add-log','attendance/get-attendance','attendance/get-daily','attendance/get-users-in-session','attendance/export'];
        $tecaher = \Spatie\Permission\Models\Role::find(4);
        $tecaher->givePermissionTo(\Spatie\Permission\Models\Permission::whereIn('name', $teacher_permissions)->get());
        
        $role = \Spatie\Permission\Models\Role::find(1);
        $role->givePermissionTo('attendance/add-session');
        $role->givePermissionTo('attendance/add-log');
        $role->givePermissionTo('attendance/get-users-in-session');
        $role->givePermissionTo('attendance/delete-attendance');
        $role->givePermissionTo('attendance/edit-attendance');
        $role->givePermissionTo('attendance/get-attendance');
        $role->givePermissionTo('attendance/export');
        $role->givePermissionTo('attendance/get-daily');



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
            'main' => 'required|array',
            'main.*.class_id' => 'required|exists:classes,id',
            'main.*.course_id' => 'required|exists:courses,id',
            'graded' => 'required|in:0,1',
            'object.*.grade_category_id' => 'required_if:graded,==,1|exists:grade_categories,id',
            'object.*.grade_max'=>'required_if:graded,==,1|integer|min:1',
            'object.*.start_date' => 'required',
            'object.*.end_date' => 'required|after:object.*.start_date',
            'type' => 'in:daily,per_session',
            'day' => 'array|required_if:type,==,per_session',
            'day.*.name'=>'required|string|in:sunday,monday,tuesday,wednesday,thursday',
            'day.*.from'=>'required|array',
            'day.*.to'=>'required|array',
            'day.*.from.*' => 'required_with:day.*.to.*|date_format:H:i',
            'day.*.to.*' => 'required_with:day.*.from.*|date_format:H:i|after:day.*.from.*',
            'time' => 'array|required_if:type,==,daily',
            'time.*.from'=>'required|array',
            'time.*.to'=>'required|array',
            'time.*.from.*' => 'required_with:time.*.to.*|date_format:H:i',
            'time.*.to.*' => 'required_with:time.*.from.*|date_format:H:i|after:time.*.from.*',

        ]);

        $classes=[];
         
        foreach($request->main  as $main)
        {
            $classes[]=   $main['class_id'];
        }
        $check_dublications = (count($classes) === count(array_flip($classes)));
        if(!$check_dublications)
                return HelperController::api_response_format(400,null ,__('messages.attendance_session.same_time_session'));

        if($request->type=="daily"){
            $days = ['sunday','monday','tuesday','wednesday','thursday'];
            $times_collect=collect();
                foreach($request->time as $times){
                    foreach($days as $day){
                        $req = [
                            'name' => $day,
                            'from' => $times['from'],
                            'to' => $times['to'],
                        ];
                        $times_collect->push($req);
                    }
                }
                $request->day=$times_collect;
        }
       
                foreach($request->object as $object){  
                    $grade_item = ($request->graded == 1)? 
                      GradeCategory::find( $object['grade_category_id'])
                      ->GradeItems()->create(['name' => 'Attendance','grademin' => '0', 'grademax' => $object['grade_max'] , 'weight' => 0])->id:null;
                      $day_before = date( 'Y-m-d H:i:s', strtotime( $object['start_date'] . ' -1 day' ) );
                    foreach($request->day as $day)
                    {
                    $temp_start = date('Y-m-d H:i:s', strtotime("next ".$day['name'], strtotime($day_before))); 
                    while(Carbon::parse($temp_start)->format('Y-m-d H:i:s') <= Carbon::parse($object['end_date'])->format('Y-m-d H:i:s')){
                        if (count($day['from']) != count($day['to'])) {
                            return HelperController::api_response_format(400, null, __('messages.error.data_invalid'));
                        }
                        foreach($day['from']  as $key => $from)
                        {
                            
                            foreach($request->main  as $main)
                            {
                                
                                $sessions=AttendanceSession::firstOrCreate([
                                    'taker_id' => Auth::id(),
                                    'name' => $request->name,
                                    'type' => $request->type,
                                    'course_id'=>$main['course_id'],
                                    'class_id' => $main['class_id'],
                                    'start_date' => Carbon::parse($temp_start)->format('Y-m-d H:i:s'),
                                    'from' => $day['from'][$key],
                                    'to' => $day['to'][$key],
                                    'graded' => $request->graded,
                                    'grade_item_id' => $grade_item
                                ]);
                        }
                    }
                        $temp_start= Carbon::parse($temp_start)->addDays(7);
                    }
                }
            }
            return HelperController::api_response_format(200,null ,__('messages.attendance_session.add'));
    }

    public function get_users_in_sessions (Request $request,$call =0)
    {
        $request->validate([
            'session_id' => 'exists:attendance_sessions,id|required_without:class_id|required_without:course_id',
            'search' => 'nullable',
            'class_id' => 'nullable|exists:classes,id|required_with:course_id|required_without:session_id',
            'course_id' => 'nullable|exists:courses,id|required_with:class_id|required_without:session_id',
        ]);

        $class_id= $request->class_id;
        $course_id= $request->course_id;
        $session = null;

        if(isset($request->session_id)){
            $session = AttendanceSession::where('id',$request->session_id)->first();
            $class_id=$session->class_id;
            $course_id = $session->course_id;
        }

        $courseseg=CourseSegment::GetWithClassAndCourse($class_id,$course_id);
        if(!isset($courseseg))
            return HelperController::api_response_format(400, [] ,__('messages.error.no_active_segment'));

        $usersIDs=Enroll::where('course_segment',$courseseg->id)->pluck('user_id')->toarray();

        $h=collect();
        $i=0;
        foreach($usersIDs as $user)
        {
            $userObj=User::find($user);
            if(!isset($userObj))
                continue;
            if($userObj->roles->pluck('id')->first() == 3){
                $userObj['status'] = null;
                if(isset($request->session_id)){
                    $userObj['status']=AttendanceLog::where('student_id',$user)
                                                ->where('session_id',$request->session_id)
                                                ->where('type','offline')
                                                ->pluck('status')
                                                ->first();
                }

                unset($userObj->roles);
                $i++;
                if($request->has('search'))
                    if(!(Str::contains($userObj->username, [$request->search]) || Str::contains($userObj->firstname, [$request->search]) || Str::contains($userObj->lastname, [$request->search]) || Str::contains($userObj->arabicname, [$request->search])))
                        continue;

                $h->push($userObj);
            }
        }

        $all_logs=AttendanceLog::where('session_id',$request->session_id)->where('type','offline')->with('User')->get();
        $attendees_object['session'] = isset($session)? $session->name : $session['name'] = 'Daily Attendnace';
        $attendees_object['Total_Logs'] = $i;
        $attendees_object['Present']['count']= $all_logs->where('status','Present')->count();
        $attendees_object['Absent']['count']= $all_logs->where('status','Absent')->count();
        $attendees_object['Late']['count']= $all_logs->where('status','Late')->count();
        $attendees_object['Excuse']['count']= $all_logs->where('status','Excuse')->count();
        $attendees_object['Present']['precentage'] = 0 ;
        $attendees_object['Absent']['precentage'] =  0 ;
        $attendees_object['Late']['precentage'] =  0 ;
        $attendees_object['Excuse']['precentage'] =  0 ;
        if($i != 0)
        {
            $attendees_object['Present']['precentage'] = round((($attendees_object['Present']['count']/$i)*100),2) ;
            $attendees_object['Absent']['precentage'] =  round((($attendees_object['Absent']['count']/$i)*100),2) ;
            $attendees_object['Late']['precentage'] =  round((($attendees_object['Late']['count']/$i)*100),2) ;
            $attendees_object['Excuse']['precentage'] =  round((($attendees_object['Excuse']['count']/$i)*100),2) ;
        }

        $attendees_object['logs']=$h;

        if($call == 1)
            return $attendees_object;

        return HelperController::api_response_format(200,$attendees_object ,__('messages.users.students_list'));
    }

    public function take_attendnace (Request $request)
    {
        $request->validate([
            'session_id' => 'nullable|exists:attendance_sessions,id',
            'object' => 'required|array',
            'object.*.user_id' => 'required|exists:users,id',
            'object.*.status' => 'nullable',
        ]);

        foreach($request->object as $object){
            if( !isset($object['status']) || $object['status'] == null)
                $object['status'] = 'Absent';
            
            if($object['status'] == 'Absent' || $object['status'] == 'Late' || $object['status'] == 'Present' || $object['status'] == 'Excuse')
            {
                if(isset($request->session_id)){
                    $attendance=AttendanceLog::updateOrCreate(['student_id' => $object['user_id'],'session_id'=>$request->session_id,'type'=>'offline'],
                    [
                        'ip_address' => \Request::ip(),
                        'student_id' => $object['user_id'],
                        'taker_id' => Auth::id(),
                        'session_id' => $request->session_id,
                        'type' => 'offline',
                        'taken_at' => Carbon::now()->format('Y-m-d H:i:s'),
                        'status' => $object['status'],
                        'attendnace_type' => 'per_session'
                    ]);
                }

                if(!isset($request->session_id)){
                    $attendance=AttendanceLog::create([
                        'ip_address' => \Request::ip(),
                        'student_id' => $object['user_id'],
                        'taker_id' => Auth::id(),
                        'session_id' => $request->session_id,
                        'type' => 'offline',
                        'taken_at' => Carbon::now()->format('Y-m-d H:i:s'),
                        'status' => $object['status'],
                        'attendnace_type' => 'daily',
                    ]);
                }
               
            }
            
        }

        return HelperController::api_response_format(200,null ,__('messages.attendance_session.taken'));
    }

    public function get_sessions (Request $request)
    {

        $request->validate([
            'class_id' => 'array',
            'class_id.*' => 'exists:classes,id',
            'course_id' => 'array',
            'course_id.*' => 'exists:courses,id',
            'start_date' => 'date',
            'end_date' => 'date|required_with:start_date',
            'type' => 'in:daily,per_session',
            'search' => 'nullable',
        ]);

        $CS_ids=GradeCategoryController::getCourseSegment($request);
        $CourseSeg = Enroll::where('user_id', Auth::id())->pluck('course_segment');
        $CourseSeg = array_intersect($CS_ids->toArray(),$CourseSeg->toArray());
        if($request->user()->can('site/show-all-courses')){
            $CourseSeg = $CS_ids;
        }
        $courses=collect();
        foreach($CourseSeg as $cs){
            if(in_array($cs, $CS_ids->toArray())){
                $cs_object = CourseSegment::find($cs);
                if($cs_object->end_date > Carbon::now() && $cs_object->start_date < Carbon::now()){
                    $courses_cs = $cs_object->courses;
                    foreach($courses_cs as $c){
                        $courses->push($c->id);
                    }
                }
            }
        }

        $sessions = AttendanceSession::whereIn('course_id',$courses)->with(['class','course'])->orderBy('start_date'); 

        if($request->has('class_id'))
            $sessions = $sessions->whereIn('class_id',$request->class_id);

        if($request->has('course_id'))
            $sessions = $sessions->whereIn('course_id',$request->course_id);

        if($request->has('type'))
            $sessions = $sessions->where('type',$request->type);
        
        if($request->has('start_date') && $request->has('end_date'))
            $sessions = $sessions->where('start_date','>=',$request->start_date)->where('start_date','<=',$request->end_date);

        if($request->has('search'))
            $sessions = $sessions->where('name', 'LIKE' , "%$request->search%");

            
        return HelperController::api_response_format(200,$sessions->get() ,__('messages.attendance_session.list'));

    }

    public function delete_session (Request $request)
    {
        $request->validate([
            'session_id' => 'required|exists:attendance_sessions,id',
        ]);

        $log = AttendanceLog::where('session_id',$request->session_id)
                                                ->where('type','offline')
                                                ->first();
        if(isset($log))
            return HelperController::api_response_format(200,'fail' ,__('messages.error.cannot_delete'));
            
        $session = AttendanceSession::where('id',$request->session_id)->first()->delete();
        return HelperController::api_response_format(200,null ,__('messages.attendance_session.delete'));
    }   

    public function update_session (Request $request)
    {
        $request->validate([
            'session_id' => 'exists:attendance_sessions,id',
            'name' => 'string',
            'from' => 'date_format:H:i',
            'to' => 'date_format:H:i',
            'start_date' => 'nullable',
        ]);
        $sessions = AttendanceSession::where('id',$request->session_id)->first();
        
        if(isset($request->name))
            $sessions->name = $request->name;
        
        if(isset($request->from))
            $sessions->from = $request->from;

        if(isset($request->to))
            $sessions->name = $request->to;

        if(isset($request->start_date))
            $sessions->start_date = $request->start_date;

        $sessions->save();
        return HelperController::api_response_format(200,null ,__('messages.attendance_session.update'));

    } 

    public function export(Request $request)
    {
        $request->validate([
            'session_id' => 'required|exists:attendance_sessions,id',
        ]);
        $attendnace_object = self::get_users_in_sessions($request,1);
        $filename = uniqid();
        $file = Excel::store(new AttendnaceExport($attendnace_object), 'attendance'.$filename.'.xls','public');
        $file = url(Storage::url('attendance'.$filename.'.xls'));
        return HelperController::api_response_format(201,$file, __('messages.success.link_to_file'));
    }
}