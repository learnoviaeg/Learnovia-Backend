<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use App\Enroll;
use App\User;
use App\Segment;
use App\Classes;
use App\Course;
use App\CourseSegment;
use Validator;

class EnrollStaff implements ToModel, WithHeadingRow
{
    /**
    * @param Collection $collection
    */
    public function model(array $row)
    {
        $messages = [
            'exists' => 'user with username '.$row['username'].' not found',
            'exists' => 'role'.$row['role_id'].' not found'
        ];
        $validator = Validator::make($row,[
            'class_id' => 'required|exists:classes,id',
            'segment_id' => 'required|exists:segments,id',
            'username' => 'required|exists:users,username',
            'role_id' => 'required|exists:roles,id'
        ],$messages)->validate();

        $optional='course';
        $count=1;
        while(isset($row[$optional.$count])){
            $course_id=Course::where('short_name',$row[$optional.$count])->pluck('id')->first();
            if(!isset($course_id))
            // break;
                die('shortname '.$row[$optional.$count].' doesn\'t exist');

            $userId =User::FindByName($row['username'])->id;

            $level=Classes::find($row['class_id'])->level_id;
            $segment=Segment::find($row['segment_id']);
            $segment_id=$segment->id;
            $type=$segment->academic_type_id;
            $year=$segment->academic_year_id;

            Enroll::firstOrCreate([
                'user_id' => $userId,
                'role_id'=> $row['role_id'],
                'year' => $year,
                'type' => $type,
                'level' => $level,
                'group' => $row['class_id'],
                'segment' => $segment_id,
                'course' => $course_id
            ]);

            $count++;
        }
    }
}
