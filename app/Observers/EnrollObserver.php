<?php

namespace App\Observers;

use App\CourseSegment;
use App\Enroll;
use App\Http\Controllers\HelperController;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Log;
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

        $log=Log::create([
            'user' => User::find(Auth::id())->username,
            'action' => 'created',
            'model' => 'Enroll',
            'data' => serialize($enroll),
        ]);
    }

    /**
     * Handle the user "updated" event.
     *
     * @return void
     */
    public function updated(Enroll $req)
    {
        $arr=array();
        $arr['before']=$req->getOriginal();
        $arr['after']=$req;

        Log::create([
            'user' => User::find(Auth::id())->username,
            'action' => 'updated',
            'model' => 'Enroll',
            'data' => serialize($arr),
        ]);
    }

    /**
     * Handle the user "deleted" event.
     *
     * @return void
     */
    public function deleted(Enroll $req)
    {
        $log=Log::create([
            'user' => User::find(Auth::id())->username,
            'action' => 'deleted',
            'model' => 'Enroll',
            'data' => serialize($req),
        ]);
    }

    /**
     * Handle the user "restored" event.
     *
     * @return void
     */
    public function restored(Enroll $req)
    {
        //
    }

    /**
     * Handle the user "force deleted" event.
     *
     * @return void
     */
    public function forceDeleted(Enroll $req)
    {
        //
    }
}
