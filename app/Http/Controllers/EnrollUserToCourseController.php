<?php

namespace App\Http\Controllers;

use App\Http\Controllers\HelperController;
use Illuminate\Http\Request;
use App\User;
use App\Enroll;
use Carbon\Carbon;
use App\CourseSegment;
use DB;
class EnrollUserToCourseController extends Controller
{
    public function EnrollSingleCourse(Request $request)
    {

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'course_segment' => 'required|exists:course_segments,id',
            'start_date' => 'required|before:end_date|after:'.Carbon::now(),
            'end_date' => 'required|after:'.Carbon::now()
        ]);
        $users_role= user::find($request->user_id);
        $roles = $users_role->getRoleNames();
        $rolesid = DB::table('roles')->where('name',$roles)->pluck('id')->first();

        $check = DB::table('enrolls')->where('course_segment', $request->course_segment)->where('user_id',$request->user_id)->pluck('id')->first();
        if($check==NULL){

        $eroll = new Enroll;
        $eroll->setAttribute('user_id', $request->user_id);
        $eroll->setAttribute('course_segment', $request->course_segment);
        $eroll->setAttribute('start_date', $request->start_date);
        $eroll->setAttribute('end_date', $request->end_date);
        $eroll->setAttribute('role_id', $rolesid);

        $eroll->save();
    }
    return HelperController::api_response_format(200, [], 'added successfully');
}




    public function ViewAllCoursesThatUserErollment(Request $request)
    {

        $request->validate([
            'id' => 'required|exists:users,id'
        ]);

        $users = DB::table('enrolls')->where('user_id', $request->id)->pluck('course_segment');
     // dd($users[1]);
        $courseID=array();
             foreach($users as $test){
                $courseID[] = DB::table('course_segments')->where('id', $test)->pluck('course_id')->first();
            };
      //  dd($courseID);
        return HelperController::api_response_format(200, $courseID, 'Done');
        
    }





    public function EnrollInAllMandatoryCourses(Request $request)
    {

        // $courses = DB::table('courses')->where('mandatory', 1)->pluck('id');
        // dd($courses); //  get all mandatory courses in courses

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'class_id' => 'required|exists:classes,id',
            'start_date' => 'required|before:end_date|after:'.Carbon::now(),
            'end_date' => 'required|after:'.Carbon::now()
        ]);

        $ClassLevelId = DB::table('class_levels')->where('class_id', $request->class_id)->pluck('id')->first();

        $SegmentClassId = DB::table('segment_classes')->where('class_level_id', $ClassLevelId)->pluck('id')->first();

        $CourseId = DB::table('course_segments')->where('segment_class_id', $SegmentClassId)->pluck('course_id');
       
       
        $courseID=array();
        foreach($CourseId as $test){
            $check = DB::table('courses')->where('id', $test)->pluck('mandatory')->first();
            if($check==1){
                $courseID[] = DB::table('courses')->where('id', $test)->where('mandatory', 1)->pluck('id')->first();
            }
       }
       $segment=array();
       foreach ($courseID as $cou) {
        $segment[] = DB::table('course_segments')->where('course_id', $cou)->pluck('id')->first();
    }


    $users_role= user::find($request->user_id);
    $roles = $users_role->getRoleNames();
    $rolesid = DB::table('roles')->where('name',$roles)->pluck('id')->first();
    
    
    foreach ($segment as $seg) {
        $check = DB::table('enrolls')->where('course_segment',$seg)->where('user_id',$request->user_id)->pluck('id')->first();
        if($check==NULL){
        $segment[] = DB::table('enrolls')->insertGetId(
            [
            'user_id' => $request->user_id,
            'course_segment'=> $seg,
            'start_date'=> $request->start_date,
            'end_date'=>$request->end_date,
            'role_id'=>$rolesid,
            ]

        );
    }}
   
    return HelperController::api_response_format(200, [], 'added successfully');
        
       // return HelperController::api_response_format(200, $courseID, 'Done');dd($CourseSegmentId[1]);
        
    }
}
