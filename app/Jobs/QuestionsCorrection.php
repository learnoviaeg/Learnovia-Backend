<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class QuestionsCorrection implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $UserQuizAnswers;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($UserQuizAnswers)
    {
        $this->UserQuizAnswers= $UserQuizAnswers;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        foreach($UserQuizAnswers as $oneAnswer)
        {
            $question=Question::find($oneAnswer->question_id);
            switch ($question->question_type_id) {
                // case 1: // True_false
                //     # code...
                //     $t_f['is_true'] = isset($question['is_true']) ? $question['is_true']: null;
                //     $data['right'] = json_encode($t_f);
                //     break;
    
                case 2: // MCQ
                    $data['user_answers'] = isset($question['MCQ_Choices']) ? json_encode($question['MCQ_Choices']) : null;
                    break;
    
                case 3: // Match
                    $match['match_a']=isset($question['match_a']) ? $question['match_a'] : null;
                    $match['match_b']=isset($question['match_b']) ? $question['match_b'] : null;
                    $data['user_answers'] = json_encode($match);
                    break;
            }
        }
    }
}
