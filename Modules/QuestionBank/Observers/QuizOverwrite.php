<?php

namespace Modules\QuestionBank\Observers;

use Modules\QuestionBank\Entities\QuizOverride;
use Modules\QuestionBank\Entities\QuizLesson;
use Modules\QuestionBank\Entities\Quiz;
use App\Lesson;
use App\Timeline;
use Carbon\Carbon;
use App\SecondaryChain;

class QuizOverwrite
{
    /**
     * Handle the quizt override "created" event.
     *
     * @param  \App\QuizOverride  $quizOverride
     * @return void
     */
    public function created(QuizOverride $quizOverride)
    {
        $quizLesson = QuizLesson::whereId($quizOverride->quiz_lesson_id)->first();
        if(isset($quizLesson)){
            $quiz = Quiz::where('id',$quizLesson->quiz_id)->first();
            $lesson = Lesson::find($quizLesson->lesson_id);
            $secondary_chains = SecondaryChain::where('lesson_id',$quizLesson->lesson_id)
                                ->where('user_id',$quizOverride->user_id)->get()->keyBy('group_id');
            foreach($secondary_chains as $secondary_chain){
                if(isset($quiz)){
                    Timeline::firstOrCreate([
                        'item_id' => $quizLesson->quiz_id,
                        'name' => $quiz->name,
                        'start_date' => $quizOverride->start_date,
                        'due_date' => $quizOverride->due_date,
                        'publish_date' => isset($quizLesson->publish_date)? $quizLesson->publish_date : Carbon::now(),
                        'course_id' => $secondary_chain->course_id,
                        'class_id' => $secondary_chain->group_id,
                        'lesson_id' => $quizLesson->lesson_id,
                        'level_id' => $secondary_chain->Enroll->level,
                        'type' => 'quiz',
                        'overwrite_user_id' => $quizOverride->user_id
                    ]);
                }
            }
        }
    }

    /**
     * Handle the quizt override "updated" event.
     *
     * @param  \App\QuizOverride  $quizOverride
     * @return void
     */
    public function updated(QuizOverride $quizOverride)
    {
        //
    }

    /**
     * Handle the quizt override "deleted" event.
     *
     * @param  \App\QuizOverride  $quizOverride
     * @return void
     */
    public function deleted(QuizOverride $quizOverride)
    {
        //
    }

    /**
     * Handle the quizt override "restored" event.
     *
     * @param  \App\QuizOverride  $quizOverride
     * @return void
     */
    public function restored(QuizOverride $quizOverride)
    {
        //
    }

    /**
     * Handle the quizt override "force deleted" event.
     *
     * @param  \App\QuizOverride  $quizOverride
     * @return void
     */
    public function forceDeleted(QuizOverride $quizOverride)
    {
        //
    }
}
