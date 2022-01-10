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
use Maatwebsite\Excel\Imports\HeadingRowFormatter;
HeadingRowFormatter::default('none');


class GradesImport implements  ToModel, WithHeadingRow
{
    /**
     * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        Validator::make($row,[
            'username' => 'required|exists:users,username',
            'course' => 'required|exists:courses,id',
        ])->validate();

        $array=[];
        foreach(array_keys($row) as $key){
            if(strpos($key,"item_") > -1)
                $array[$key]=$row[$key];
        }
        foreach($array as $key => $item){

            $instance = GradeCategory::select('id')->where('course_id',$row['course'])->where('name', (str_replace('item_', '', $key)) )->first();
            $user = User::select('id')->where('username' , $row['username'])->first();
            $req[0]['item_id'] = $instance->id;
            $req[0]['grade'] = $item;
            $req[0]['user_id'] = $user->id;
            $request = new Request([
                'user' => $req,
            ]);
            app('App\Http\Controllers\UserGradeController')->store($request);
        }
        

    }
}
