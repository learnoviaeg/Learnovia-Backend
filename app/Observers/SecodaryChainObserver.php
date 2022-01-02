<?php

namespace App\Observers;
use App\Enroll;
use App\Lesson;
use App\SecondaryChain;

class SecodaryChainObserver
{
    public function __construct()
    {
        //
    }

    public function created(Enroll $enroll)
    {
        // dd($enroll);
        $lessons=Lesson::where('course_id',$enroll->course)->get();
        // dd($lessons);    
        foreach ($lessons as $lesson)
        {
            if(!in_array($enroll->group ,$lesson->shared_classes->pluck('id')->toArray()))
                continue;
            SecondaryChain::firstOrCreate([
                'user_id' => $enroll->user_id,
                'role_id' => $enroll->role_id,
                'group_id' => $enroll->group,
                'course_id' => $enroll->course,
                'lesson_id' => $lesson->id,
                'enroll_id' => $enroll->id
            ]);
        }
    }
}
