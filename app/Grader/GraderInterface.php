<?php

namespace App\Grader;
use Illuminate\Http\Request;

interface GraderInterface
{
  public function grade();
}

class True_False implements GraderInterface
{
    public function grade()
    {
        dd('true/false');
    }
}

class MCQ implements GraderInterface
{
    public function grade()
    {
        dd('mcq');
    }
}