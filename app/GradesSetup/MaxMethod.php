<?php
namespace App\GradesSetup;
use Illuminate\Http\Request;
use App\GradeCategory;
use App\UserGrader;
use Modules\Assigments\Entities\AssignmentLesson;
use Modules\QuestionBank\Entities\quiz;

class MaxMethod implements GradeSetupInterface
{
    public function calculateMark($grade_category)
    {
        $total_category_mark = 0;
        if($grade_category->categories_items()->where('weights' , 100)->where('weight_adjust', 1)->count() > 0)
            return $grade_category->categories_items()->where('weights' , 100)->where('weight_adjust', 1)->max('max');
        
        return $grade_category->categories_items()->max('max');

    }

    public function calculateWeight($grade_category)
    {
        $total_grade = $grade_category->max;
        $total_weight = 100;

        foreach($grade_category->categories_items as $cats)
        {
            if($cats->weights === 0.0)
                continue;
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
            if($cats->instance_type != null){

                if($cats->instance_type == 'Quiz'){
                    if($cats->weights > 0)
                        quiz::where('id', $cats->instance_id )->update(['is_graded' => 1]);
                    else
                        quiz::where('id', $cats->instance_id )->update(['is_graded' => 0]);
                }
                
                if($cats->instance_type == 'Assignment'){
                    if($cats->weights > 0)
                        AssignmentLesson::where('assignment_id', $cats->instance_id )->update(['is_graded' => 1]);
                    else
                        AssignmentLesson::where('assignment_id', $cats->instance_id )->update(['is_graded' => 0]);
                }  
            }
        }
    }

    public function calculateUserGrade($user, $grade_category)
    {
        $total_marks_in_categories = 0;
        foreach($grade_category->categories_items as $child){
            $user_mark = UserGrader::select('grade')->where('user_id', $user->id)->where('item_id',$child->id)->where('item_type','category')->first();
            if(!isset($user_mark)||$user_mark->grade === null || $child->max == 0)
                continue;

            if($user_mark->grade != null){
                //non-adjusted natural sums only the grades 
                if($grade_category->categories_items()->where('weight_adjust' ,0)->count() == $grade_category->categories_items()->count())
                    $total_marks_in_categories += $user_mark->grade ;
                 else 
                    $total_marks_in_categories += ($user_mark->grade / $child->max) * $child->weights;
            }
        }
        if($grade_category->categories_items()->where('weight_adjust' ,0)->count() == $grade_category->categories_items()->count())
                return $total_marks_in_categories;
      $grade = ($total_marks_in_categories) *($grade_category->max/ 100);
        return $grade;

    }    

    
    public function weightAdjustCheck($grade_category)
    {
        if($grade_category->categories_items()->count() == 0)
            return '';
        $adjusted_children = $grade_category->categories_items()->where('weight_adjust', 1)->count();
        $all_children = $grade_category->categories_items()->count();

        if(($adjusted_children == $all_children && $grade_category->categories_items()->sum('weights') != 100) || 
        $grade_category->categories_items()->where('weights' , 100)->where('weight_adjust', 1)->count() > 1 )
        
            $grade_category->categories_items()->where('instance_type', null)->update(['weight_adjust' => 0]);
    }
}
