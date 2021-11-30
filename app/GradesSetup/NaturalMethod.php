<?php
namespace App\GradesSetup;
use Illuminate\Http\Request;
use App\GradeCategory;

class NaturalMethod implements GradeSetupInterface
{
    public function calculateMark($grade_category)
    {
        $total_category_mark = 0;
        foreach($grade_category->Child as $category){
            if($category->weights === 0 && $category->weight_adjust === 1 )
                continue;
            $total_category_mark += $category->max;
        }
        foreach($grade_category->GradeItems as $items){
            if(($items->weights === 0 && $category->weight_adjust === 1 ) || $items->grade_category_id != $grade_category->id)
                continue;
            $total_category_mark += $items->max;
        }        
        return $total_category_mark;
    }

    public function calculateWeight($grade_category)
    {
        $total_grade = 0;
        $total_weight = 100;
        $category = GradeCategory::find($grade_category->id);

        foreach($category->child as $cats)
        {
            if($cats->weight_adjust	 === 1){
                $total_weight -= $cats->weights;
            }
            if($cats->weights > 0 || $cats->weights == null){
                $total_grade += $cats->max;
            }
        }
        foreach($category->GradeItems as $item)
        {

            if($item->weight_adjust	 === 1){
                $total_weight -= $item->weights;
            }
            if($item->weights > 0 || $item->weights == null){
                $total_grade += $item->max;
            }

        }

        foreach($category->child as $cats)
        {
            if($cats->weight_adjust	 != 1){
                $cats->weights = ($cats->max / $total_grade) *$total_weight;
                $cats->save();
            }
        }

        foreach($category->GradeItems as $item)
        {
            if($item->weight_adjust	 != 1){
                $item->weights = ($item->max / $total_grade) *$total_weight;
                $item->save();
            }
        }

    
    }

}