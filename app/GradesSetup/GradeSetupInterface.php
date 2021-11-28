<?php

namespace App\GradesSetup;
use App\User;
use App\GradeCategory;
use Illuminate\Http\Request;

interface GradeSetupInterface
{
    public function calculate(GradeCategory $grade_category);
}
