<?php
namespace App\GradesSetup;
use Illuminate\Http\Request;

class NaturalMethod implements GradeSetupInterface
{
    public function calculate($grade_category)
    {
        $total_category_mark = 0;
        foreach($grade_category->Child as $category){
            if($category->weight == 0)
                continue;
            $total_category_mark += $category->max;
        }
        foreach($grade_category->GradeItems as $items){
            if($items->weight == 0 || $items->grade_category_id != $grade_category->id)
                continue;
            $total_category_mark += $items->max;
        }        
        return $total_category_mark;
    }
}