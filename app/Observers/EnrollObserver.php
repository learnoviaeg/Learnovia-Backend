<?php
 
namespace App\Observers;

use App\CourseSegment;
use App\Enroll;
use App\User;

class EnrollObserver
{
    public function created(Enroll $enroll)
    {
        // dd($enroll);
        $user = User::find($enroll->user_id);
        // dd($user);
        $courseSeg=CourseSegment::find($enroll->course_segment);
        // dd($courseSeg->segmentClasses);
        $user->update([
            'class_id' => $courseSeg->segmentClasses[0]->classLevel[0]->class_id,
            'level' => $courseSeg->segmentClasses[0]->classLevel[0]->yearLevels[0]->level_id,
            'type' => $courseSeg->segmentClasses[0]->classLevel[0]->yearLevels[0]->yearType[0]->academic_type_id
        ]);
    }
}