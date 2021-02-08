<?php

namespace App\Imports;

use App\User;
use App\Enroll;
use Maatwebsite\Excel\Concerns\ToModel;
use App\Http\Controllers\HelperController;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class EnrollImport implements ToModel,WithHeadingRow
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    //this file became unused ^_^
    public function model(array $row)
    {
        $user_id = User::FindByName( $row['username'])->id;
        $request = new Request([
            'year' => $row['year'],
            'type' => $row['type'],
            'level' => $row['level'],
            'class' => $row['class'],
            'segment' => $row['segment'],
            'course' => $row['course']
        ]);

        $courseSegment = GradeCategoryController::getCourseSegment($request);

        return new Enroll([
            'user_id'=>$user_id,
            'course_segment' => $courseSegment[0],
            'role_id'=>$row['role_id'],
            'year' => $row['year'],
            'type' => $row['type'],
            'level' => $row['level'],
            'class' => $row['class'],
            'segment' => $row['segment'],
            'course' => $row['course']
        ]);
    }
}
