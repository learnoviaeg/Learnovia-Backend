<?php
namespace App\GradesSetup;
use Illuminate\Http\Request;
use App\GradeCategory;
use App\userGrader;

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
        $total_user_mark = 0;
        $max_total = 0;
        foreach($grade_category->categories_items as $child){
            $user_mark = userGrader::select('grade')->where('user_id', $user->id)->where('item_id',$child->id)->where('item_type','category')->first();
            if(!isset($user_mark)||$user_mark->grade == null)
                continue;
            $total_user_mark += $user_mark->grade;
            $max_total += $child->max;
        }
        if($max_total == 0)
            return 0;
        $grade = ($total_user_mark/$max_total) *($grade_category->max);
        return $grade;
    }

    public function weightAdjustCheck($grade_category)
    {
        $adjusted_children = $grade_category->categories_items()->where('weight_adjust', 1)->count();
        $all_children = $grade_category->categories_items()->count();
        if($adjusted_children == $all_children)
            $grade_category->categories_items()->update(['weight_adjust' , 0]);
    }
}
