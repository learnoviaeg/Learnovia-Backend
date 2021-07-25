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
        $grades = [];
        $items = GradeItems::where('grade_category_id',$grade_category->id)->pluck('id');
        foreach(UserGrader::where('user_id',$user->id)->whereIn('item_id', $items)->cursor() as $grade)
            array_push($grades , $grade->grade);

        return (array_sum($grades)/ count($grades));
    }
}