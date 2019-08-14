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
use App\Segment;
use App\AcademicYear;


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
            'type'=>'required|exists:academic_types,id',
            'level'=>'exists:levels,id',
            'class'=>'required|exists:classes,id',


        ])->validate();
        $year = AcademicYear::Get_current()->id;
        $segment = Segment::Get_current()->id;
        if (isset($row['year'])) {
            Validator::make($row,[
                'year'=>'exists:academic_years,id',
            ])->validate();
            $year = $row['year'] ;
        }
        if (isset($row['segment'])) {
            Validator::make($row,[
                'segment'=>'exists:segments,id',
                ])->validate();
            $segment = $row['segment'] ;
        }
        $request = new Request([
            'name' => $row['name'],
            'category' => $row['category'],
            'year' => $year,
            'type' => $row['type'],
            'level' => $row['level'],
            'class' => $row['class'],
            'segment' => $segment,
        ]);

        CourseController::add($request);

    }

}
