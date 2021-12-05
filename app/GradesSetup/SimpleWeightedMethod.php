<?php
namespace App\GradesSetup;
use Illuminate\Http\Request;
use App\GradeCategory;

class SimpleWeightedMethod implements GradeSetupInterface
{
    public function calculateMark($grade_category)
    {
        return $grade_category->max;
    }

    public function calculateWeight($grade_category)
    {
        $total_grade = $grade_category->max;
        $total_weight = 100;
        foreach($grade_category->categories_items as $cats)
        {
            if($cats->weight_adjust	 === 1){
                $total_weight -= $cats->weights;
                $total_grade -= $cats->max;
            }
        }
        foreach($grade_category->categories_items as $cats)
        {
            if($cats->weight_adjust	 != 1){
                if($total_grade == 0)
                    $cats->weights =0;
                else
                    $cats->weights = ($cats->max / $total_grade) *$total_weight;
                $cats->save();
            }
        }

    }

    public function calculateUserGrade($user, $grade_category)
    {
        return '';
    }

}
