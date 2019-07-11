<?php

namespace App\Http\Controllers;

use App\Http\Controllers\HelperController;
use Illuminate\Http\Request;
use App\User;
use App\Enroll;
use Carbon\Carbon;
use App\CourseSegment;
use App\SegmentClass;
use DB;
use Spatie\Permission\Models\Role;

class EnrollUserToCourseController extends Controller
{
    // Enroll one\more users to one\more course_segements
    public function EnrollCourses(Request $request)
    {

        $request->validate([
            'course_segment' => 'required|array|exists:course_segments,id',

            'start_date' => 'required|before:end_date|after:'.Carbon::now(),
            'users'=> 'required|array',
            'users.*'=>'required|string|exists:users,username',
            'end_date' => 'required|after:'.Carbon::now(),
            'role_id'=>'required|array|exists:roles,id'
        ]);

        $data=array();
        $count=0;
        $rolecount=0;
        foreach($request->course_segment as $courses){


            foreach($request->users as $username)
            {
             
                $user_id=User::FindByName($username)->id;

   
                $check =Enroll::IsExist($courses,$user_id);
                if(!$check){
                    $enroll = new Enroll;
                    $enroll->setAttribute('user_id', $user_id);
                    $enroll->setAttribute('course_segment', $courses);
                    $enroll->setAttribute('start_date',$request->start_date);
                    $enroll->setAttribute('end_date',$request->end_date);
                    $enroll->setAttribute('role_id',$request->role_id[$rolecount]);
                    $enroll->setAttribute('username', $username);

                    $enroll->save();  
                }   
                else
                {
                    $count++;
                    $data[]=$username;
                }
                $rolecount++;
            }
            $rolecount=0;
        }
    if($count!=0)
    {
        return HelperController::api_response_format(200, $data, 'those users already enrolled');

    }        

    return HelperController::api_response_format(200, [], 'added successfully');
}

// unEnroll a user to a coursor more courses
    public function UnEnroll(Request $request){

        $request->validate([
            'username' => 'required|exists:enrolls,username',
            'course_segment' => 'required|exists:enrolls,course_segment'
        ]);
        $user_id=User::FindByName($request->username)->id;
        $users_enroll= Enroll::FindUserbyID($user_id,$request->course_segment);
        $users_enroll->delete();
    
        return HelperController::api_response_format(200 ,$users_enroll , 'users UnEnrolled Successfully');
    }


    public function ViewAllCoursesThatUserErollment(Request $request)
    {

        $request->validate([
            'username' => 'required|exists:users,username'
        ]);

        $user_id=User::FindByName($request->username)->id;    
        $users =Enroll::GetCourseSegment($user_id);
        $courseID=array();
             foreach($users as $test){
                $courseID[] = CourseSegment::GetCoursesByCourseSegment($test)->pluck('course_id')->first();
            };
    
        return HelperController::api_response_format(200, $courseID, 'The Courses Registerd is');
        
    }





    public function EnrollInAllMandatoryCourses(Request $request)
    {

        $request->validate([
            'username' => 'required|exists:users,username',
            'start_date' => 'required|before:end_date|after:'.Carbon::now(),
            'end_date' => 'required|after:'.Carbon::now(),
            'SegmentClassId' => 'required|exists:course_segments,id' 
        ]);


        $userId =User::FindByName($request->username)->id;
     
        $x = SegmentClass::find($request->SegmentClassId);
        $segments = collect([]);
            $x->course_segment;
            foreach ($x->course_segment as $key => $segment) {
                $segment->courses;
                foreach ($segment->courses as $key => $course) {
                    if ($course->mandatory == 1) {
                        $segments->push($segment->id);
                    }
                }
            }
      
        $role=Role::findByName('Student')->id;
        foreach($segments as $segment){
            Enroll::create([
                'username' => $request->username,
                'user_id' => $userId,
                'course_segment' => $segment,
                'start_date'=> $request->start_date,
                'end_date'=>$request->end_date,
                'role_id'=>$role,
            ]);
        }
   
    return HelperController::api_response_format(200, [], 'added successfully');
                
    }
}
