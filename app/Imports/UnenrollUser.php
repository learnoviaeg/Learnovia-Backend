<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Validator;
use App\User;
use Illuminate\Http\Request;

class UnenrollUser implements ToModel,WithHeadingRow
{
    /**
    * @param Collection $collection
    */
    public function model(array $row)
    {
        $messages = [
            'exists' => 'user with username '.$row['username'].' not found'
        ];
        $validator = Validator::make($row,[
            'course' => 'exists:courses,id',
            'username' => 'required|exists:users,username',
        ],$messages)->validate();
        
        $user = User::where('username',$row['username'])->first();
        
        $user->enroll()->where('course', $row['course'])->delete();
    }
}
