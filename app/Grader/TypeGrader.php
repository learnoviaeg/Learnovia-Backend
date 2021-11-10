<?php

namespace App\Grader;
use Illuminate\Http\Request;

class TypeGrader implements GraderInterface
{
    public function True_False($answer)
    {
        $mark=0;
        $right=0;
        $grade=$answer['correct_answer'];
        if(isset($answer['student_answer']) && $answer['student_answer']->is_true == $answer['correct_answer']->is_true){
            $mark=$answer['correct_answer']->mark;
            $right=1;
        }
        $grade->and_why_mark=null;
        $grade->mark=$mark;
        $grade->right=$right;
        return $grade;
    }

    public function MCQ($answer)
    {
        $mark=0;
        $right=0;
        $grade=$answer['correct_answer'];
        // if(isset($answer['student_answer'])){
            switch($answer['correct_answer']->type){
                case 1 : //single
                    foreach($answer['correct_answer']->details as $detail){
                        $detail->stu_ans=0;
                        $detail->right=0;
                        if(isset($answer['student_answer'])){
                            if($detail->key == (int)$answer['student_answer'][0] && $detail->is_true==1){
                                $mark+=$detail->mark;
                                $right=1;
                                $detail->right=$right;
                            }
                            if($detail->key == (int)$answer['student_answer'][0])
                                $detail->stu_ans=1;
                        }
                    }
                    break;

                case 2 : // multi
                    foreach($answer['correct_answer']->details as $detail){
                        $detail->stu_ans=0;
                        $detail->right=0;
                        if(isset($answer['student_answer'])){
                            for($i=0;$i<count($answer['student_answer']);$i++){
                                if($detail->key == $answer['student_answer'][$i]){
                                    if($detail->is_true == 1){
                                        $mark+=$detail->mark;
                                        $detail->right=1;
                                    }
                                    $detail->stu_ans=1;
                                }
                            }
                        }
                    }
                    if($mark < $answer['correct_answer']->total_mark)
                        $mark=0;
                    if($mark == $answer['correct_answer']->total_mark)
                        $right=1;
                    break;

                case 3 : // part
                    foreach($answer['correct_answer']->details as $detail){
                        $detail->stu_ans=0;
                        $detail->right=0;
                        if(isset($answer['student_answer'])){
                            for($i=0;$i<count($answer['student_answer']);$i++){
                                if($detail->key == $answer['student_answer'][$i]){
                                    if($detail->is_true == 1){
                                        $mark+=$detail->mark;
                                        $detail->right=1;
                                    }
                                    $detail->stu_ans=1;
                                }
                            }
                        }
                    }
                    if($mark < $answer['correct_answer']->total_mark)
                        $right=2;
                    if($mark == $answer['correct_answer']->total_mark)
                        $right=1;
                    break;
            }
        // }
        $grade->mark=$mark;
        $grade->right=$right;
        return $grade;
    }

    public function Match($answer)
    {   
        //obj of answer of Match
        /**
         * array:[  
            * "student_answer"[
                * {"1": "1"}
                * {"3": "3"}
            * ]
            * 
            * "correct_answer"{
                * "match_a"[
                    * {"1": "mariam2"}
                    * {"2": "ismail2"}
                    * {"3": "roshdy3"}
                * ]
                * "match_b"[
                    * {"1": "mariam2"}
                    * {"2": "ismail2"}
                * ]
                * "mark"[
                    * {"1": "3"}
                    * {"2": "2"}
                * ]
                * "total_mark: 5
            * }
         * ]
         * 
         */
        $right=0;
        $mark=0;
        $grade=$answer['correct_answer'];
        if(isset(($answer['student_answer']))){
            foreach($answer['student_answer'] as $kk=>$ans){
                $ans->right=0;
                $ans->grade=0;
                foreach($ans as $key=>$aa){
                    if(is_numeric($key)){ // because key may be int|string cause object {1:2, right:1, feedback:"text", grade:19}
                        if(isset($answer['correct_answer']->mark[$key-1]))
                            foreach($answer['correct_answer']->mark[$key-1] as $mrk){
                                if($key === $aa){
                                    $ans->right=1;
                                    $ans->grade=$mrk;
                                    $mark+=$mrk;
                                    break;
                                }
                            }
                    }
                }
            }
        }
        else{
            foreach($answer['student_answer'] as $kk=>$ans)
                $ans->right=0;
        }

        if($mark > 0 && $mark < $answer['correct_answer']->total_mark)
            $right=2;
        if($mark == $answer['correct_answer']->total_mark)
            $right=1;

        $grade->mark=$mark;
        $grade->right=$right;
        $grade->stu_ans=$answer['student_answer'];
        return $grade;
    }
}
