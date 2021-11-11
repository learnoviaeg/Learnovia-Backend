<?php

namespace App\Listeners;

use App\Events\ManualCorrectionEvent;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\GradeCategory;
use App\GradeItems;
use App\UserGrader;
use App\User;
use Modules\QuestionBank\Entities\Questions;
use Modules\QuestionBank\Entities\UserQuizAnswer;
use Modules\QuestionBank\Entities\UserQuiz;
use App\Events\RefreshGradeTreeEvent;

class GradeManualListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  ManualCorrectionEvent  $event
     * @return void
     */
    public function handle(ManualCorrectionEvent $event)
    {
        $attem=$event->attempt;
        $essayQues = Questions::whereIn('id',$attem->quiz_lesson->quiz->Question->pluck('id'))->where('question_type_id',4)->pluck('id');
        $t_f_Quest = Questions::whereIn('id',$attem->quiz_lesson->quiz->Question->pluck('id'))->where('question_type_id',1)->pluck('id');
        $all_quest_without = Questions::whereIn('id',$attem->quiz_lesson->quiz->Question->pluck('id'))->whereIn('question_type_id',[1,2,3])->pluck('id');
        $gradeNotWeight=0;
        $gradeAuto=0;
        $allcorrection=UserQuizAnswer::where('user_quiz_id',$attem->id)->whereIn('question_id',$all_quest_without)->pluck('correction');
        foreach($allcorrection as $oneAuto)
            $gradeAuto+=$oneAuto->mark;

        // $autocorrect=UserQuiz::find($attem->id);
        // $gradeAuto=$autocorrect->grade;
        // dd($gradeAuto);

        //7esab daragat el true_false questions
        $userEssayCheckAnswerTF=UserQuizAnswer::where('user_quiz_id',$attem->id)->where('answered',1)->where('force_submit',1)
                                ->whereIn('question_id',$t_f_Quest)->get();
        if(count($userEssayCheckAnswerTF) > 0)
        {
            foreach($userEssayCheckAnswerTF as $TF){
                if($TF->correction->and_why == true){
                    if(isset($TF->correction->grade)){
                        $gradeNotWeight+= $TF->correction->and_why_mark;
                        if(($TF->correction->and_why_right == 1 && $TF->correction->mark < 1) ||
                            $TF->correction->and_why_right == 0 && $TF->correction->mark >= 1){
                            $tes=$TF->correction;
                            $tes->right=2;
                            // $tes->user_quest_grade=$TF->correction->and_why_mark + $TF->correction->mark; // daraget el taleb fel so2al koloh
                            $tes->user_quest_grade=$TF->correction->grade; // daraget el taleb fel so2al koloh
                            $TF->update(['correction'=>json_encode($tes)]); //because it doesn't read update
                        }
                    }
                }
            }
        }

        //7esab daragat el essay questions
        $userEssayCheckAnswerE=UserQuizAnswer::where('user_quiz_id',$attem->id)->where('answered',1)->where('force_submit',1)->whereIn('question_id',$essayQues)->get();
        if(count($userEssayCheckAnswerE) > 0)
        {
            foreach($userEssayCheckAnswerE as $esay){
                if(isset($esay->correction)){
                    $gradeNotWeight+= $esay->correction->grade;
                }
            }
        }
        $grade_cat=GradeCategory::where('instance_type','Quiz')->where('instance_id',$attem->quiz_lesson->quiz_id)
                                    ->where('lesson_id',$attem->quiz_lesson->lesson_id)->first();
        $gradeitem=GradeItems::where('index',$attem->attempt_index)->where('grade_category_id',$grade_cat->id)->first();
        UserGrader::where('user_id',$attem->user_id)->where('item_id',$gradeitem->id)->where('item_type','item')->update(['grade' => $gradeNotWeight+$gradeAuto]);
        UserQuiz::whereId($attem->id)->update(['grade' => $gradeNotWeight+$gradeAuto]);
        event(new RefreshGradeTreeEvent(User::find($attem->user_id) ,$grade_cat));
    }
}
