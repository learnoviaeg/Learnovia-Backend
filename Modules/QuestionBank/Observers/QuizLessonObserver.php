<?php

namespace Modules\QuestionBank\Observers;

use App\Repositories\RepportsRepositoryInterface;
use Modules\QuestionBank\Entities\QuizLesson;
use Modules\QuestionBank\Entities\Quiz;
use App\Events\MassLogsEvent;
use App\Lesson;
use App\Timeline;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Log;
use App\User;
use App\LessonComponent;

class QuizLessonObserver
{
    protected $report;

    public function __construct(RepportsRepositoryInterface $report)
    {
        $this->report = $report;
    }
    /**
     * Handle the quiz lesson "created" event.
     *
     * @param  \App\QuizLesson  $quizLesson
     * @return void
     */
    public function created(QuizLesson $quizLesson)
    {
        $quiz = Quiz::where('id',$quizLesson->quiz_id)->first();
        $lesson = Lesson::find($quizLesson->lesson_id);
        $course_id = $lesson->courseSegment->course_id;
        $class_id = $lesson->courseSegment->segmentClasses[0]->classLevel[0]->class_id;
        $level_id = $lesson->courseSegment->segmentClasses[0]->classLevel[0]->yearLevels[0]->level_id;
        if(isset($quiz)){
            Timeline::firstOrCreate([
                'item_id' => $quizLesson->quiz_id,
                'name' => $quiz->name,
                'start_date' => $quizLesson->start_date,
                'due_date' => $quizLesson->due_date,
                'publish_date' => isset($quizLesson->publish_date)? $quizLesson->publish_date : Carbon::now(),
                'course_id' => $course_id,
                'class_id' => $class_id,
                'lesson_id' => $quizLesson->lesson_id,
                'level_id' => $level_id,
                'type' => 'quiz',
                'visible' => $quizLesson->visible

            ]);
        }
    }

    /**
     * Handle the quiz lesson "updated" event.
     *
     * @param  \App\QuizLesson  $quizLesson
     * @return void
     */
    public function updated(QuizLesson $quizLesson)
    {
        $quiz = Quiz::where('id',$quizLesson->quiz_id)->first();
        if(isset($quiz)){
            $forLogs=Timeline::where('item_id',$quizLesson->quiz_id)->where('lesson_id',$quizLesson->getOriginal('lesson_id'))->where('type' , 'quiz')->first();
            $forLogs->update([
                'item_id' => $quizLesson->quiz_id,
                'name' => $quiz->name,
                'start_date' => $quizLesson->start_date,
                'due_date' => $quizLesson->due_date,
                'publish_date' => isset($quizLesson->publish_date)? $quizLesson->publish_date : Carbon::now(),
                'lesson_id' => $quizLesson->lesson_id,
                'type' => 'quiz',
                'visible' => $quizLesson->visible
            ]);
        }

        if($quizLesson->isDirty('seen_number')){
            $lesson = Lesson::find($quizLesson->lesson_id);
            $course_id = $lesson->courseSegment->course_id;
            $this->report->calculate_course_progress($course_id);
        }
    }

    /**
     * Handle the quiz lesson "deleted" event.
     *
     * @param  \App\QuizLesson  $quizLesson
     * @return void
     */
    public function deleted(QuizLesson $quizLesson)
    {
        //for log event
        $logsbefore=Timeline::where('lesson_id',$quizLesson->lesson_id)->where('item_id',$quizLesson->quiz_id)->where('type','quiz')->get();
        $all = Timeline::where('lesson_id',$quizLesson->lesson_id)->where('item_id',$quizLesson->quiz_id)->where('type','quiz')->delete();
        if($all > 0)
            event(new MassLogsEvent($logsbefore,'deleted'));

        LessonComponent::where('comp_id',$quizLesson->quiz_id)->where('lesson_id',$quizLesson->lesson_id)
        ->where('module','Quiz')->delete();

        $lesson = Lesson::find($quizLesson->lesson_id);
        $course_id = $lesson->courseSegment->course_id;
        $this->report->calculate_course_progress($course_id);
    }

    /**
     * Handle the quiz lesson "restored" event.
     *
     * @param  \App\QuizLesson  $quizLesson
     * @return void
     */
    public function restored(QuizLesson $quizLesson)
    {
        //
    }

    /**
     * Handle the quiz lesson "force deleted" event.
     *
     * @param  \App\QuizLesson  $quizLesson
     * @return void
     */
    public function forceDeleted(QuizLesson $quizLesson)
    {
        //
    }
}
