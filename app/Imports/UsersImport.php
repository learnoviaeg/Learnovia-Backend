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
            ])->validate();

            if ($row['role_id'] == 3) {

                $classLevel=ClassLevel::where('class_id',$row['class_id'])->pluck('id')->first();
                $level=ClassLevel::find($classLevel)->yearLevels[0]->level_id;
                $type=ClassLevel::find($classLevel)->yearLevels[0]->yearType[0]->academic_type_id;
                $year=ClassLevel::find($classLevel)->yearLevels[0]->yearType[0]->academic_year_id;
        
                //get current segment if there just one in all types of all system 
                $segment = Segment::where('current',1)->pluck('id')->first();
                if(isset($row['segment_id']))
                {
                    Validator::make($row,[
                        'segment_id' => 'exists:segments,id',
                    ])->validate();

                    $segment=$row['segment_id'];
                }
                
                $request = new Request([
                    'year' => $year,
                    'type' => $type,
                    'level' => $level,
                    'class' => $row['class_id'],
                    'segment' => $segment,
                    'users' => [$user->id]
                ]);
                EnrollUserToCourseController::EnrollInAllMandatoryCourses($request);
        
                $enrollcounter=1;
                while(isset($row[$enrollOptional.$enrollcounter])) {
                    $course_id=Course::where('short_name',$row[$enrollOptional.$enrollcounter])->pluck('id')->first();
                    if(!isset($course_id))
                        break;
                    $courseSeg=CourseSegment::GetWithClassAndCourse($row['class_id'],$course_id);
                    if($courseSeg == null)
                        break;
            
                    Enroll::firstOrCreate([
                        'course_segment' => $courseSeg->id,
                        'user_id' => $user->id,
                        'role_id'=> 3,
                        'year' => $year,
                        'type' => $type,
                        'level' => $level,
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
                    $course_id=Course::where('short_name',$row[$teacheroptional.$teachercounter])->pluck('id')->first();
                    if(!isset($course_id))
                        break;
                    $courseSeg=CourseSegment::getidfromcourse($course_id);
                    if(isset($row['class_id'])){
                        $courseSegg=CourseSegment::GetWithClassAndCourse($row['class_id'],$course_id);
                        if(isset($courseSegg))
                            $courseSeg=[$courseSegg->id];
                    }
                    if($courseSeg == null)
                        break;
        
                    foreach($courseSeg as $course_seg)
                    {
                        $cour_seg=CourseSegment::find($course_seg);
                        $class = $cour_seg->segmentClasses[0]->classLevel[0]->class_id;
                        $level= $cour_seg->segmentClasses[0]->classLevel[0]->yearLevels[0]->level_id;
                        $type = $cour_seg->segmentClasses[0]->classLevel[0]->yearLevels[0]->yearType[0]->academic_type_id;
                        $year = $cour_seg->segmentClasses[0]->classLevel[0]->yearLevels[0]->yearType[0]->academic_year_id;
                        $segment =$cour_seg->segmentClasses[0]->segment_id;
        
                        Enroll::firstOrCreate([
                            'course_segment' => $course_seg,
                            'user_id' => $user->id,
                            'role_id'=> $row['role_id'],
                            'year' => $year,
                            'type' => $type,
                            'level' => $level,
                            'class' => $class,
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
