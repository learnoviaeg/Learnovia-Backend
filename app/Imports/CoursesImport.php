<?php

namespace App\Imports;

use App\Course;
// use App\AcademicYearType;
use PhpOffice\PhpSpreadsheet\Shared\Date;
// use App\ClassLevel;
// use App\CourseSegment;
use App\Segment;
use App\Classes;
use App\SecondaryChain;
use App\Lesson;
use App\Enroll;
use App\Http\Controllers\CourseController;
// use App\SegmentClass;
use Illuminate\Http\Request;
// use App\YearLevel;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use App\GradeCategory;
use Validator;
use Modules\QuestionBank\Entities\QuestionsCategory;

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
            'name'=>'required',
            'category'=>'exists:categories,id',
            'level_id' => 'required|exists:levels,id',
            'segment_id' => 'required|exists:segments,id',
            'no_of_lessons' => 'integer',
            'mandatory' => 'in:0,1',
            // 'short_name' => 'unique:courses',
        ])->validate();

        $short_names=Course::where('segment_id',$row['segment_id'])->where('short_name',$row['short_name'])->get();
        if(count($short_names)>0)
            die('short name must be unique');

        $course = Course::firstOrCreate([
            'name' => $row['name'],
            'short_name' => $row['short_name'],
            'segment_id' => $row['segment_id'],
            'level_id' => $row['level_id'],
            'category_id' => isset($row['category']) ? $row['category'] : null,
            'mandatory' => isset($row['mandatory']) ? $row['mandatory'] : 1,
            'description' => isset($row['description']) ? $row['description'] : null
        ]);

        //Creating defult question category
        $quest_cat = QuestionsCategory::firstOrCreate([
            'name' => $course->name . ' Category',
            'course_id' => $course->id,
        ]);
    }
}
