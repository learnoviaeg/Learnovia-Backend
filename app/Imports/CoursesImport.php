<?php

namespace App\Imports;

use App\Course;
use App\AcademicYearType;
use App\ClassLevel;
use App\CourseSegment;
use App\Http\Controllers\CourseController;
use App\SegmentClass;
use Illuminate\Http\Request;
use App\YearLevel;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;


class CoursesImport implements ToModel , WithHeadingRow 
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {

        $request = new Request([
            'name' => $row['name'],
            'category' => $row['category'],
            'year' => $row['year'],
            'type' => $row['type'],
            'level' => $row['level'],
            'class' => $row['class'],
            'segment' => $row['segment'],
        ]);

        CourseController::add($request);

    }
    
}
