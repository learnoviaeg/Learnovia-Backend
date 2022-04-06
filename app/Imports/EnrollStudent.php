<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Validator;
use App\Enroll;
use App\User;
use App\Course;
use App\Classes;
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
            'class_id' => 'exists:classes,id',
            'username' => 'required|exists:users,username',
            'segment_id' => 'required|exists:segments,id'
        ],$messages)->validate();
        
        $optional='optional';

        $user_id = User::FindByName($row['username'])->id;
        
        $level=Classes::find($row['class_id'])->level_id;
        $segment=Segment::find($row['segment_id']);
        $segment_id=$segment->id;
        $type=$segment->academic_type_id;
        $year=$segment->academic_year_id;

        $request = new Request([
            'year' => $year,
            'type' => $type,
            'level' => $level,
            'class' => $row['class_id'],
            'segment' => $row['segment_id'],
            'users' => [$user_id]
        ]);

        EnrollUserToCourseController::EnrollInAllMandatoryCourses($request);

        $count=1;
        while(isset($row[$optional.$count])) {
            $course=Course::where('short_name',$row[$optional.$count])->first();
            if($course->segment_id != $row['segment_id'])
                return HelperController::api_response_format(400, [], __('messages.enroll.error'));

            $course_id=$course->id;
            if(!isset($course_id))
                die('shortname '.$row[$optional.$count].'doesn\'t exist');

            Enroll::firstOrCreate([
                'user_id' => $user_id,
                'role_id'=> 3,
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
