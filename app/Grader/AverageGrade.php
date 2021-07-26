<?php
namespace App\Grader;
use App\GradeCategory;
use App\GradeItems;
use App\UserGrader;
use Auth;
use Illuminate\Http\Request;

class AverageGrade implements gradingMethodsInterface
{
    public function calculate($user ,  $grade_category)
    {
        $items = GradeItems::where('grade_category_id',$grade_category->id)->pluck('id');
        $categories = GradeCategory::where('parent',$grade_category->id)->pluck('id');
        $items_grades = UserGrader::where('user_id',$user->id)->whereIn('item_id', $categories)->where('item_type' , 'Category')->pluck('grade');
        $cats_grades =   UserGrader::where('user_id',$user->id)->whereIn('item_id', $items)->where('item_type' , 'Item')->pluck('grade');

        $grades = array_merge($items_grades->toArray() , $cats_grades->toArray());
        return (array_sum($grades)/ count($grades));
    }
}