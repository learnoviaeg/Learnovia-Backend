<?php

namespace App\Imports;

use App\Course;
use App\AcademicYearType;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use App\ClassLevel;
use App\CourseSegment;
use App\Http\Controllers\CourseController;
use App\SegmentClass;
use Illuminate\Http\Request;
use App\YearLevel;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Validator;

class CoursesImport implements ToModel , WithHeadingRow
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        $no_of_lessons = 4;

        Validator::make($row,[
            'name'=>'required',
            'category'=>'exists:categories,id',
            'year' => 'required|exists:academic_years,id',
            'type' => 'required|exists:academic_types,id',
            'level' => 'required|exists:levels,id',
            'class' => 'required|exists:classes,id',
            'segment' => 'required|exists:segments,id',
            'no_of_lessons' => 'integer',
            'start_date' => 'required_with:year',
            'end_date' =>'required_with:year',
            'mandatory' => 'in:0,1'
        ])->validate();

        $course = Course::firstOrCreate([
            'name' => $row['name'],
            'category_id' => isset($row['category']) ? $row['category'] : null,
        ]);

        if (isset($row['description'])) {
            $course->description = $row['description'];
            $course->save();
        }
        if (isset($row['mandatory'])) {
            $course->mandatory = $row['mandatory'];
            $course->save();
        }

        $yeartype = AcademicYearType::checkRelation($row['year'], $row['type']);
        $yearlevel = YearLevel::checkRelation($yeartype->id, $row['level']);
        $classLevel = ClassLevel::checkRelation($row['class'], $yearlevel->id);
        $segmentClass = SegmentClass::checkRelation($classLevel->id, $row['segment']);
        $courseSegment = CourseSegment::firstOrCreate([
            'course_id' => $course->id,
            'segment_class_id' => $segmentClass->id,
            'is_active' => 1,
            'start_date' =>  Date::excelToDateTimeObject($row['start_date']),
            'end_date' =>  Date::excelToDateTimeObject($row['end_date']),
        ]);

        if (isset($row['no_of_lessons'])) {
            $no_of_lessons = $row['no_of_lessons'];
        }

        for ($i = 1; $i <= $no_of_lessons; $i++) {
            $courseSegment->lessons()->firstOrCreate([
                'name' => 'Lesson ' . $i,
                'index' => $i,
            ]);
        }
        return $course;
    }

}
