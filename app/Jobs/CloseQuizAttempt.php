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
        userQuizAnswer::where('user_quiz_id',$this->userQuiz['id'])->where('force_submit', null)->update([
            'answered' => 1,
            'force_submit' => 1,
        ]);
        
        userQuiz::whereId($this->userQuiz['id'])->where('submit_time',null)->update([
            'submit_time' => Carbon::now()->format('Y-m-d H:i:s'),
        ]);
        
    }
}
