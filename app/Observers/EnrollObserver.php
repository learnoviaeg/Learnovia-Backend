<?php
 
namespace App\Observers;

use App\CourseSegment;
use App\Enroll;
use App\Http\Controllers\HelperController;
use App\User;
use PhpParser\Node\Stmt\Continue_;

class EnrollObserver
{
    public function created(Enroll $enroll)
    {
        $user = User::find($enroll->user_id);

        $courseSeg=CourseSegment::find($enroll->course_segment);
        if($courseSeg->mandatory != 1)
            // continue;
            return HelperController::api_response_format(400, $courseSeg, 'This Course not mandatory');

        $user->update([
            'class_id' => $courseSeg->segmentClasses[0]->classLevel[0]->class_id,
            'level' => $courseSeg->segmentClasses[0]->classLevel[0]->yearLevels[0]->level_id,
            'type' => $courseSeg->segmentClasses[0]->classLevel[0]->yearLevels[0]->yearType[0]->academic_type_id
        ]);
    }
}