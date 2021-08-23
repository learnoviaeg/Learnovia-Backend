<?php

namespace App\Imports;

use App\Course;
use App\AcademicYearType;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use App\ClassLevel;
use App\CourseSegment;
use App\Segment;
use App\Http\Controllers\CourseController;
use App\SegmentClass;
use Illuminate\Http\Request;
use App\YearLevel;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use App\GradeCategory;
use Validator;
use Carbon\Carbon;
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
        // $no_of_lessons = 4;

        Validator::make($row,[
            'name'=>'required',
            'category'=>'exists:categories,id',
            'level_id' => 'required|exists:levels,id',
            // 'no_of_lessons' => 'integer',
            'mandatory' => 'in:0,1',
            'short_name' => 'unique:courses',
        ])->validate();

        // $Class='class_id';
        // $classCount=1;
        // $classes_ids=[];
        $course = Course::create([
            'name' => $row['name'],
            'short_name' => $row['short_name'],
            'category_id' => isset($row['category']) ? $row['category'] : null,
            'mandatory' => isset($row['mandatory']) ? $row['mandatory'] : 1,
            'description' => isset($row['description']) ? $row['description'] : null,
            'level_id' => $row['level_id']
        ]);

        $yearLevel = YearLevel::where('level_id', $row['level_id'])->first();
        if(!isset($row['segment'])){
            Validator::make($row,[
                'type_id' => 'required'
            ])->validate();

            //get current segment in case there is one segment active in all types of all system
            $segment=Segment::where('academic_type_id',$row['type_id'])->where('end_date','>=',Carbon::now())->pluck('id')->first();
            if(!isset($segment)){
                Validator::make($row,[
                    'segment_id' => 'required'
                ])->validate();

                $segment=$row['segment_id'];
            }
        }
        else
            $segment=$row['segment_id'];

            // $courseSegment = CourseSegment::firstOrCreate([
            //     'course_id' => $course->id,
            //     'segment_class_id' => $one->id,
            //     'is_active' => 1,
            //     'start_date' =>  Date::excelToDateTimeObject($row['start_date']),
            //     'end_date' =>  Date::excelToDateTimeObject($row['end_date']),
            // ]);
            // $gradeCat = GradeCategory::firstOrCreate([
            //     'name' => $course->name . ' Total',
            //     'course_segment_id' => $courseSegment->id,
            //     // 'id_number' => isset($row['level_id']) ? $yearLevel->id : null
            // ]);

            // //Creating defult question category
            // $quest_cat = QuestionsCategory::firstOrCreate([
            //     'name' => $course->name . ' Category',
            //     'course_id' => $course->id,
            //     'course_segment_id' => $courseSegment->id
            // ]);

            // for ($i = 1; $i <= $no_of_lessons; $i++) {
            //     $courseSegment->lessons()->firstOrCreate([
            //         'name' => 'Lesson ' . $i,
            //         'index' => $i,
            //     ]);
            // }
    }
}
