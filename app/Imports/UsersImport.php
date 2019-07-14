<?php

namespace App\Imports;
use App\Http\Controllers\SpatieController;
use DB;
use App\User;
use App\Course;
use App\CourseSegment;
use Maatwebsite\Excel\Concerns\ToModel;
use Spatie\Permission\Models\Role;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Http\Request;
use App\Http\Controllers\EnrollUserToCourseController;
use App\Classes;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Maatwebsite\Excel\Facades\Excel;
class UsersImport implements ToModel, WithHeadingRow
{
    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        $optionals = ['arabicname', 'country', 'birthdate', 'gender', 'phone', 'address', 'nationality', 'notes', 'email'];
        $enrollOptional = ['optional1','optional2','optional3'];
        $teacheroptional=['course1','course2'];


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
            
            $classLevID=DB::table('class_levels')->where('class_id',$row['class_id'])->pluck('id')->first();
            $classSegID=DB::table('segment_classes')->where('class_level_id',$classLevID)->pluck('id')->first();
            $request = new Request([
                'username' => $user->username, 
                'start_date' => Date::excelToDateTimeObject($row['start_date']),
                'end_date' => Date::excelToDateTimeObject($row['end_date']),
                'SegmentClassId' => $classSegID
            ]);
            EnrollUserToCourseController::EnrollInAllMandatoryCourses($request);
                foreach($enrollOptional as $seg) 
                {
                    if (isset($row[$seg]))
                    {
                        $course_id=Course::findByName($row[$seg]);
                        $segmentid= CourseSegment::getidfromcourse($course_id);
                        $option = new Request([
                            'course_segment' => array($segmentid),
                            'start_date' => Date::excelToDateTimeObject($row['start_date']),
                            'users'=> array($user->username), 
                            'end_date' => Date::excelToDateTimeObject($row['end_date']),
                            'role_id'=>array(3)
    
                        ]);  
                        EnrollUserToCourseController::EnrollCourses($option);
                    }

                }

        } else {
            foreach($teacheroptional as $sege) 
            {
                if (isset($row[$sege]))
                {
                    $course_id=Course::findByName($row[$sege]);
                    $segmentid= CourseSegment::getidfromcourse($course_id);
                    $option = new Request([
                        'course_segment' => array($segmentid),
                        'start_date' => Date::excelToDateTimeObject($row['start_date']),
                        'users'=> array($user->username), 
                        'end_date' => Date::excelToDateTimeObject($row['end_date']),
                        'role_id'=>array($role->id)

                    ]);  
                    EnrollUserToCourseController::EnrollCourses($option);
                }

            }

        }
        return $user;
    }
}
