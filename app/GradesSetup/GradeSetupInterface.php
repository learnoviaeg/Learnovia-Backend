<?php

namespace App\GradesSetup;
use App\User;
use App\GradeCategory;
use Illuminate\Http\Request;

interface GradeSetupInterface
{
    public function calculateMark(GradeCategory $grade_category);
    public function calculateWeight(GradeCategory $grade_category);
}
