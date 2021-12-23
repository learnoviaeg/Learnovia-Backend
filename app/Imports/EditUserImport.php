<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use App\User;
use Validator;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class EditUserImport implements ToModel, WithHeadingRow
{
    /**
    * @param Collection $collection
    */
    public function model(array $row)
    {
        Validator::make($row,[
            // 'id' => 'required|exists:users,id',
            'email' => 'unique:users,email,' . $row['username'],
            // 'class_id' => 'exists:classes,id',
            // 'level' => 'exists:levels,id',
            // 'type' => 'exists:academic_types,id',
            'username' => 'required'
        ])->validate();

        if(isset($row['language']))
            Validator::make($row,[
                'language' => 'exists:languages,id',
            ])->validate();

        if(isset($row['second language']))
            Validator::make($row,[
                'second language' => 'exists:languages,id',
            ])->validate();

        $optionals = ['arabicname', 'country', 'birthdate', 'gender', 'phone', 'address', 'nationality', 'notes', 'email',
                    'language', 'timezone', 'religion', 'second language', 'class_id', 'level', 'type', 'firstname',
                    'lastname', 'username', 'real_password', 'suspend'];

        $user=User::where('username',($row['username']))->first();
        
        $array=[];
        foreach(array_keys($row) as $key){
            if(strpos($key,"extra_") > -1)
                $array[$key]=$row[$key];
        }
        $user->profile_fields=json_encode($array);

        foreach ($optionals as $optional) {
            if (isset($row[$optional])){
                if($optional =='birthdate'){
                    $row[$optional] =  Date::excelToDateTimeObject($row['birthdate']);
                }
                if($optional =='real_password'){
                    $user->update([$optional => $row[$optional]]);
                    $user->password =   bcrypt($row[$optional]);
                }
                $user->update([$optional => $row[$optional]]);
            }
        }
    }
}
