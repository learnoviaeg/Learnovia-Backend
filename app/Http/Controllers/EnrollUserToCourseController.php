<?php

namespace App\Http\Controllers;

use App\Http\Controllers\HelperController;
use Illuminate\Http\Request;
use App\User;
use Excel;
use App\Imports\EnrollImport;
use App\Enroll;
use Carbon\Carbon;
use App\ClassLevel;
use App\CourseSegment;
use App\SegmentClass;
use DB;
use App\Imports\UsersImport;
use Spatie\Permission\Models\Role;
use App\Http\Controllers\ExcelController;
use App\Http\Resources\UserResource;


class EnrollUserToCourseController extends Controller
{
    // Enroll one\more users to one\more course_segements
    public static function EnrollCourses(Request $request)
    {
        $request->validate([
            'users' => 'required|array',
            'users.*' => 'required|string|exists:users,username',
            'role_id' => 'required|array|exists:roles,id',
            'start_date' => 'required|before:end_date|after:' . Carbon::now(),
            'end_date' => 'required|after:' . Carbon::now(),
            'year' => 'exists:academic_years,id',
            'type' => 'required|exists:academic_types,id',
            'level' => 'required|exists:levels,id',
            'class' => 'required|exists:classes,id',
            'segment' => 'exists:segments,id'
        ]);

        $data = array();
        $count = 0;
        $rolecount = 0;
        $course_segment = HelperController::Get_Course_segment($request)['value'];
        foreach ($course_segment as $courses) {
            foreach ($request->users as $username) {
                $user_id = User::FindByName($username)->id;
                $check = Enroll::IsExist($courses->id, $user_id);
                if (!$check) {
                    $enroll = new Enroll;
                    $enroll->setAttribute('user_id', $user_id);
                    $enroll->setAttribute('course_segment', $courses->id);
                    $enroll->setAttribute('start_date', $request->start_date);
                    $enroll->setAttribute('end_date', $request->end_date);
                    $enroll->setAttribute('role_id', $request->role_id[$rolecount]);
                    $enroll->setAttribute('username', $username);
                    $enroll->save();
                } else {
                    $count++;
                    $data[] = $username;
                }
                $rolecount++;
            }
            $rolecount = 0;
        }
        if ($count != 0) {
            return HelperController::api_response_format(200, $data, 'those users already enrolled');
        }
        return HelperController::api_response_format(200, [], 'added successfully');
    }

    // unEnroll a user to a coursor more courses
    public function UnEnroll(Request $request)
    {
        $request->validate([
            'username' => 'required|array|exists:enrolls,username',
            'year' => 'exists:academic_years,id',
            'type' => 'required|exists:academic_types,id',
            'level' => 'required|exists:levels,id',
            'class' => 'required|exists:classes,id',
            'segment' => 'exists:segments,id',
            'course' => 'exists:courses,id'
        ]);
        $courseSegment = HelperController::Get_Course_segment_Course($request);
        foreach ($request->username as $users) {
            $course_segment = Enroll::where('course_segment', $courseSegment)->where('username', $users)->delete();
        }

        if($course_segment > 0)
            return HelperController::api_response_format(200, $course_segment, 'users UnEnrolled Successfully');

        return HelperController::NOTFOUND();
    }


    public function ViewAllCoursesThatUserEnrollment(Request $request)
    {
        $request->validate([
            'username' => 'required|exists:users,username'
        ]);

        $user_id = User::FindByName($request->username)->id;
        $users = Enroll::GetCourseSegment($user_id);
        $courseID = array();
        foreach ($users as $test) {
            $courseID[] = CourseSegment::GetCoursesByCourseSegment($test)->pluck('course_id')->first();
        };

        return HelperController::api_response_format(200, $courseID, 'The Courses Registerd is');
    }

    public static function EnrollInAllMandatoryCourses(Request $request)
    {
        $request->validate([
            'username' => 'required|array|exists:users,username',
            'start_date' => 'required|before:end_date|after:' . Carbon::now(),
            'end_date' => 'required|after:' . Carbon::now(),
            'year' => 'exists:academic_years,id',
            'type' => 'required|exists:academic_types,id',
            'level' => 'required|exists:levels,id',
            'class' => 'required|exists:classes,id',
            'segment' => 'exists:segments,id'
        ]);

        $count = 0;
        foreach ($request->username as $user) {
            $userId = User::FindByName($user)->id;
            $x = HelperController::Get_segment_class($request);
            $segments = collect([]);
            $x->courseSegment;
            foreach ($x->courseSegment as $key => $segment) {
                $segment->courses;
                foreach ($segment->courses as $key => $course) {
                    if ($course->mandatory == 1) {
                        $segments->push($segment->id);
                    }
                }
            }
            if($segments == null)
                break;

            $check = Enroll::where('user_id', $userId)->where('course_segment', $segments)->pluck('id');
            if (count($check) == 0) {
                foreach ($segments as $segment) {
                    Enroll::firstOrCreate([
                        'username' => $user,
                        'user_id' => $userId,
                        'course_segment' => $segment,
                        'start_date' => $request->start_date,
                        'end_date' => $request->end_date,
                        'role_id' => 3,
                    ]);
                }
            } else {
                $count++;
            }
        }
       //($count);
        if ($count > 0) {
            return HelperController::api_response_format(200, [], 'those users added before');
        }
        return HelperController::api_response_format(200, [], 'added successfully');
    }

    public function EnrollExistUsersFromExcel(Request $request)
    {
        $ExcelCntrlVar = new ExcelController();
        $ExcelCntrlVar->import($request);
    }

    public function AddAndEnrollBulkOfNewUsers(Request $request)
    {
        $ExcelCntrlVar = new ExcelController();
        $ExcelCntrlVar->import($request);
    }

    public function GetEnrolledStudents(Request $request)
    {

        $request->validate([
            'course_id' => 'required|exists:courses,id',
            'class_id' => 'required|exists:classes,id',
            'filter'=> 'nullable|in:parent,child'
        ]);

        $course_seg=CourseSegment::GetWithClassAndCourse($request->class_id,$request->course_id);
        $users=array();
        if($course_seg == null)
        {
            return HelperController::api_response_format(200, null , 'There is no users found');
        }
        else
        {
            foreach($course_seg->enroll as $enroll)
            {
                if($enroll->role_id == 3)
                {
                    $users[]=$enroll->user_id;
                }
            }
            $uniuser=array_unique($users);
            $childparent=array();
            $child=array();
            //find every student parent
            foreach($uniuser as $us)
            {
                $use=User::find($us);
                $childparent[]=$use->parents;
                $child[]=$use;
            }

            //starting filters
            if($request->filter == null)
            {
                $childwithparent=array();
                foreach($child as $cs)
                {
                    $childwithparent[]= new UserResource($cs);
                    foreach($cs->parents as $parent)
                    {
                        $childwithparent[]['parents'] = new UserResource($parent);
                    }
                }
                return HelperController::api_response_format(200, $childwithparent , 'Enrolled Students With Parnets');
            }
            else if($request->filter == 'parent')
            {
                $parents=array();
                foreach($childparent as $par)
                {
                    $parents[]=new UserResource(User::find($par[0]['pivot']['parent_id']));
                }
                return HelperController::api_response_format(200, $parents , 'Parnets of Enrolled Students');
            }
            else if($request->filter == 'child')
            {
                $student=array();
                foreach($uniuser as $u)
                {
                    $use=new UserResource(User::find($u));
                    $student[]=$use;
                }

                return HelperController::api_response_format(200, $student , 'Enrolled Students');
            }
        }
    }
}
