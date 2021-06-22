<?php

namespace App\Grader;
use Illuminate\Http\Request;

class TypeGrader implements GraderInterface
{
    public function True_False($answer)
    {
        $grade['mark']=0;
        $grade['right']=0;
        if(isset($answer['student_answer']) && $answer['student_answer']->is_true == $answer['correct_answer']->is_true &&
            $answer['student_answer']->is_true != null){
            $grade['mark']=$answer['correct_answer']->mark;
            $grade['right']=1;
        }
        return $grade;
    }

    public function MCQ($answer)
    {
        $mark=0;
        $right=0;
        if(isset($answer['student_answer'])){
            switch($answer['correct_answer']->type){
                case 1 : //single
                    foreach($answer['correct_answer']->details as $detail){
                        if($detail->key == (int)$answer['student_answer'][0] && $detail->is_true==1){
                            $mark+=$detail->mark;
                            $right=1;
                        }
                    }
                    break;
                case 2 : // multi
                    foreach($answer['correct_answer']->details as $detail){
                        for($i=0;$i<=count($answer['student_answer']);$i++){
                            if($detail->key == $answer && $detail->is_true==1)
                                $mark+=$detail->mark;
                            
                            if($mark < $answer['correct_answer']->total_mark)
                                $mark=0;
                        }
                    }
                    break;
                case 3 : // part
                    foreach($answer['correct_answer']->details as $detail){
                        for($i=0;$i<count($answer['student_answer']);$i++){
                            if($detail->key == $answer['student_answer'][$i] && $detail->is_true==1)
                                $mark+=$detail->mark;
                        }
                    }
                    if($mark < $answer['correct_answer']->total_mark)
                        $right=2; // part_answer
                    if($mark == $answer['correct_answer']->total_mark)
                        $right=1;
                    break;
            }
        }
        $grade['mark']=$mark;
        $grade['right']=$right;
        return $grade;
    }

    public function Match()
    {
        dd('match');
    }
}
