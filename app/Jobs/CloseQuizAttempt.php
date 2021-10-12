<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Modules\QuestionBank\Entities\userQuizAnswer;
use Carbon\Carbon;
use Modules\QuestionBank\Entities\userQuiz;

class CloseQuizAttempt implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $userQuiz;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($userQuiz)
    {
        $this->userQuiz = $userQuiz;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $quiz_time=Carbon::parse($userQuiz->open_time)->addSeconds($userQuiz->quiz_lesson->quiz->duration)->format('Y-m-d H:i:s');
        if( $quiz_time < Carbon::now()->format('Y-m-d H:i:s'))
        {
            if($quiz_time > Carbon::parse($userQuiz->quiz_lesson->due_date)->format('Y-m-d H:i:s'))
                $quiz_time=$userQuiz->quiz_lesson->due_date;

            UserQuizAnswer::where('user_quiz_id',$userQuiz->id)->update(['force_submit'=>'1','answered' => 1]);
            userQuiz::find($userQuiz->id)->update(['submit_time'=>$quiz_time]);
        }
    }
}
