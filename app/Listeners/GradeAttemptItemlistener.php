<?php

namespace App\Listeners;

use App\Events\GradeAttemptEvent;
use App\Events\RefreshGradeTreeEvent;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\GradeItems;
use App\Grader\TypeGrader;
use App\UserGrader;
use App\GradeCategory;
use Auth;
use App\User;
use App\ItemDetail;
use App\ItemDetailsUser;
use Modules\QuestionBank\Entities\Quiz;
use Modules\QuestionBank\Entities\Questions;
use Modules\QuestionBank\Entities\UserQuiz;
use Modules\QuestionBank\Entities\UserQuizAnswer;


class GradeAttemptItemlistener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(TypeGrader $typeGrader) //attempt
    {
        $this->gradeinterface=$typeGrader; // for calculation
    }

    public function handle(GradeAttemptEvent $event){        
        $user_quiz_answers=UserQuizAnswer::where('user_quiz_id',$event->attempt->id)->get();

        $grade_cat=GradeCategory::where('instance_type','Quiz')->where('instance_id',$event->attempt->quiz_lesson->quiz_id)->where('lesson_id',$event->attempt->quiz_lesson->lesson_id)->first();
        $gradeitem=GradeItems::where('index',$event->attempt->attempt_index)->where('grade_category_id',$grade_cat->id)->first();
        if(isset($gradeitem)){ // this check for roles(ex. teacher, admin, ...) that open more attempts more than max_attemp
            $item_details=ItemDetail::where('parent_item_id',$gradeitem->id)->get()->unique('item_id');

            $total_grade_attempt=0;

            foreach($item_details as $item_detail)
            {   
                foreach($user_quiz_answers as $stud_quest_ans)
                {
                    $grade = null ;
                    if($item_detail->item_id == $stud_quest_ans->question_id){
                        $correction_answer['student_answer']=$stud_quest_ans->user_answers;
                        $correction_answer['correct_answer']=$item_detail->weight_details;

                        // if($item_detail->weight_details->exclude_mark){
                        //     $stud_quest_ans->update(['correction'=>json_encode($correction_answer['correct_answer'])]);
                        //     continue;
                        // }

                        $question_type=Questions::whereId($item_detail['item_id'])->pluck('question_type_id')->first();
            
                        if($question_type == 1){
                            $grade=$this->gradeinterface->True_False($correction_answer);
                            $gradeOld= $stud_quest_ans->correction; //old
                            if(isset($gradeOld->and_why_right)){
                                $grade->and_why_right = $gradeOld->and_why_right;
                                $grade->grade = $gradeOld->grade;
                                $grade->feedback = $gradeOld->feedback;
                                $grade->and_why_mark = $gradeOld->and_why_mark;
                                $grade->mark=$grade->grade;
                            }
                        }


                        if($question_type == 2)
                            $grade=$this->gradeinterface->MCQ($correction_answer);

                        if($question_type == 3)
                            $grade=$this->gradeinterface->Match($correction_answer);

                        if($question_type == 4 && $stud_quest_ans->correction != null){
                            $grade= $stud_quest_ans->correction;
                            $grade->mark=$grade->grade;
                        }
                    
                        ItemDetailsUser::firstOrCreate([
                            'user_id' => $event->attempt->user_id,
                            'item_details_id' => $item_detail->id,
                            'grade' => ($grade) ? $grade->mark:null,
                            'Answers_Correction' => json_encode($correction_answer)
                        ]);

                        $stud_quest_ans->update(['correction'=>json_encode($grade)]);
                        if(isset($grade)){
                            if(!$item_detail->weight_details->exclude_mark)
                                $total_grade_attempt+=$grade->mark;
                        }
                    }
                }
            }
            //Scale grade of user attempt to actual total mark of quiz
            if($event->attempt->quiz_lesson->questions_mark != 0){
                $actual_mark = ($total_grade_attempt * $event->attempt->quiz_lesson->grade) / $event->attempt->quiz_lesson->questions_mark;
            }
            if($event->attempt->quiz_lesson->questions_mark == 0 ){
                $actual_mark=0;
            }
            UserGrader::updateOrCreate(
                ['item_id'=>$gradeitem->id, 'item_type' => 'item', 'user_id' => $event->attempt->user_id],
                ['grade' =>  $actual_mark]
            );
            
            UserQuiz::whereId($event->attempt->id)->update([
                'grade'=> $actual_mark
            ]);
            event(new RefreshGradeTreeEvent(User::find($event->attempt->user_id) ,$grade_cat));
        }

    }
}
