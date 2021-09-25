<?php

namespace App\Jobs;

use App\Http\Controllers\NotificationsController;
use App\Parents;
use App\SecondaryChain;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Request;
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
    
        $allUsers = SecondaryChain::whereIn('lesson_id',$lessons)->whereNotIn('user_id',$answeredUsers)->whereHas('Teacher')->select('user_id')->distinct()->pluck('user_id');
        $parents = Parents::whereIn('child_id',$allUsers)->pluck('parent_id');
      
        $allUsers = $allUsers->merge($parents);
        
        if(count($allUsers) > 0){

            //notification object
            $notify_request = new Request ([
                'id' => $this->quiz->id,
                'type' => 'quiz',
                'publish_date' => Carbon::now(),
                'title' => $this->quiz->name,
                'message' => 'Quiz '.$this->quiz->name.' will close after 24 hours.',
                'from' => $this->quiz->created_by,
                'users' => $allUsers->toArray()
            ]);
            
            // use notify store function to notify users with the announcement
            try {
                $notify = (new NotificationsController)->store($notify_request);
            } catch (\Throwable $th) {
                //throw $th;
            }
        }
    }
}