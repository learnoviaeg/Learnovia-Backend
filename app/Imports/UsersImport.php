<?php

namespace App\Imports;

use App\Http\Controllers\SpatieController;
use DB;
use App\User;
use App\Course;
use App\AcademicYear;
use App\Segment;
use App\CourseSegment;
use Maatwebsite\Excel\Concerns\ToModel;
use Spatie\Permission\Models\Role;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Http\Request;
use App\Http\Controllers\EnrollUserToCourseController;
use App\Http\Controllers\HelperController;
use App\Classes;
use App\Enroll;
use Validator;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Maatwebsite\Excel\Facades\Excel;
use App\ClassLevel;
use App\Contract;
use App\SegmentClass;
use Carbon\Carbon;

class UsersImport implements ToModel, WithHeadingRow
{
    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        Validator::make($row,[
            'firstname'=>'required',
            'lastname'=>'required',
            'role_id'=>'required|exists:roles,id'
        ])->validate();

        $optionals = ['arabicname', 'country', 'birthdate', 'gender', 'phone', 'address', 'nationality', 'notes', 'email',
                    'language', 'timezone', 'religion', 'second language', 'class_id', 'level', 'type', 'firstname',
                    'lastname', 'username', 'real_password', 'suspend'];
        $enrollOptional = 'optional'; 
        $teacheroptional='course';

        $password = mt_rand(100000, 999999);

        $max_allowed_users = Contract::whereNotNull('id')->pluck('numbers_of_users')->first();
        $users=Enroll::where('role_id',3)->get();

        // dd((count($users)));
        if(((count($users) + count($row)-1)) > $max_allowed_users)
            die('U Can\'t add users any more');

        $user = new User([
            'firstname' => $row['firstname'],
            'lastname' => $row['lastname'],
            'username' => User::generateUsername(),
            'password' => bcrypt($password),
            'real_password' => $password
        ]);

        foreach ($optionals as $optional) {
            if (isset($row[$optional])){
                if($optional =='birthdate'){
                    $row[$optional] =  Date::excelToDateTimeObject($row['birthdate']);
                }
                if($optional =='real_password'){
                    $user->$optional = $row[$optional];
                    $user->password =   bcrypt($row[$optional]);
                }
                $user->$optional = $row[$optional];
            }
        }
        $user->save();
        $role = Role::find($row['role_id']);
        $user->assignRole($role);

        if (isset($row['type'])&&isset($row['level'])&&isset($row['class_id']))
        {
            Validator::make($row,[
                'type' => 'required|exists:academic_types,id',
                'level' => 'required|exists:levels,id',
                'class_id' => 'required|exists:classes,id',
                'segment' => 'exists:segments,id',
                'year' => 'exists:academic_years,id'
            ])->validate();

            if (isset($row['year'])) {
                Validator::make($row,[
                    'year' => 'exists:academic_years,id'
                ])->validate();

                $year = $row['year'];
            }
            else
            {
                $year = AcademicYear::Get_current()->id;
            }
            if (isset($row['segment'])) {

                Validator::make($row,[
                    'segment' => 'exists:segments,id',
                ])->validate();

                $segment = $row['segment'];
            }
            else{
                $segment = Segment::Get_current($row['type'])->id;
            }

            Validator::make($row,[
                'role_id'=>'required|exists:roles,id',
            ])->validate();

            $userId[] =User::FindByName($user->username)->id;

            if ($row['role_id'] == 3) {
                $request = new Request([
                    'year' => $year,
                    'type' => $row['type'],
                    'level' => $row['level'],
                    'class' => $row['class_id'],
                    'segment' => $segment,
                    'users' => $userId
                ]);

                EnrollUserToCourseController::EnrollInAllMandatoryCourses($request);

                $enrollcounter=1;
                while(isset($row[$enrollOptional.$enrollcounter])) {

                    $course_id=Course::findById($row[$enrollOptional.$enrollcounter]);
                    // $courseSeg=CourseSegment::getidfromcourse($course_id);
                    $courseSeg=CourseSegment::GetWithClassAndCourse($row['class_id'],$course_id);
                    if($courseSeg == null)
                        break;
                    $userId =User::FindByName($user->username)->id;

                        Enroll::firstOrCreate([
                            'course_segment' => $courseSeg->id,
                            'user_id' => $userId,
                            'role_id'=> 3,
                            'year' => $year,
                            'type' => $row['type'],
                            'level' => $row['level'],
                            'class' => $row['class_id'],
                            'segment' => $segment,
                            'course' => $course_id
                        ]);

                        $enrollcounter++;
                }
            }
            else{
                $teachercounter=1;
                while(isset($row[$teacheroptional.$teachercounter])){
                    $course_id=Course::findById($row[$teacheroptional.$teachercounter]);
                    $courseSeg=CourseSegment::getidfromcourse($course_id);
                    if($courseSeg == null)
                        break;
                    $userId =User::FindByName($user->username)->id;

                    foreach($courseSeg as $course_seg)
                    {
                        Enroll::create([
                            'course_segment' => $course_seg,
                            'user_id' => $userId,
                            'role_id'=> 4,
                            'year' => $year,
                            'type' => $row['type'],
                            'level' => $row['level'],
                            'class' => $row['class_id'],
                            'segment' => $segment,
                            'course' => $course_id
                        ]);

                        $teachercounter++;
                    }
                }
            }
        }
    }
}
