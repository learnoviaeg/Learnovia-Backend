<?php

namespace App\Listeners;

use App\Events\GradeAttemptEvent;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\GradeItems;
use App\GradeCategory;
use Auth;
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
    public function __construct() //attempt
    {
        // $this->item=$item; //attempt
        // $this->gradeinterface=$gradeinterface; // for calculation
    }

    public function handle(GradeAttemptEvent $event){
        
        $user_quiz_answers=UserQuizAnswer::where('user_quiz_id',$event->item->id)->get();
        // dd($user_quiz_answers);

        $grade_cat=GradeCategory::where('instance_type','Quiz')->where('instance_id',$event->item->quiz_lesson->quiz_id)->where('lesson_id',$event->item->quiz_lesson->lesson_id)->first();
        //grade item ( attempt_item )
        $gradeitem=GradeItems::where('index',$event->item->attempt_index)->where('grade_category_id',$grade_cat->id)->first();
        $item_details=ItemDetail::where('parent_item_id',$gradeitem->id)->get();
        $total_grade_attempt=0;
        foreach($item_details as $item_detail)
        {
            foreach($user_quiz_answers as $stud_quest_ans)
            {
                $grade = null ;
                if($item_detail->item_id == $stud_quest_ans->question_id){
                    $correction_answer['student_answer']=$stud_quest_ans->user_answers;
                    $correction_answer['correct_answer']=$item_detail->weight_details;

                    $Q=Questions::find($item_detail['item_id']);
                    $question_type=$Q->question_type->name;

                    //for grade all types of attempt

                    // $files = scandir(__DIR__ . '/../Grader/Types/');
                    // // dd($files);
                    // $name = $question_type . '.php';
            
                    // if (in_array($name, $files)) {
                    //     eval('$classes = new App\Grader\Types\\' . $question_type . '();');
                    //     // $check = Excel::import($classes, request()->file('file'));
                    //     return HelperController::api_response_format(201 , [] , 'done');
                    // } else {
                    //     return HelperController::api_response_format(201 , [] , 'can\'t grade this type of question');
                    // }
        
                    if($question_type == 1)
                        $grade=$event->gradeinterface->True_False($correction_answer);

                    if($question_type == 2)
                        $grade=$event->gradeinterface->MCQ($correction_answer);

                    if($question_type == 3)
                        $grade=$event->gradeinterface->Match($correction_answer);

                    ItemDetailsUser::firstOrCreate([
                        'user_id' => Auth::id(),
                        'item_details_id' => $item_detail->id,
                        'grade' => isset($grade) ? $grade->mark:null,
                        'Answers_Correction' => json_encode($correction_answer)
                    ]);

                    $stud_quest_ans->update(['correction'=>json_encode($grade)]);
                    if(isset($grade))
                        $total_grade_attempt=$grade->mark;
                }
            }
        }
        return $total_grade_attempt;
    }
}
