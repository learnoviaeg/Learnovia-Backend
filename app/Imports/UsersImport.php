<?php

namespace App\Imports;

use DB;
use App\User;
use App\Course;
use App\CourseSegment;
use Maatwebsite\Excel\Concerns\ToModel;
use Spatie\Permission\Models\Role;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Http\Request;
use App\Http\Controllers\EnrollUserToCourseController;
use App\Enroll;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Maatwebsite\Excel\Concerns\WithValidation;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\HelperController;

class UsersImport implements ToModel, WithHeadingRow
{
    private $count = 0;
    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        Validator::make($row, [
            'firstname' => 'required|alpha',
            'lastname' => 'required|alpha',
            'role_id' => 'required|exists:roles,id',
        ])->validate();
        $optionals = ['arabicname', 'country', 'birthdate', 'gender', 'phone', 'address', 'nationality', 'notes', 'email',
                        'language','timezone','religion','second language'];
        $enrollOptional = 'optional';
        $teacheroptional='course';


        // dd($classSegID);
        $password = mt_rand(100000, 999999);

        $user = new User([
            'firstname' => $row['firstname'],
            'lastname' => $row['lastname'],
            'username' => User::generateUsername(),
            'password' => bcrypt($password),
            'real_password' => $password
        ]);

        foreach ($optionals as $optional) {
            if (isset($row[$optional]))
                $user->$optional = $row[$optional];
        }
        $user->save();

        $role = Role::find($row['role_id']);
        $user->assignRole($role);
        if ($row['role_id'] == 3) {
            $request = new Request([
                'username' => array($user->username),
                'start_date' => Date::excelToDateTimeObject($row['start_date']),
                'end_date' => Date::excelToDateTimeObject($row['end_date']),
                'year' => $row['year'],
                'type' => $row['type'],
                'level' => $row['level'],
                'class' => $row['class'],
                'segment' => $row['segment']
            ]);

            EnrollUserToCourseController::EnrollInAllMandatoryCourses($request);

            $enrollcounter=1;
            while(isset($row[$enrollOptional.$enrollcounter])) {

                $option = new Request([
                    'year' => $row['year'],
                    'type' => $row['type'],
                    'level' => $row['level'],
                    'class' => $row['class'],
                    'segment' => $row['segment'],
                    'course' => $row['course'],
                    'start_date' => Date::excelToDateTimeObject($row['start_date']),
                    'users'=> array($user->username),
                    'end_date' => Date::excelToDateTimeObject($row['end_date']),
                    'role_id'=>array(3)
                ]);

                EnrollUserToCourseController::EnrollCourses($option);

                $enrollcounter++;
            }
        }
        else{
            $teachercounter=1;
            while(isset($row[$teacheroptional.$teachercounter])){
                $course_id=Course::findByName($row[$teacheroptional.$teachercounter]);
                $courseSeg=CourseSegment::getidfromcourse($course_id);
                $userId =User::FindByName($user->username)->id;

                Enroll::create([
                    'course_segment' => $courseSeg,
                    'user_id' => $userId,
                    'start_date' => Date::excelToDateTimeObject($row['start_date']),
                    'username'=> $user->username,
                    'end_date' => Date::excelToDateTimeObject($row['end_date']),
                    'role_id'=> 4
                ]);

                $teachercounter++;
            }
        }
        $this->count++;
        return $user;
    }

}
