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
use App\Contract;
use App\SegmentClass;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
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
            'role_id'=>'required|exists:roles,id',
            'language' => 'exists:languages,id',
            'second language' => 'exists:languages,id',
            'username' => 'unique:users'
        ])->validate();

        if(isset($row['email']))
        {
            Validator::make($row,[
                'email' => 'unique:users'
            ])->validate();
        }

        $optionals = ['arabicname', 'country', 'birthdate', 'gender', 'phone', 'address', 'nationality', 'notes', 'email',
                    'language', 'timezone', 'religion', 'second language', 'class_id', 'level', 'type',
                    'username', 'real_password', 'suspend'];
        $enrollOptional = 'optional'; 
        $teacheroptional='course';

        $password = mt_rand(100000, 999999);

        $max_allowed_users = Contract::whereNotNull('id')->pluck('numbers_of_users')->first();
        $users=Enroll::where('role_id',3)->get();

        // dd((count($users)));
        if(((count($users) + count($row)-1)) > $max_allowed_users)
            die('U Can\'t add users any more');
        $clientt = new Client();
        $data = json_encode(array(
            'name' => $row['firstname']. " " .$row['lastname'] ,
            'meta_data' => array(
                "image_link" => null,
                'role'=> Role::find($row['role_id'])->name,
            ),
        ));
        
        $res = $clientt->request('POST', 'https://us-central1-learnovia-notifications.cloudfunctions.net/createUser', [
            'headers'   => [
                'Content-Type' => 'application/json'
            ], 
            'body' => $data
        ]);
        $user = new User([
            'firstname' => $row['firstname'],
            'lastname' => $row['lastname'],
            'username' => User::generateUsername(),
            'password' => bcrypt($password),
            'real_password' => $password,
            'chat_uid' => json_decode($res->getBody(),true)['user_id'],
            'chat_token' => json_decode($res->getBody(),true)['custom_token'],
            'refresh_chat_token' => json_decode($res->getBody(),true)['refresh_token']
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

        if (isset($row['class_id'])){

            Validator::make($row,[
                'class_id' => 'exists:classes,id',
                'segment_id' => 'required|exists:segments,id',
            ])->validate();

            $level=Classes::find($row['class_id'])->level_id;
            $segment=Segment::find($row['segment_id']);
            $segment_id=$segment->id;
            $type=$segment->academic_type_id;
            $year=$segment->academic_year_id;

            if ($row['role_id'] == 3) {
                $request = new Request([
                    'year' => $year,
                    'type' => $type,
                    'level' => $level,
                    'class' => $row['class_id'],
                    'segment' => $segment_id,
                    'users' => [$user->id]
                ]);
                EnrollUserToCourseController::EnrollInAllMandatoryCourses($request);
        
                $enrollcounter=1;
                while(isset($row[$enrollOptional.$enrollcounter])) {
                    $course_id=Course::where('short_name',$row[$enrollOptional.$enrollcounter])->pluck('id')->first();
                    if(!isset($course_id))
                        // break;
                        die('shortname '.$row[$enrollOptional.$enrollcounter.'doesn\'t exist']);
            
                    Enroll::firstOrCreate([
                        'user_id' => $user->id,
                        'role_id'=> 3,
                        'year' => $year,
                        'type' => $type,
                        'level' => $level,
                        'group' => $row['class_id'],
                        'segment' => $segment_id,
                        'course' => $course_id
                    ]);
        
                    $enrollcounter++;
                }
            }
            else{
                $teachercounter=1;
                while(isset($row[$teacheroptional.$teachercounter])){
                    $course_id=Course::where('short_name',$row[$teacheroptional.$teachercounter])->pluck('id')->first();
                    if(!isset($course_id))
                        // break;
                        die('shortname '.$row[$enrollOptional.$enrollcounter.'doesn\'t exist']);
        
                        Enroll::firstOrCreate([
                            'user_id' => $user->id,
                            'role_id'=> $row['role_id'],
                            'year' => $year,
                            'type' => $type,
                            'level' => $level,
                            'group' => $row['class_id'],
                            'segment' => $segment_id,
                            'course' => $course_id
                        ]);
        
                        $teachercounter++;
                }
            }
        }
    }
}
