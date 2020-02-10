<?php

namespace App\Imports;

use Validator;
use App\User;
use App\Parents;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

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
            'fullname'=>'required|exists:users,lastname'
        ])->validate();

        $password = mt_rand(100000, 999999);

        // $user =new User();
        // $user->firstname = $row['firstname'];
        // $user->lastname = $row['lastname'];
        // $user->username = User::generateUsername();
        // $user->password = bcrypt($password);
        // $user->real_password = $password;
        // $user->save();

        $user =new User([
            'firstname' => $row['firstname'],
            'lastname' => $row['lastname'],
            'username' => User::generateUsername(),
            'password' => bcrypt($password),
            'real_password' => $password
        ]);
        $user->save();

        // dd($user);
        $childs=User::where('lastname',$row['fullname'])->pluck('id');

        foreach($childs as $child)
        {
            $parent = new Parents([
                'parent_id' => $user->id,
                'child_id' => $child,
            ]);
            $parent->save();
        }
    }
}
