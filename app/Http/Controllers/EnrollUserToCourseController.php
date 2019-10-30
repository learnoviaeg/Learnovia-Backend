<?php

namespace App\Http\Controllers;

use App\AcademicYearType;
use App\Http\Controllers\HelperController;
use App\YearLevel;
use Illuminate\Http\Request;
use App\User;
use Excel;
use App\Imports\EnrollImport;
use App\Enroll;
use Carbon\Carbon;
use App\ClassLevel;
use App\CourseSegment;
use App\Course;
use App\SegmentClass;
use DB;
use App\Imports\UsersImport;
use Spatie\Permission\Models\Role;
use App\Http\Controllers\ExcelController;

class EnrollUserToCourseController extends Controller
{
    // Enroll one\more users to one\more course_segements
    public static function EnrollCourses(Request $request)
    {
        $request->validate([
            'users' => 'required|array',
            'users.*' => 'required|string|exists:users,username',
            'role_id' => 'required|array|exists:roles,id',
            'start_date' => 'required|before:end_date|date_format:Y-m-d H:i:s|after:' . Carbon::now(),
            'end_date' => 'required|date_format:Y-m-d H:i:s|after:' . Carbon::now(),
            'class' => 'required|exists:classes,id',
            'course' => 'required|array',
            'course.*' => 'required|exists:courses,id'
        ]);

        $data = array();
        $count = 0;
        $rolecount = 0;
        $course_segment = [];
        foreach ($request->course as $course) {
            $course_segment[] = CourseSegment::GetWithClassAndCourse($request->class, $course);
        }

        if (!in_array(null, $course_segment) == true) {
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
        } else {
            return HelperController::api_response_format(200, [], 'invalid class data or not active course');
        }
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
        if ($courseSegment == null)
            return HelperController::api_response_format(200, null, 'No current segment or year');

        foreach ($request->username as $users)
            $course_segment = Enroll::where('course_segment', $courseSegment['value']->id)->where('username', $users)->delete();

        if ($course_segment > 0)
            return HelperController::api_response_format(200, $course_segment, 'users UnEnrolled Successfully');

        return HelperController::api_response_format(200, $course_segment, 'NOT FOUND USER IN THIS COURSE/invalid data');
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
            'segment' => 'exists:segments,id',
            'course' => 'array|exists:courses,id'
        ]);

        $count = 0;
        foreach ($request->username as $user) {
            $userId = User::FindByName($user)->id;
            $x = HelperController::Get_segment_class($request);
            if ($x != null) {
                $segments = collect([]);
                if (count($x->courseSegment) < 1) {
                    return HelperController::api_response_format(400, [], 'No Courses belong to this class');
                }
                foreach ($x->courseSegment as $key => $segment) {
                    $segment->courses;
                    foreach ($segment->courses as $key => $course) {
                        if ($course->mandatory == 1) {
                            $segments->push($segment->id);
                        }
                    }
                }
                if ($segments == null)
                    break;

                $check = Enroll::where('user_id', $userId)->where('course_segment', $segments)->pluck('id');
                if (count($check) == 0) {
                    foreach ($segments as $segment) {
                        Enroll::Create([
                            'username' => $user,
                            'user_id' => $userId,
                            'course_segment' => $segment,
                            'start_date' => $request->start_date,
                            'end_date' => $request->end_date,
                            'role_id' => 3,
                        ]);
                    }
                }
                if (isset($request->course)) {
                    $mand = Course::where('id', $request->course)->pluck('mandatory')->first();
                    if ($mand == 1)
                        return HelperController::api_response_format(400, [], 'This Course not Optional_Course');

                    foreach ($request->course as $course) {
                        $courseSegment = HelperController::Get_Course_segment_course($request);
                        Enroll::Create([
                            'username' => $user,
                            'user_id' => $userId,
                            'course_segment' => $courseSegment['value']['id'],
                            'start_date' => $request->start_date,
                            'end_date' => $request->end_date,
                            'role_id' => 3,
                        ]);
                    }
                } else {
                    $count++;
                }
            } else
                return HelperController::api_response_format(400, [], 'No Current segment or year');
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
        ]);

        $course_seg_id = CourseSegment::getidfromcourse($request->course_id);

        if ($request->class_id == null) {
            foreach ($course_seg_id as $course_seg)
                $users_id[] = Enroll::GetUsers_id($course_seg);
            foreach ($users_id as $users) {
                if ($users->isEmpty())
                    return HelperController::api_response_format(200, null, 'There is No student enrolled');
                else
                    foreach ($users as $user)
                        $UsersIds[] = User::findOrFail($user);
            }

            //return all users that enrolled in this course
            return HelperController::api_response_format(200, $UsersIds, 'students are ... ');
        } //if was send class_id and course_id
        else {
            $request->validate([
                'class_id' => 'required|exists:classes,id'
            ]);

            //$usersByClass is an array that have all users in this class
            $usersByClass = User::GetUsersByClass_id($request->class_id);

            if ($usersByClass->isEmpty())
                return HelperController::api_response_format(200, null, 'There is no student in This class ');

            foreach ($usersByClass as $users)
                $UsersClassIds[] = User::findOrFail($users);

            foreach ($course_seg_id as $course_seg) {
                $users_id = Enroll::GetUsers_id($course_seg);

                // $result is an array of users enrolled this course in this class
                $result[] = array_intersect($usersByClass->toArray(), $users_id->toArray());
            }

            foreach ($result as $users)
                $Usersenrolled[] = User::findOrFail($users);
            foreach ($Usersenrolled as $use)
                if ($use->isEmpty())
                    return HelperController::api_response_format(200, null, 'there is no student ');

            return HelperController::api_response_format(200, $Usersenrolled, 'students are ... ');
        }
    }

    public function getUnEnroll(Request $request)
    {
        $request->validate([
            'year' => 'required|exists:academic_years,id',
            'type' => 'required|exists:academic_types,id',
            'level' => 'required|exists:levels,id',
            'class' => 'required|exists:classes,id',
            'segment' => 'required|exists:segments,id',
            'courses' => 'required|array',
            'courses.*' => 'required|exists:courses,id'
        ]);

        $academic_year_type = AcademicYearType::checkRelation($request->year, $request->type);
        $year_level = YearLevel::checkRelation($academic_year_type->id, $request->level);
        $class_level = ClassLevel::checkRelation($request->class, $year_level->id);
        $segment_class = SegmentClass::checkRelation($class_level->id, $request->segment);

        $course_segment = collect([]);
        foreach ($request->courses as $c)
            $course_segment[] = CourseSegment::checkRelation($segment_class->id, $c);

        if ($course_segment == null)
            return HelperController::api_response_format(200, null, 'No current segment or year');

        $ids = Enroll::whereIn('course_segment', $course_segment->pluck('id'))->pluck('user_id');
        $userUnenrolls = User::whereNotIn('id', $ids)->get();

        return HelperController::api_response_format(200, $userUnenrolls, 'students are ... ');

    }

    public function unEnrolledUsersBulk(Request $request)
    {
        $courseSegments = HelperController::Get_Course_segment($request);
        if ($courseSegments['result'] == false) {
            return HelperController::api_response_format(400, null, $courseSegments['value']);
        }
        if ($courseSegments['value'] == null) {
            return HelperController::api_response_format(400, null, 'No Course active in segment');
        }

        $ids = Enroll::whereIn('course_segment', $courseSegments['value']->pluck('id'))->pluck('user_id');
        $userUnenrolls = User::whereNotIn('id', $ids)->get();
        return HelperController::api_response_format(200, $userUnenrolls, 'students are ... ');


    }
}