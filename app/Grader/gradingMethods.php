<?php

namespace App\Grader;
use App\GradeCategory;
use App\GradeItems;
use App\UserGrader;
use Auth;
use Illuminate\Http\Request;

class gradingMethods 
{
    public function Highest($request)
    {
        $items = GradeItems::where('grade_category_id',$request['grade_cat']->id)->pluck('id');

            UserGrader::updateOrCreate(
                ['item_id'=>$request['grade_cat']->id, 'item_type' => 'category', 'user_id' => $request['user']],
                ['grade' =>  (UserGrader::where('user_id',$request['user'])->whereIn('item_id', $items)->max("grade"))]
            );
    }

    public function Lowest($request)
    {
        $items = GradeItems::where('grade_category_id',$request['grade_cat']->id)->pluck('id');
      
            UserGrader::updateOrCreate(
                ['item_id'=>$request['grade_cat']->id, 'item_type' => 'category', 'user_id' => $request['user']],
                ['grade' =>  (UserGrader::where('user_id',$request['user'])->whereIn('item_id', $items)->min("grade"))]
            );
    }

    public function First($request)
    {
        $items = GradeItems::where('grade_category_id',$request['grade_cat']->id)->pluck('id');

            UserGrader::updateOrCreate(
                ['item_id'=>$request['grade_cat']->id, 'item_type' => 'category', 'user_id' => $request['user']],
                ['grade' =>  (UserGrader::where('user_id',$request['user'])->whereIn('item_id', $items)->first()->grade)]
            );
    }

    public function Last($request)
    {
        $items = GradeItems::where('grade_category_id',$request['grade_cat']->id)->pluck('id');

            UserGrader::updateOrCreate(
                ['item_id'=>$request['grade_cat']->id, 'item_type' => 'category', 'user_id' => $request['user']],
                ['grade' =>  (UserGrader::where('user_id',$request['user'])->whereIn('item_id', $items)->orderBy("id",'desc')->first())->grade]
            );
    }

    public function Average($request)
    {
        $grades = [];
        $items = GradeItems::where('grade_category_id',$request['grade_cat']->id)->pluck('id');
        foreach(UserGrader::where('user_id',$request['user'])->whereIn('item_id', $items)->cursor() as $grade)
            array_push($grades , $grade->grade);
            
            UserGrader::updateOrCreate(
                ['item_id'=>$request['grade_cat']->id, 'item_type' => 'category', 'user_id' => $request['user']],
                ['grade' =>  (array_sum($grades)/ count($grades))]
            );
    }
}