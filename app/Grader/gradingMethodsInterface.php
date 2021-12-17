<?php

namespace App\Grader;
use App\User;
use App\GradeCategory;
use Illuminate\Http\Request;

interface gradingMethodsInterface
{
    public function calculate(User $user, GradeCategory $grade_category);
}
