<?php

namespace App\Grader;
use Illuminate\Http\Request;

interface GraderInterface
{
    public function True_False($answer);
    public function MCQ($answer);
    // public function Match();
}

// class True_False implements GraderInterface
// {
//     public function grade()
//     {
//         dd('true/false');
//     }
// }

// class MCQ implements GraderInterface
// {
//     public function grade()
//     {
//         dd('mcq');
//     }
// }