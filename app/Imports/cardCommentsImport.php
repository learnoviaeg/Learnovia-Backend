<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Validator;
use App\User;
use App\CardComment;
use Spatie\Permission\Models\Permission;

class cardCommentsImport implements ToModel , WithHeadingRow
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        $messages = [
            'exists' => $row['username'],
        ];
        Validator::make($row,[
            'username' => 'required|exists:users,username',
        ],$messages)->validate();

        CardComment::firstOrCreate([
            'permission_id' =>  Permission::where('name',$row['permission_name'])->first()->id,
            'user_id' => User::find($row['username'])->id,
            'comment' => $row['comment'],
        ]);
    }
}