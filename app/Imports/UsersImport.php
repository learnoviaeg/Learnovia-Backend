<?php

namespace App\Imports;

use App\User;
use Maatwebsite\Excel\Concerns\ToModel;
use Spatie\Permission\Models\Role;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class UsersImport implements ToModel, WithHeadingRow
{
    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        $optionals = ['arabicname', 'country', 'birthdate', 'gender', 'phone', 'address', 'nationality', 'notes', 'email'];
        $password = mt_rand(100000, 999999);
        $user = new User([
            'firstname' => $row['firstname'],
            'lastname' => $row['lastname'],
            'username' => User::generateUsername(),
            'password' => bcrypt($password),
            'real_password' => $password
        ]);
        foreach ($optionals as $optional) {
            if (isset($row[$optional]))
                $user->$optional = $row[$optional];
        }
        $user->save();
        $role = Role::find($row['role']);
        $user->assignRole($role->name);
        if ($row['role'] == 2) {

        } else {

        }
        return $user;
    }
}
