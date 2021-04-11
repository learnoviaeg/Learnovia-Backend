<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Validator;
use App\Enroll;
use App\User;
use App\Course;
use App\Segment;
use App\CourseSegment;
use Illuminate\Http\Request;
use App\Http\Controllers\EnrollUserToCourseController;
use App\ClassLevel;
use App\SegmentClass;

class EnrollStudent implements ToModel,WithHeadingRow
{
    /**
    * @param Collection $collection
    */
    public function model(array $row)
    {
        $messages = [
            'exists' => 'user with username '.$row['username'].' not found'
        ];
        $validator = Validator::make($row,[
            'class_id' => 'required|exists:classes,id',
            'username' => 'required|exists:users,username',
            'segment_id' => 'exists:segments,id'
        ],$messages)->validate();
        
        $optional='optional';

        $user_id = User::FindByName($row['username'])->id;
        if(!isset($row['class_id']))
            die('class_id is required');
        $classLevel=ClassLevel::where('class_id',$row['class_id'])->pluck('id')->first();
        $level=ClassLevel::find($classLevel)->yearLevels[0]->level_id;
        $type=ClassLevel::find($classLevel)->yearLevels[0]->yearType[0]->academic_type_id;
        $year=ClassLevel::find($classLevel)->yearLevels[0]->yearType[0]->academic_year_id;

        //get current segment if there just one in all types of all system 
        $segment = Segment::where('current',1)->pluck('id')->first();
        if(isset($row['segment_id']))
            $segment=$row['segment_id'];

        $request = new Request([
            'year' => $year,
            'type' => $type,
            'level' => $level,
            'class' => $row['class_id'],
            'segment' => $segment,
            'users' => [$user_id]
        ]);

        EnrollUserToCourseController::EnrollInAllMandatoryCourses($request);

        $count=1;
        while(isset($row[$optional.$count])) {
            $course_id=Course::where('short_name',$row[$optional.$count])->pluck('id')->first();
            if(!isset($course_id))
                break;
            $courseSeg=CourseSegment::GetWithClassAndCourse($row['class_id'],$course_id);
            if($courseSeg == null)
                break;

            Enroll::firstOrCreate([
                'course_segment' => $courseSeg->id,
                'user_id' => $user_id,
                'role_id'=> 3,
                'year' => $year,
                'type' => $type,
                'level' => $level,
                'class' => $row['class_id'],
                'segment' => $segment,
                'course' => $course_id
            ]);

            $count++;
        }
    }
}
