<?php

namespace App\Imports;

use App\Course;
use Maatwebsite\Excel\Concerns\ToModel;

class CourseImport implements ToModel
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        return new Course([
            'name'            => $row[0],
            'description'     => $row[1],
            'hide'            => $row[2],
            'start_date'      => $row[3],
            'end_date'        => $row[4],
        ]);
    }
}