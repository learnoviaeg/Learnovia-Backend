<?php

namespace App\Http\Controllers;

use App\AcademicYear;
use App\AcademicYearType;
use App\Http\Controllers\HelperController;
use App\YearLevel;
use Illuminate\Http\Request;
use App\User;
use App\Enroll;
use App\ClassLevel;
use App\CourseSegment;
use App\Course;
use App\GradeCategory;
use App\SegmentClass;

use App\Imports\UsersImport;
use Spatie\Permission\Models\Role;
use App\Http\Controllers\ExcelController;
use App\UserGrade;

class EnrollUserToCourseController extends Controller
{
    /**
     * Enroll uses/s to course/s
     *
     * @param  [array] users .. id
     * @param  [array] role_id
     * @param  [array] course .. id
     * @param  [int..id] class
     * @return if these users enrolled before [string] those users already enrolled
     * @return if coure not active and invalid class [string] invalid class data or not active course
     * @return [string] added successfully
     */
    public static function EnrollCourses(Request $request)
    {
        $request->validate([
            'users' => 'required|array',
            'users.*' => 'required|exists:users,id',
            'role_id' => 'required|array|exists:roles,id',
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
                foreach ($request->users as $user_id) {
                    $username = User::find($user_id)->username;
                    $check = Enroll::IsExist($courses->id, $user_id,$request->role_id[$rolecount]);
                    if (!$check) {
                        $enroll = new Enroll;
                        $enroll->setAttribute('user_id', $user_id);
                        $enroll->setAttribute('course_segment', $courses->id);
                        $enroll->setAttribute('role_id', $request->role_id[$rolecount]);
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

    /**
     * UnEnroll uses/s to course/s
     *
     * @param  [array] user_id .. id
     * @param  [int..id] year
     * @param  [int..id] type
     * @param  [int..id] level
     * @param  [int..id] class
     * @param  [int..id] segment
     * @param  [int..id] course
     * @return if No current segment or year [string] There is no current segment or year
     * @return if No user in this course [string] NOT FOUND USER IN THIS COURSE/invalid data
     * @return [object] courses that users unenrolled successfully
     */
    public function UnEnroll(Request $request)
    {
        $request->validate([
            'user_id' => 'required|array|exists:enrolls,user_id',
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

        foreach ($request->user_id as $users)
            $course_segment = Enroll::where('course_segment', $courseSegment['value']->id)->where('user_id', $users)->delete();

        if ($course_segment > 0)
            return HelperController::api_response_format(200, $course_segment, 'users UnEnrolled Successfully');

        return HelperController::api_response_format(200, $course_segment, 'NOT FOUND USER IN THIS COURSE/invalid data');
    }

    /**
     * view all courses that user enrolled in
     *
     * @param  [int] user_id .. id
     * @return [ids] courses that users enrolled in
     */
    public function ViewAllCoursesThatUserEnrollment(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id'
        ]);

        $users = Enroll::GetCourseSegment($request->user_id);
        $courseID = array();
        foreach ($users as $test) {
            $courseID[] = CourseSegment::GetCoursesByCourseSegment($test)->pluck('course_id')->first();
        };

        return HelperController::api_response_format(200, $courseID, 'The Courses Registerd is');
    }

    /**
     * Enroll uses/s to Mandatory course/s
     *
     * @param  [array] users .. id
     * @param  [int..id] year
     * @param  [int..id] type
     * @param  [int..id] level
     * @param  [int..id] class
     * @param  [int..id] segment
     * @param  [array..id] course
     * @return if the course that user entered not optional [string] This Course not Optional_Course
     * @return if these users enrolled before [string] those users already enrolled
     * @return if No current segment or year [string] There is no current segment or year
     * @return [string] added successfully
     */
    public static function EnrollInAllMandatoryCourses(Request $request)
    {
        $request->validate([
            'users' => 'required|array|exists:users,id',
            'year' => 'exists:academic_years,id',
            'type' => 'required|exists:academic_types,id',
            'level' => 'required|exists:levels,id',
            'class' => 'required|exists:classes,id',
            'segment' => 'exists:segments,id',
            'course' => 'array|exists:courses,id'
        ]);

        $count = 0;
        foreach ($request->users as $user) {
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

                $check = Enroll::where('user_id', $user)->where('course_segment', $segments)->pluck('id');
                if (count($check) == 0) {
                    foreach ($segments as $segment) {
                        Enroll::Create([
                            'user_id' => $user,
                            'course_segment' => $segment,
                            'role_id' => 3,
                        ]);
                    }
                } elseif (isset($request->course)) {
                    $mand = Course::where('id', $request->course)->pluck('mandatory')->first();
                    if ($mand == 1)
                        return HelperController::api_response_format(400, [], 'This Course not Optional_Course');

                    $courseSegment = GradeCategoryController::getCourseSegment($request);
                    foreach($courseSegment as $one){
                        Enroll::Create([
                            'user_id' => $user,
                            'course_segment' => $one,
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
            return HelperController::api_response_format(200, [], 'found user added before');
        }
        return HelperController::api_response_format(200, [], 'added successfully');
    }

    /**
     * Enroll bulk of users from excel sheet
     *
     * @param  [excel] all data of user
     * @return [string] done depended on Import files {enroll/users/courses}
     */
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

    /**
     * Get Enrolled student in this course
     *
     * @param  [int] course .. id
     * @param  [int] class .. id
     * @return if given class [string] filtered just students in this class
     * @return if these users enrolled before [string] those users already enrolled
     * @return [string] students
     */
    public function GetEnrolledStudents(Request $request)
    {
        $request->validate([
            'course_id' => 'required|exists:courses,id',
            'search' => 'nullable'
        ]);

        if ($request->class_id == null) {
            $course_seg_id = CourseSegment::getidfromcourse($request->course_id);

            $users_id = Enroll::whereIn('course_segment', $course_seg_id)->where('role_id', 3)->pluck('user_id');

            if ($request->filled('search')) {
                $users = User::whereIn('id', $users_id)->where(function ($query) use ($request) {
                    $query->where('firstname', 'LIKE', "%$request->search%")
                        ->orWhere('lastname', 'LIKE', "%$request->search%")->orWhere('username', 'LIKE', "%$request->search%");
                })->get();

                return HelperController::api_response_format(200, $users);
            }

            $users = User::whereIn('id', $users_id)->get();

            //return all users that enrolled in this course
            return HelperController::api_response_format(200, $users, 'students are ... ');
        }
        //if was send class_id and course_id
        else {
            $request->validate([
                'class_id' => 'required|exists:classes,id'
            ]);

            $course_seg = CourseSegment::GetWithClassAndCourse($request->class_id, $request->course_id);

            //$usersByClass --> all users in this class
            $usersByClass = User::GetUsersByClass_id($request->class_id);
            //$usersByClass --> all users in this course
            $users_id = Enroll::GetUsers_id($course_seg->id);

            $result = array_intersect($usersByClass->toArray(), $users_id->toArray());

            if ($request->filled('search')) {
                $users = User::whereIn('id', $users_id)->where(function ($query) use ($request) {
                    $query->where('firstname', 'LIKE', "%$request->search%")
                        ->orWhere('lastname', 'LIKE', "%$request->search%")->orWhere('username', 'LIKE', "%$request->search%");
                })->get();

                return HelperController::api_response_format(200, $users);
            }

            if ($usersByClass->isEmpty())
                return HelperController::api_response_format(200, null, 'There is no student in This class ');

            foreach ($result as $users)
                $Usersenrolled[] = User::findOrFail($users);

            if (!isset($Usersenrolled))
                return HelperController::api_response_format(200, null, 'there is no student ');

            return HelperController::api_response_format(200, $Usersenrolled, 'students are ... ');
        }
    }

    /**
     * Get UnEnroll uses/s in these course/s
     *
     * @param  [array] users .. id
     * @param  [int..id] year
     * @param  [int..id] type
     * @param  [int..id] level
     * @param  [int..id] class
     * @param  [int..id] segment
     * @param  [array..id] course
     * @return if No current segment or year [string] There is no current segment or year
     * @return [objects] students
     */
    public function getUnEnroll(Request $request)
    {
        $request->validate([
            'year' => 'exists:academic_years,id',
            'type' => 'required|exists:academic_types,id',
            'level' => 'required|exists:levels,id',
            'class' => 'required|exists:classes,id',
            'segment' => 'exists:segments,id',
            'courses' => 'required|array',
            'courses.*' => 'required|exists:courses,id',
            'search' => 'nullable'
        ]);
        $year = AcademicYear::Get_current()->id;
        if (isset($request->year)) {
            $year = $request->year;
        }
        $academic_year_type = AcademicYearType::checkRelation($year, $request->type);
        $year_level = YearLevel::checkRelation($academic_year_type->id, $request->level);
        $class_level = ClassLevel::checkRelation($request->class, $year_level->id);
        $segment = AcademicYear::Get_current()->id;
        if (isset($request->segment)) {
            $segment = $request->segment;
        }
        $segment_class = SegmentClass::checkRelation($class_level->id, $segment);

        $course_segment = collect([]);
        foreach ($request->courses as $c)
            $course_segment[] = CourseSegment::checkRelation($segment_class->id, $c);

        if ($course_segment == null)
            return HelperController::api_response_format(200, null, 'No current segment or year');

        $ids = Enroll::whereIn('course_segment', $course_segment->pluck('id'))->pluck('user_id');
        $userUnenrolls = User::where('username', 'LIKE', "%$request->search%")->whereNotIn('id', $ids)->get();
        foreach($userUnenrolls as $user)
            $user->picture=$user->attachment->path;

        return HelperController::api_response_format(200, $userUnenrolls->paginate(HelperController::GetPaginate($request)), 'students are ... ');
    }

    /**
     * get UnEnrolled students in this tree
     *
     * @param  [int..id] year
     * @param  [int..id] type
     * @param  [int..id] level
     * @param  [int..id] class
     * @param  [int..id] segment
     * @return if course not active and invalid class [string] invalid class data or not active course
     * @return if there is no courses [string] false and null
     * @return [objects] students
     */
    public function unEnrolledUsersBulk(Request $request)
    {
        $request->validate([
            'search' => 'nullable'
        ]);
        $courseSegments = HelperController::Get_Course_segment($request);
        if ($courseSegments['result'] == false) {
            return HelperController::api_response_format(400, $courseSegments['value']);
        }
        if ($courseSegments['value'] == null) {
            return HelperController::api_response_format(400, null, 'No Course active in segment');
        }

        $ids = Enroll::whereIn('course_segment', $courseSegments['value']->pluck('id'))->pluck('user_id');
        $userUnenrolls = User::where('username', 'LIKE', "%$request->search%")->whereNotIn('id', $ids)->get();
        return HelperController::api_response_format(200, $userUnenrolls->paginate(HelperController::GetPaginate($request)), 'students are ... ');
    }

    /**
     * Enroll uses/s to course/s with chain
     *
     * @param  [array] users .. id
     * @param  [array] role_id
     * @param  [array] courses .. id
     * @param  [int..id] year
     * @param  [int..id] type
     * @param  [int..id] level
     * @param  [int..id] class
     * @param  [int..id] segment
     * @return if these users enrolled before [string] those users already enrolled     *
     * @return if there is no courses [string] there is no course segment here
     * @return [objects] Enrolled successfilly
     */
    public function enrollWithChain(Request $request)
    {
        $request->validate([
            'users' => 'required|array',
            'users.*' => 'required|string|exists:users,id',
            'role_id' => 'required|array|exists:roles,id',
            'year' => 'exists:academic_years,id',
            'type' => 'exists:academic_types,id|required_with:level',
            'level' => 'exists:levels,id|required_with:class',
            'class' => 'exists:classes,id',
            'segment' => 'exists:segments,id',
            'courses' => 'array|exists:courses,id'
        ]);

        $courseSeg = GradeCategoryController::getCourseSegment($request);
        if (isset($courseSeg)) {
            $count = 0;
            foreach ($request->users as $user) {
                foreach ($courseSeg as $course) {
                    $check = Enroll::IsExist($course, $user,$request->role_id[$count]);
                    if ($check == null) {
                        Enroll::Create([
                            'user_id' => $user,
                            'course_segment' => $course,
                            'role_id' => $request->role_id[$count],
                        ]);
                    } else
                        $EnrolledBefore[] = $user[$count];
                }
                $count++;
            }
            if (isset($EnrolledBefore))
                return HelperController::api_response_format(200, array_values(array_unique($EnrolledBefore)), 'Success and theses users added before');
            else
                return HelperController::api_response_format(200, 'Enrolled Successfully');
        }
        return HelperController::api_response_format(200, 'No Course Segment here');
    }

    public function Migration(Request $request)
    {
        $request->validate([
            'users' => 'array|required|exists:users,id',
            'new_class' => 'exists:classes,id'
        ]);

        foreach ($request->users as $user1) {
            $user = User::find($user1);
            $req = new Request([
                'class' => $user->class_id,
                'level' => $user->level,
                'type' => $user->type,
            ]);
            $oldcourseSeg = GradeCategoryController::getCourseSegment($req);
            $userGrade1 = UserGrade::where('user_id', $user1)->with(['GradeItems.GradeCategory' => function ($query) use ($oldcourseSeg) {
                $query->whereIn('course_segment_id', $oldcourseSeg);
            }])->get();
            $oldGradeItems = $userGrade1->pluck('GradeItems');
            $requestenroll = new Request([
                'class' => $request->new_class,
                'level' => $user->level,
                'type' => $user->type,
                'users' => $request->users
            ]);
            $newcourseSeg = GradeCategoryController::getCourseSegment($requestenroll);

            if (!$newcourseSeg)
                return HelperController::api_response_format(200, 'No Course Segment here');

            self::EnrollInAllMandatoryCourses($requestenroll);
            Enroll::whereIn('course_segment', $oldcourseSeg)->whereIn('user_id', $request->users)->delete();
            $newGradeItms = GradeCategory::whereIn('course_segment_id', $newcourseSeg)->with('GradeItems')->get();
            $newsItems = $newGradeItms->pluck('GradeItems');
            if (!$newGradeItms)
                continue;

            foreach ($oldGradeItems as $oldItems) {
                $useGrade = $oldItems->UserGrade->where('user_id', $user1);
                foreach ($newsItems as $newItems) {
                    foreach ($newItems as $new) {
                        if ($oldItems->name == $new->name) {
                            UserGrade::firstOrCreate([
                                'grade_item_id' => $new->id,
                                'user_id' => $user1,
                                'raw_grade' => $useGrade[0]->raw_grade
                            ]);
                        }
                        UserGrade::where('grade_item_id', $oldItems->id)->where('user_id', $user1)->delete();
                    }
                }
            }
        }
        return HelperController::api_response_format(200, 'User Migrated to class ' . $request->new_class);
    }
}
