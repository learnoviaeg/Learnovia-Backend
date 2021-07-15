<?php
namespace App\Grader;
use App\GradeCategory;
use App\GradeItems;
use App\UserGrader;
use Auth;
use Illuminate\Http\Request;

class LowestGrade implements gradingMethodsInterface
{
    public function calculate($user ,  $grade_category)
    {
        $items = GradeItems::where('grade_category_id',$grade_category->id)->pluck('id');
        $grade = UserGrader::where('user_id',$user->id)->whereIn('item_id', $items)->min("grade");
        return $grade;
    }
}