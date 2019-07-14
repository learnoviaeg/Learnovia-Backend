<?php

namespace App\Imports;

use App\User;
use App\Enroll;
use Maatwebsite\Excel\Concerns\ToModel;
use App\Http\Controllers\HelperController;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class EnrollImport implements ToModel,WithHeadingRow
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */

    public function model(array $row)
    {

        $user_id = User::FindByName( $row['username'])->id;
        
        return new Enroll([
            'username' => $row['username'],
            'user_id'=>$user_id,
            'course_segment' => $row['course_segment'],
            'role_id'=>$row['role_id'],
            'start_date'=>Date::excelToDateTimeObject($row['start_date']),
            'end_date'=>Date::excelToDateTimeObject($row['end_date']),
        ]);


    }
}
