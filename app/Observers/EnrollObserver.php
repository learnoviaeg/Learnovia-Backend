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
        if ($enroll->courseSegment->courses[0]->mandatory == 1) {
            $user = User::find($enroll->user_id);
            $user->update([
                'class_id' => $enroll->courseSegment->segmentClasses[0]->classLevel[0]->class_id,
                'level' => $enroll->courseSegment->segmentClasses[0]->classLevel[0]->yearLevels[0]->level_id,
                'type' => $enroll->courseSegment->segmentClasses[0]->classLevel[0]->yearLevels[0]->yearType[0]->academic_type_id
            ]);
        }
    }
}
