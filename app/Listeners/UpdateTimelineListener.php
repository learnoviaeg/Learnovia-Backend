<?php

namespace App\Listeners;

use App\Events\UpdatedQuizQuestionsEvent;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\QuestionBank\Entities\QuizLesson;
use Modules\QuestionBank\Entities\quiz_questions;
use App\Repositories\RepportsRepositoryInterface;
use Modules\QuestionBank\Entities\Quiz;
use App\GradeCategory;
use App\UserGrader;
use App\Lesson;
use App\Timeline;
use App\Enroll;

class UpdateTimelineListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(RepportsRepositoryInterface $report)
    {
        $this->report = $report;
    }

    /**
     * Handle the event.
     *
     * @param  UpdatedQuizQuestionsEvent  $event
     * @return void
     */
    public function handle(UpdatedQuizQuestionsEvent $event)
    {
        $quiz = Quiz::where('id',$event->Quiz)->first();
        $quiz_lessons = QuizLesson::where('quiz_id',$event->Quiz)->get();
        foreach($quiz_lessons as $quiz_lesson){
            $lesson = Lesson::find($quiz_lesson['lesson_id']);
            $course_id = $lesson->courseSegment->course_id;
            $class_id = $lesson->courseSegment->segmentClasses[0]->classLevel[0]->class_id;
            $level_id = $lesson->courseSegment->segmentClasses[0]->classLevel[0]->yearLevels[0]->level_id;
             Timeline::firstOrCreate([
                'item_id' => $event->Quiz,
                'name' => $quiz->name,
                'start_date' => $quiz_lesson->start_date,
                'due_date' => $quiz_lesson->due_date,
                'publish_date' => isset($quiz_lesson->publish_date)? $quiz_lesson->publish_date : Carbon::now(),
                'course_id' => $course_id,
                'class_id' => $class_id,
                'lesson_id' => $quiz_lesson->lesson_id,
                'level_id' => $level_id,
                'type' => 'quiz',
                'visible' => $quiz_lesson->visible
    
            ]);
        }
    }
}
