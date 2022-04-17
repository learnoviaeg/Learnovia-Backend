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
use App\Course;
use Carbon\Carbon;
use App\Timeline;
use App\Enroll;
use DB;

class createTimelineListener
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
            $course_id = $lesson->course_id;
            $timeline = DB::table('timelines')->whereIn('class_id',$lesson->shared_classes->pluck('id'))->where('item_id',$event->Quiz)->where('type','quiz')->count();
            if($timeline > 0)
                continue;
            foreach($lesson->shared_classes->pluck('id') as $class){
                Timeline::updateOrCreate([
                                    'item_id' => $event->Quiz,
                                    'class_id' => $class,
                                    'type' => 'quiz',
                                ],[
                                    'lesson_id' => $quiz_lesson->lesson_id,
                                    'name' => $quiz->name,
                                    'start_date' => $quiz_lesson->start_date,
                                    'due_date' => $quiz_lesson->due_date,
                                    'publish_date' => isset($quiz_lesson->publish_date)? $quiz_lesson->publish_date : Carbon::now(),
                                    'course_id' => $course_id,
                                    'level_id' => Course::find($lesson->course_id)->level_id,
                                    'visible' => $quiz_lesson->visible
                                ]);
                        }
                    }
                }
            }
            
            