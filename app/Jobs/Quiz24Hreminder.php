<?php

namespace App\Jobs;

use App\Notifications\QuizNotification;
use App\Notifications\SendNotification;
use App\Parents;
use App\SecondaryChain;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Modules\QuestionBank\Entities\userQuiz;
use Carbon\Carbon;

class Quiz24Hreminder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $quiz;
    
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($quiz)
    {
        $this->quiz = $quiz;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $quizLessons = $this->quiz->quizLesson;
   
        foreach($quizLessons as $quizLesson){

            $answeredUsers = userQuiz::where('quiz_lesson_id',$quizLesson->id)->select('user_id')->distinct()->pluck('user_id');
           
            $allUsers = SecondaryChain::where('lesson_id',$quizLesson->lesson_id)->whereNotIn('user_id',$answeredUsers)->where('role_id',3)->whereHas('Teacher')->select('user_id')->distinct()->pluck('user_id');
           
            $parents = Parents::whereIn('child_id',$allUsers)->pluck('parent_id');
          
            $allUsers = $allUsers->merge($parents);
        
            if(count($allUsers) > 0){
               
                //sending notifications
                // $notification = new QuizNotification($quizLesson,'Quiz '.$this->quiz->name.' will be closed soon, Hurry up to solve it.');
                // $notification->send();    

            }
        }
    }
}