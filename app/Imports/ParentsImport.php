<?php

namespace App\Imports;

use Validator;
use App\User;
use App\Parents;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Spatie\Permission\Models\Role;

class ParentsImport implements ToModel, WithHeadingRow
{
    /**
    * @param array $row
    */
    public function model(array $row)
    {
        Validator::make($row,[
            'firstname'=>'required',
            'lastname'=>'required',
            'fullname'=>'required|exists:users,lastname', //it's not just father >> his/her mother or another member of his/her family
            'role_id' => 'required|exists:roles,id'
        ])->validate();

        $password = mt_rand(100000, 999999);

        $user =User::firstOrCreate([
            'firstname' => $row['firstname'],
            'lastname' => $row['lastname'],
            'username' => User::generateUsername(),
            'password' => bcrypt($password),
            'real_password' => $password
        ]);

        $role = Role::find($row['role_id']);
        $user->assignRole($role);

        $childs=User::where('lastname',$row['fullname'])->pluck('id');

        foreach($childs as $child)
        {
            $parent = Parents::firstOrCreate([
                'parent_id' => $user->id,
                'child_id' => $child,
            ]);
        }
    }
}
