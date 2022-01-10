<?php

namespace App\Imports;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use App\GradeCategory;
use App\User;
use Validator;

class GradesImport implements  ToModel, WithHeadingRow
{
    /**
     * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        Validator::make($row,[
            'item_name' => 'required|exists:grade_categories,name',
            'username' => 'required|exists:users,username',
            'grade' => 'required',
            'course' => 'required|exists:courses,id',
        ])->validate();

        $instance = GradeCategory::select('id')->where('course_id',$row['course'])->where('name', $row['item_name'])->first();
        $user = User::select('id')->where('username' , $row['username'])->first();
        $array[0]['item_id'] = $instance->id;
        $array[0]['grade'] = $row['grade'];
        $array[0]['user_id'] = $user->id;

        $request = new Request([
            'user' => $array,
        ]);
        app('App\Http\Controllers\UserGradeController')->store($request);
    }
}
