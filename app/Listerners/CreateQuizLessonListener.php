<?php

namespace App\Listerners;

use App\Events\QuizLessonEvent;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\QuestionBank\Entities\Quiz;
use App\Lesson;
use App\Timeline;

class CreateQuizLessonListener implements ShouldQueue
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
     * @param  QuizLessonEvent  $event
     * @return void
     */
    public function handle(QuizLessonEvent $event)
    {
        $quiz_lesson = $event->quiz_lesson;
        $quiz = Quiz::where('id',$quiz_lesson->quiz_id)->first();
        $lesson = Lesson::find($quiz_lesson->lesson_id);
        $course_id = $lesson->courseSegment->course_id;
        $class_id = $lesson->courseSegment->segmentClasses[0]->classLevel[0]->class_id;
        $level_id = $lesson->courseSegment->segmentClasses[0]->classLevel[0]->yearLevels[0]->level_id;
        if(isset($quiz)){
            Timeline::firstOrCreate([
                'item_id' => $quiz_lesson->quiz_id,
                'name' => $quiz->name,
                'start_date' => $quiz_lesson->start_date,
                'due_date' => $quiz_lesson->due_date,
                'publish_date' => isset($quiz_lesson->publish_date)? $quiz_lesson->publish_date : Carbon::now(),
                'course_id' => $course_id,
                'class_id' => $class_id,
                'lesson_id' => $quiz_lesson->lesson_id,
                'level_id' => $level_id,
                'type' => 'quiz'
            ]);
        }
        
    }
}
