<?php

namespace App\Listeners;

use App\Events\QuizAttemptEvent;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\GradeCategory;
use App\GradeItems;
use Modules\QuestionBank\Entities\QuizLesson;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AttemptItemlistener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  QuizAttemptEvent  $event
     * @return void
     */
    public function handle(QuizAttemptEvent $event)
    {
        $QuizID = QuizLesson::find($event->attempt->quiz_lesson_id)->quiz->id;
        $GradeCategory = GradeCategory::where('instance_id' , $QuizID)->first();
        $gradeItem = GradeItems::create([
            'type' => 'Attempts',
            'item_id' => $event->attempt->id,
            'name' => 'Attempt number ' .$event->attempt->attempt_index,
            'grade_category_id' => $GradeCategory->id,
        ]);
    }
}
