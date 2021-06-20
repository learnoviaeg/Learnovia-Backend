<?php

namespace App\Grader;
use Illuminate\Http\Request;

class TypeGrader implements GraderInterface
{
    public function True_False($answer)
    {
        if($answer['student_answer']->is_true == $answer['correct_answer']->is_true){
            $mark=$answer['correct_answer']->mark;
            return $mark;
        }
        return 0;
    }

    public function MCQ($answer)
    {
        dd('mcq');
    }

    public function Match()
    {
        dd('match');
    }
}
