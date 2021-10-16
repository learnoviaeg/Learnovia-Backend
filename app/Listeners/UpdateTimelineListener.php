<?php

namespace App\Listeners;

use App\Events\updateQuizAndQuizLessonEvent;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

use App\Lesson;
use App\Course;
use App\Enroll;
use App\Notification;
use App\Timeline;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class updateTimelineListener
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
     * @param  updateQuizAndQuizLessonEvent  $event
     * @return void
     */
    public function handle(updateQuizAndQuizLessonEvent $event)
    {
        $lesson = Lesson::find($event->quizLesson->lesson_id);
        $course_id = $lesson->course_id;

        //sending notifications        
        $users = Enroll::whereIn('group',$lesson->shared_classes->pluck('id'))
                        ->where('course',$lesson->course_id)
                        ->where('user_id','!=',Auth::user()->id)
                        ->where('role_id','!=', 1 )->select('user_id')->distinct()->pluck('user_id')->toArray();

        $requ = new Request([
            'message' => $event->quizLesson->quiz->name . ' quiz was updated',
            'id' => $event->quizLesson->quiz->id,
            'users' => count($users) > 0 ? $users : null,
            'type' =>'quiz',
            'publish_date'=> Carbon::parse($event->quizLesson->publish_date),
            'course_id' => $lesson->course_id,
            'classes'=> $lesson->shared_classes->pluck('id')->toArray(),
            'lesson_id'=> $lesson->id,
        ]);

        (new Notification())->send($requ);
                        
        foreach($lesson->shared_classes->pluck('id') as $class){
            $timeLine=Timeline::where('item_id',$event->quizLesson->quiz_id)->where('type','quiz')->first();
            if(isset($timeLine)) // in case quiz was drafted
                $timeLine->update([
                    'name' => $event->quizLesson->quiz->name,
                    'start_date' => $event->quizLesson->start_date,
                    'due_date' => $event->quizLesson->due_date,
                    'publish_date' => $event->quizLesson->publish_date,
                    'course_id' => $course_id,
                    'class_id' => $class,
                    'lesson_id' => $event->quizLesson->lesson_id,
                    'level_id' => Course::find($lesson->course_id)->level_id,
                    'visible' => $event->quizLesson->visible
                ]);
            // dd($timeLine);
        } 
    }
}
