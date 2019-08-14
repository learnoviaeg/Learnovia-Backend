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
use Illuminate\Support\Facades\Validator;



class CoursesImport implements ToModel , WithHeadingRow
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        Validator::make($row,[
            'name'=>'string',
            'category'=>'exists:categories,id',
            'year'=>'exists:academic_years,id',
            'type'=>'required|exists:academic_types,id',
            'level'=>'exists:levels,id',
            'class'=>'required|exists:classes,id',
            'segment'=>'exists:segments,id',


        ])->validate();

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
