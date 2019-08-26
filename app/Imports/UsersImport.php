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
            'firstname'=>'required|alpha',
            'lastname'=>'required|alpha',
            'role_id'=>'required|exists:roles,id'
        ])->validate();



        $optionals = ['arabicname', 'country', 'birthdate', 'gender', 'phone', 'address', 'nationality', 'notes', 'email',
                        'language','timezone','religion','second language'];
        $enrollOptional = 'optional';
        $teacheroptional='course';

        $password = mt_rand(100000, 999999);

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
                $user->$optional = $row[$optional];
            }
        }
        $user->save();

        if (isset($row['start_date'])&&isset($row['end_date']))
        {
            Validator::make($row,[
                'type' => 'required|exists:academic_types,id',
                'level' => 'required|exists:levels,id',
                'class' => 'required|exists:classes,id',
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

            $time=['start_date'=>Date::excelToDateTimeObject($row['start_date']),'end_date' =>Date::excelToDateTimeObject($row['end_date'])];

            Validator::make($time,[
                'start_date'=> 'required|before:end_date|after:' . Carbon::now(),
                'end_date' => 'required|after:' . Carbon::now()
            ])->validate();

            Validator::make($row,[
                'role_id'=>'required|exists:roles,id',
            ])->validate();

            $role = Role::find($row['role_id']);
            $user->assignRole($role);
            if ($row['role_id'] == 3) {
                $request = new Request([
                    'username' => array($user->username),
                    'start_date' => Date::excelToDateTimeObject($row['start_date']),
                    'end_date' => Date::excelToDateTimeObject($row['end_date']),
                    'year' => $year,
                    'type' => $row['type'],
                    'level' => $row['level'],
                    'class' => $row['class'],
                    'segment' => $segment
                ]);

                EnrollUserToCourseController::EnrollInAllMandatoryCourses($request);

                $enrollcounter=1;
                while(isset($row[$enrollOptional.$enrollcounter])) {

                    $course_id=Course::findById($row[$enrollOptional.$enrollcounter]);
                    $courseSeg=CourseSegment::getidfromcourse($course_id);
                    if($courseSeg == null)
                        break;
                    $userId =User::FindByName($user->username)->id;

                    foreach($courseSeg as $course_seg)
                    {
                        Enroll::firstOrCreate([
                            'course_segment' => $course_seg,
                            'user_id' => $userId,
                            'start_date' => Date::excelToDateTimeObject($row['start_date']),
                            'username'=> $user->username,
                            'end_date' => Date::excelToDateTimeObject($row['end_date']),
                            'role_id'=> 3
                        ]);

                        $enrollcounter++;
                    }

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
                            'start_date' => Date::excelToDateTimeObject($row['start_date']),
                            'username'=> $user->username,
                            'end_date' => Date::excelToDateTimeObject($row['end_date']),
                            'role_id'=> 4
                        ]);

                        $teachercounter++;
                    }
                }
            }
        }

    }
}
