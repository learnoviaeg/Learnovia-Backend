<?php

namespace App\Imports;

use App\User;
use App\Enroll;
use Maatwebsite\Excel\Concerns\ToModel;
use App\Http\Controllers\HelperController;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\WithValidation;
use Carbon\Carbon;

class EnrollImport implements ToModel,WithHeadingRow
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */

    public function model(array $row)
    {
        Validator::make($row,[
            'username'=>'required|exists:users,username',
            'year'=>'exists:academic_years,id',
            'level'=>'required|exists:levels,id',
            'type'=>'required|exists:academic_types,id',
            'segment'=>'exists:segments,id',
            'class'=>'required|exists:classes,id',
            'course'=>'required|exists:course_segments,course_id',
            'role_id'=>'required|exists:roles,id',


        ])->validate();
        $time=['start_date'=>Date::excelToDateTimeObject($row['start_date']),'end_date' =>Date::excelToDateTimeObject($row['end_date'])];
        Validator::make($time,[
            'start_date'=> 'required|before:end_date|after:' . Carbon::now(),
            'end_date' => 'required|after:' . Carbon::now()

        ])->validate();
        $user_id = User::FindByName( $row['username'])->id;

        $request = new Request([
            'year' => $row['year'],
            'type' => $row['type'],
            'level' => $row['level'],
            'class' => $row['class'],
            'segment' => $row['segment'],
            'course' => $row['course']
        ]);

        $courseSegment = HelperController::Get_Course_segment_By_Course($request);

        return new Enroll([
            'username' => $row['username'],
            'user_id'=>$user_id,
            'course_segment' => $courseSegment[0],
            'role_id'=>$row['role_id'],
            'start_date'=>Date::excelToDateTimeObject($row['start_date']),
            'end_date'=>Date::excelToDateTimeObject($row['end_date']),
        ]);
    }
}
