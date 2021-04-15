<?php

namespace App\Http\Controllers;

use App\AcademicYear;
use App\AcademicYearType;
use App\Http\Controllers\HelperController;
use App\YearLevel;
use Illuminate\Http\Request;
use App\User;
use Auth;
use App\Enroll;
use App\Level;
use App\Segment;
use App\ClassLevel;
use App\CourseSegment;
use App\Course;
use App\GradeCategory;
use App\SegmentClass;
use App\Classes;
use App\Imports\UsersImport;
use Spatie\Permission\Models\Role;
use App\Http\Controllers\ExcelController;
use App\UserGrade;
use App\Exports\teacherwithcourse;
use App\Exports\StudentEnrolls;
use App\Exports\classeswithstudents;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use App\LastAction;
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
                    $courseseg= CourseSegment::find($courses->id);
                    $level= $courseseg->segmentClasses[0]->classLevel[0]->yearLevels[0]->level_id;
                    $segment= $courseseg->segmentClasses[0]->segment_id;
                    $type = $courseseg->segmentClasses[0]->classLevel[0]->yearLevels[0]->yearType[0]->academic_type_id;
                    $year = $courseseg->segmentClasses[0]->classLevel[0]->yearLevels[0]->yearType[0]->academic_year_id;
                    $check = Enroll::IsExist($courses->id, $user_id,$request->role_id[$rolecount]);
                    if (!$check) {
                        $enroll = new Enroll;
                        $enroll->setAttribute('user_id', $user_id);
                        $enroll->setAttribute('course_segment', $courses->id);
                        $enroll->setAttribute('role_id', $request->role_id[$rolecount]);
                        $enroll->setAttribute('year', $year);
                        $enroll->setAttribute('type', $type);
                        $enroll->setAttribute('level', $level);
                        $enroll->setAttribute('class', $request->class);
                        $enroll->setAttribute('segment', $segment);
                        $enroll->setAttribute('course', CourseSegment::whereId($courses->id)->pluck('course_id')->first());
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
                return HelperController::api_response_format(200, $data, __('messages.enroll.already_enrolled'));
            }
            return HelperController::api_response_format(200, [], __('messages.enroll.add'));
        } else {
            return HelperController::api_response_format(200, [], __('messages.error.data_invalid'));
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
            'user_id' => 'required|array|exists:users,id',
            'year' => 'exists:academic_years,id',
            'type' => 'exists:academic_types,id',
            'level' => 'exists:levels,id',
            'class' => 'exists:classes,id',
            'segment' => 'exists:segments,id',
            'courses' => 'array|exists:courses,id'
        ]);
        $courseSegment = GradeCategoryController::getCourseSegment($request);
        if ($courseSegment == null)
            return HelperController::api_response_format(200, null, __('messages.error.no_available_data'));

        $enroll = Enroll::whereIn('course_segment', $courseSegment)->whereIn('user_id', $request->user_id)->first();
        if(isset($enroll))
            $enroll->delete();
                
        return HelperController::api_response_format(200, null, __('messages.enroll.delete'));
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
            $exist_user=collect();
            $x = HelperController::Get_segment_class($request);
            if ($x != null) {
                $segments = collect([]);
                if (count($x->courseSegment) < 1) {
                    return HelperController::api_response_format(400, [], __('messages.enroll.no_courses_belong_to_class'));
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

                if ($request->has('course') && count($request->course) > 0) {
                    foreach($request->course as $course){
                        $courseSegment = CourseSegment::GetWithClassAndCourse($request->class,$course);
                        if(isset($courseSegment)){
                            Enroll::firstOrCreate([
                                'user_id' => $user,
                                'course_segment' => $courseSegment->id,
                                'role_id' => 3,
                                'year' => isset($request->year) ? $request->year : AcademicYear::Get_current()->id,
                                'type' => $request->type,
                                'level' => $request->level,
                                'class' => $request->class,
                                'segment' => isset($request->segment) ? $request->segment : Segment::Get_current($request->type)->id,
                                'course' => $courseSegment->course_id,
                            ]);
                        }
                    }
                }

                // $check = Enroll::where('user_id', $user)->whereIn('course_segment', $segments)->pluck('id');
                // if (count($check) == 0) {
                    foreach ($segments as $segment) {
                        $check = Enroll::where('user_id', $user)->where('course_segment', $segment)->pluck('id');
                        if(count($check) > 0)
                            continue;
                        Enroll::firstOrCreate([
                            'user_id' => $user,
                            'course_segment' => $segment,
                            'role_id' => 3,
                            'year' => isset($request->year) ? $request->year : AcademicYear::Get_current()->id,
                            'type' => $request->type,
                            'level' => $request->level,
                            'class' => $request->class,
                            'segment' => isset($request->segment) ? $request->segment : Segment::Get_current($request->type)->id,
                            'course' => CourseSegment::whereId($segment)->pluck('course_id')->first(),
                        ]);
                    }
                // } else {
                //     $count++;
                    // $exist_user->push(User::find($user));
                // }
            } else
                return HelperController::api_response_format(400, [], __('messages.error.no_active_year'));
        }
        //($count);
        // if ($count > 0) {
        //     return HelperController::api_response_format(200, $exist_user->paginate(HelperController::GetPaginate($request)), 'enrolled and found user added before');
        // }
        return HelperController::api_response_format(200, [], __('messages.enroll.add'));
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
        LastAction::lastActionInCourse($request->course_id);
        if ($request->class_id == null) {
            $course_seg_id = CourseSegment::getidfromcourse($request->course_id);

            $users_id = Enroll::whereIn('course_segment', $course_seg_id)->where('role_id', 3)->pluck('user_id');

            if ($request->filled('search')) {

                $users = User::whereIn('id', $users_id)->where(function ($query) use ($request) {
                    $query->WhereRaw("concat(firstname, ' ', lastname) like '%$request->search%' ")
                    ->orWhere('arabicname', 'LIKE' ,"%$request->search%" )
                    ->orWhere('username', 'LIKE', "%$request->search%");
                })->get();

                return HelperController::api_response_format(200, $users);
            }

            $users = User::whereIn('id', $users_id)->get();

            //return all users that enrolled in this course
            return HelperController::api_response_format(200, $users, __('messages.users.students_list'));
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
                    $query->WhereRaw("concat(firstname, ' ', lastname) like '%$request->search%' ")
                    ->orWhere('arabicname', 'LIKE' ,"%$request->search%" )
                    ->orWhere('username', 'LIKE', "%$request->search%");
                })->get();

                return HelperController::api_response_format(200, $users);
            }

            if ($usersByClass->isEmpty())
                return HelperController::api_response_format(200, null, __('messages.error.no_available_data'));

            foreach ($result as $users)
                $Usersenrolled[] = User::findOrFail($users);

            if (!isset($Usersenrolled))
                return HelperController::api_response_format(200, null, __('messages.error.no_available_data'));

            return HelperController::api_response_format(200, $Usersenrolled, __('messages.users.students_list'));
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
            return HelperController::api_response_format(200, null, __('messages.error.no_active_year'));

        $ids = Enroll::whereIn('course_segment', $course_segment->pluck('id'))->pluck('user_id');
        $userUnenrolls = User::where('username', 'LIKE', "%$request->search%")->whereNotIn('id', $ids)->get();
        foreach($userUnenrolls as $user)
            if(isset($user->attachment))
                $user->picture = $user->attachment->path;

        return HelperController::api_response_format(200, $userUnenrolls->paginate(HelperController::GetPaginate($request)), __('messages.users.students_list'));
    }

    /**
     * @param  [int] year
     * @param  [int] type
     * @param  [int] level
     * @param  [int] class
     * @param  [int] courses
     * @param  [int] segment
     */
    public function UnEnrolledUsers(Request $request)
    {
        $request->validate([
            'year' => 'exists:academic_years,id',
            'type' => 'exists:academic_types,id|required_with:level',
            'level' => 'exists:levels,id|required_with:class',
            'class' => 'exists:classes,id',
            'segment' => 'exists:segments,id',
            'courses' => 'array|exists:courses,id',
            'student' => 'required|in:1,0',
            'search' => 'string'
        ]);

        $usersall=User::whereNotNull('id');
        if($request->filled('country'))
            $usersall->where('country','LIKE',"%$request->country%");
        if($request->filled('nationality'))
            $usersall->where('nationality','LIKE',"%$request->nationality%");
        if($request->filled('religion'))
            $usersall->where('religion','LIKE',"%$request->religion%");
        if($request->filled('gender'))
            $usersall->where('gender','LIKE',"$request->gender");

        $flg=false;
        $users = Enroll::where('user_id','!=' ,Auth::id());
        if($request->filled('year')){
            $users->where('year',$request->year);
            $flg=true;
        }if($request->filled('type')){
            $users->where('type',$request->type);
            $flg=true;
        }if($request->filled('level')){
            $users->where('level',$request->level);
            $flg=true;
        }if($request->filled('segment')){
            $users->where('segment',$request->segment);
            $flg=true;
        }if($request->filled('class')){
            $users->where('class',$request->class);
            $flg=true;
        }if($request->filled('courses')){
            $users->whereIn('course',$request->courses);
            $flg=true;
        }
        if($flg)
            $intersect = array_intersect($usersall->pluck('id')->toArray(),$users->pluck('user_id')->toArray());
        else
            $intersect=$usersall->pluck('id')->toArray();



        $users_student=collect();
        $users_staff=collect();
        $searched=collect();
        foreach($intersect as $oneobj)
        {
            $users2=User::find($oneobj);
            if(isset($users2->attachment)){
                $users2->picture =$users2->attachment->path;
            }

            if(count($users2->roles) > 0)
            {
                if($users2->roles[0]->id == 3)
                    $users_student->push($users2);
                else
                    $users_staff->push($users2);
            }
        }
        if($request->student == 1)
        {
            //search a student
            if(isset($request->search))
            {
                $users_student =  User::whereHas("roles",function ( $q){
                    $q->where('name',"Student");
                })->where( function($q)use($request){
                    $q->orWhere('arabicname', 'LIKE' ,"%$request->search%" )
                        ->orWhere('username', 'LIKE' ,"%$request->search%" )
                        ->orWhereRaw("concat(firstname, ' ', lastname) like '%$request->search%' ");
                    })->with('attachment');
                return HelperController::api_response_format(200, $users_student->paginate(HelperController::GetPaginate($request)),__('messages.users.students_list'));
            }
            return HelperController::api_response_format(200, $users_student->paginate(HelperController::GetPaginate($request)), __('messages.users.students_list'));
        }
        else
        {
            //search one of staff
            if(isset($request->search))
            {
                $users_staff =  User::whereHas("roles",function ( $q){
                   $q->where('name',"!=","Student");
               })->where(function($q) use($request){
                   $q->orWhere('arabicname', 'LIKE' ,"%$request->search%" )
                   ->orWhere('username', 'LIKE' ,"%$request->search%" )
                   ->orWhereRaw("concat(firstname, ' ', lastname) like '%$request->search%' ");
               })->with('attachment')->with('roles');
                return HelperController::api_response_format(200, $users_staff->paginate(HelperController::GetPaginate($request)), 'STAFF are ... ');
            }
            return HelperController::api_response_format(200, $users_staff->paginate(HelperController::GetPaginate($request)), 'STAFF are ... ');
        }
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
        $course_segment = GradeCategoryController::getCourseSegment($request);
        if (!isset($course_segment))
            return HelperController::api_response_format(404, 'There is no courses');

        $ids = Enroll::whereIn('course_segment', $course_segment)->pluck('user_id');
        $userUnenrolls = User::where('username', 'LIKE', "%$request->search%")->whereNotIn('id', $ids)->get();
        return HelperController::api_response_format(200, $userUnenrolls->paginate(HelperController::GetPaginate($request)), 'users that unenrolled in this chain  are ... ');
    }

    /**
     * Enroll uses/s to course/s with chain
     *
     * @param  [array] users .. id
     * @param  [array] role_id
     * @param  [array] courses .. id
     * @param  [int..id] year
     * @param  [array] type
     * @param  [array] levels
     * @param  [array] classes
     * @param  [array] segments
     * @return if these users enrolled before [string] those users already enrolled     *
     * @return if there is no courses [string] there is no course segment here
     * @return [objects] Enrolled successfilly
     */
    public function enrollWithChain(Request $request)
    {
        $rules = [
            'users' => 'required|array',
            'users.*' => 'required|string|exists:users,id',
            'role_id' => 'required|exists:roles,id',
            'year' => 'exists:academic_years,id',
            'type' => 'array|exists:academic_types,id|required_with:level',
            'levels' => 'array|exists:levels,id|required_with:class',
            'classes' => 'array|exists:classes,id',
            'segments' => 'array|exists:segments,id',
            'courses' => 'array|exists:courses,id'
        ];

        $customMessages = [
            'users.required'   => 'Users array is required.',
            'users.exists'   => 'Invalid user supplied!.',
            'role_id.required'   => __('messages.error.role_required'),
        ];

        $this->validate($request, $rules, $customMessages);

        $courseSeg = GradeCategoryController::getCourseSegmentWithArray($request);
        // return $courseSeg;
        if (isset($courseSeg)) {
            $count = 0;
            foreach ($request->users as $user) {
                foreach ($courseSeg as $course) {
                    $courseseg = CourseSegment::find($course);
                    $class = $courseseg->segmentClasses[0]->classLevel[0]->class_id;
                    $level= $courseseg->segmentClasses[0]->classLevel[0]->yearLevels[0]->level_id;
                    $type = $courseseg->segmentClasses[0]->classLevel[0]->yearLevels[0]->yearType[0]->academic_type_id;
                    $segment =$courseseg->segmentClasses[0]->segment_id;
                        
                    $check = Enroll::IsExist($course, $user,$request->role_id);
                    if ($check == null) {
                        Enroll::Create([
                            'user_id' => $user,
                            'course_segment' => $course,
                            'role_id' => $request->role_id,
                            'year' => isset($request->year) ? $request->year : AcademicYear::Get_current()->id,
                            'type' => $type,
                            'level' => $level,
                            'class' => $class,
                            'segment' => $segment,
                            'course' => CourseSegment::whereId($course)->pluck('course_id')->first(),
                        ]);
                    } else
                        $EnrolledBefore[] = $user[$count];
                }
                $count++;
            }
            if (isset($EnrolledBefore))
                return HelperController::api_response_format(200, array_values(array_unique($EnrolledBefore)), __('messages.enroll.already_enrolled'));
            else
                return HelperController::api_response_format(200,[], __('messages.enroll.add'));
        }
        return HelperController::api_response_format(200, [],__('messages.error.no_active_segment'));
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
                return HelperController::api_response_format(200, __('messages.error.no_active_segment'));

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
                        UserGrade::where('grade_item_id', $oldItems->id)->where('user_id', $user1)->first()->delete();
                    }
                }
            }
        }
        return HelperController::api_response_format(200,null, 'User Migrated to class ' . $request->new_class);
    }

    public function EmptyCourses()
    {
        $course=array();
        $coursesNotEnrolled=CourseSegment::whereNotIn('id',Enroll::where('role_id',3)->pluck('course_segment'))->get();
        foreach($coursesNotEnrolled as $cor)
            $course[$cor->segmentClasses[0]->classLevel[0]->classes[0]->name . "_".  $cor->segmentClasses[0]->classLevel[0]->classes[0]->id][]=$cor->courses[0]->short_name;

        return HelperController::api_response_format(200, $course , 'empty courses');
    }

    public function exportcourseswithteachers(Request $request)
    {
        $request->validate([
            'search' => 'required|string',
        ]);

        $courses = Course::where('short_name', 'LIKE' ,"%$request->search%")->pluck('id');
        if(isset($courses))
            $course_segments = CourseSegment::whereIn('course_id',$courses)->pluck('id');
        if(isset($course_segments))
            $enrolls = Enroll::whereIn('course_segment',$course_segments)->where('role_id',4)->with(['user','courseSegment','classes','courses'])->get();

            // return $enrolls;
        $filename = uniqid();
        $file = Excel::store(new teacherwithcourse($enrolls), 'tech'.$filename.'.xls','public');
        $file = url(Storage::url('tech'.$filename.'.xls'));
        return HelperController::api_response_format(201,$file, __('messages.success.link_to_file'));
        // return HelperController::api_response_format(201,$enrolls, 'enrolls');
    }

    public function StudentdInLevels(Request $request)
    {
        $allUsers=Enroll::pluck('user_id')->unique();

        $duplicated_users=array();
        foreach($allUsers as $user)
        {
            $usr=User::find($user);
            if(isset($usr)){
                $levels=Enroll::where('user_id',$user)->where('role_id',3)->pluck('level')->unique();
                if(count($levels) > 1){
                    foreach($levels as $level){
                        $lvlOBJ=Level::find($level);
                        if(isset($lvlOBJ))
                            $lvl[]=$lvlOBJ->name;
                    }
                    $duplicated_users[$usr->username]=$lvl;
                    $lvl=[];
                }
            }
        }

        // $filename = uniqid();
        // $file = Excel::store(new StudentEnrolls($duplicated_users), 'students'.$filename.'.xlsx','public');
        // $file = url(Storage::url('students'.$filename.'.xlsx'));
        // return HelperController::api_response_format(201,$file, 'Link to file ....');
        return $duplicated_users;
    }

    public function exportstudentsenrolls(Request $request)
    {

        $CS_ids=GradeCategoryController::getCourseSegment($request);
        if(count($CS_ids) == 0)
            return HelperController::api_response_format(201,[], 'No active course segments');
            
        $enrolls = Enroll::whereIn('course_segment',$CS_ids)->where('role_id',3)->with(['user','levels','classes'])->get()->groupBy(['levels.name','classes.name']);
        $filename = uniqid();
        $file = Excel::store(new classeswithstudents($enrolls), 'students'.$filename.'.xls','public');
        $file = url(Storage::url('students'.$filename.'.xls'));
        return HelperController::api_response_format(201,$file, __('messages.success.link_to_file'));
        // return HelperController::api_response_format(201,$enrolls, 'enrolls');
    }

    public function updateenrolls(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'except_courses' => 'array',
            'except_courses.*' => 'exists:courses,id',
            'new_role' => 'required|exists:roles,id'
        ]);

        $enrolls = Enroll::where('user_id',$request->user_id);

        if($request->filled('except_courses'))//courses that thier role shoudn't be changed
            $enrolls->whereNotIn('course',$request->except_courses);

        $enrolls->update([
            'role_id' => $request->new_role
        ]);

        return HelperController::api_response_format(201,$enrolls, 'updated');
    }

    public function reset_enrollment(Request $request)
    {
        $request->validate([
            'user_id' => 'required|array|exists:users,id'
        ]);

        $enroll=Enroll::whereIn('user_id',$request->user_id)->delete();

        return HelperController::api_response_format(200, null, __('messages.enroll.delete'));
    }
}
