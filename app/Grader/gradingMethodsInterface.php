<?php

namespace App\Grader;
use App\User;
use App\GradeCategory;
use Illuminate\Http\Request;

interface gradingMethodsInterface
{
    // public function Highest($request);
    // public function Lowest($request);
    // public function First($request);
    // public function Last($request);
    // public function Average($request);
    public function calculate(User $user, GradeCategory $grade_category);

}
