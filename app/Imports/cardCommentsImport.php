<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Validator;
use App\User;
use App\cardComment;


class cardCommentsImport implements ToModel , WithHeadingRow
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        $validator = Validator::make($row,[
            'username' => 'required|exists:users,username',
        ],$messages)->validate();

        cardComment::firstOrCreate([
            'permission_id' => $row['name'],
            'user_id' => User::find($row['username'])->id,
            'comment' => $row['comment'],
        ]);
    }
}