<?php

namespace App\Jobs;

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
        //ask about parents
        $lessons = $this->quiz->quizLesson->pluck('lesson_id');

        $answeredUsers = userQuiz::whereIn('quiz_lesson_id',$this->quiz->quizLesson->pluck('id'))->select('user_id')->distinct()->pluck('user_id');
    
        $allUsers = SecondaryChain::whereIn('lesson_id',$lessons)->whereNotIn('user_id',$answeredUsers)->where('role_id',3)->whereHas('Teacher')->select('user_id')->distinct()->pluck('user_id');
        $parents = Parents::whereIn('child_id',$allUsers)->pluck('parent_id');
      
        $allUsers = $allUsers->merge($parents);
        
        if(count($allUsers) > 0){

            //notification object
            $notification = [
                'item_id' => $this->quiz->id,
                'item_type' => 'quiz',
                'publish_date' => Carbon::now(),
                'message' => 'Quiz '.$this->quiz->name.' will be closed soon, Hurry up to solve it.',
                'created_by' => $this->quiz->created_by,
                'type' => 'notification'
            ];

            //assign notification to given users
            // $createdNotification = (new SendNotification)->toDatabase($notification,$allUsers->toArray());
            
            //firebase Notifications
            // (new SendNotification)->toFirebase($createdNotification);
        }
    }
}