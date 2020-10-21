<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use App\User;
use App\Parents;
use Validator;

class DeleteUsersImport implements ToModel, WithHeadingRow
{
    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        Validator::make($row,[
            'username_p' => 'exists:users,username'
        ])->validate();

        if(isset($row['username_p']))
            User::where('username',$row['username_p'])->delete();
    }
}
