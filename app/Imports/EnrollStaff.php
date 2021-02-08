<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use App\Enroll;
use App\User;
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
            'class_id' => 'exists:classes,id',
            'username' => 'required|exists:users,username',
            'role_id' => 'required|exists:roles,id'
        ],$messages)->validate();

        $optional='course';
        $count=1;
        while(isset($row[$optional.$count])){
            $course_id=Course::where('short_name',$row[$optional.$count])->pluck('id')->first();
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
            $userId =User::FindByName($row['username'])->id;

            foreach($courseSeg as $course_seg)
            {
                $cour_seg=CourseSegment::find($course_seg);
                // dd($cour_seg->segmentClasses[0]->classLevel[0]);
                $class = $cour_seg->segmentClasses[0]->classLevel[0]->class_id;
                $level= $cour_seg->segmentClasses[0]->classLevel[0]->yearLevels[0]->level_id;
                $type = $cour_seg->segmentClasses[0]->classLevel[0]->yearLevels[0]->yearType[0]->academic_type_id;
                $year = $cour_seg->segmentClasses[0]->classLevel[0]->yearLevels[0]->yearType[0]->academic_year_id;
                $segment =$cour_seg->segmentClasses[0]->segment_id;

                Enroll::firstOrCreate([
                    'course_segment' => $course_seg,
                    'user_id' => $userId,
                    'role_id'=> $row['role_id'],
                    'year' => $year,
                    'type' => $type,
                    'level' => $level,
                    'class' => $class,
                    'segment' => $segment,
                    'course' => $course_id
                ]);

                $count++;
            }
        }
    }
}
