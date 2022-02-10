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
            $item_id = substr($key,strrpos($key,"_")+1);
            $user = User::select('id')->where('username' , $row['username'])->first();

            if(is_string($item)){
                if(GradeCategory::find($item_id)->scale_id == null)
                    dd('Cannot add scale for this item');

                GradeCategory::find($item_id)->scale->details()->where('name', $item);
                    $req[0]['scale_id'] = GradeCategory::find($item_id)->scale->details()->where('evaluation', $item)->first()->id;
            }
            else{
                if(GradeCategory::find($item_id)->scale_id != null)
                    dd('Cannot add grade for this item');
                $req[0]['grade'] = $item;
            }

            $req[0]['item_id'] = $item_id;
            $req[0]['user_id'] = $user->id;
            $request = new Request([
                'user' => $req,
            ]);
            app('App\Http\Controllers\UserGradeController')->store($request);
        }
        

    }
}
