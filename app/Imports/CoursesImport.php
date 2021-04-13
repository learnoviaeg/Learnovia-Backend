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
        $no_of_lessons = 4;

        Validator::make($row,[
            'name'=>'required',
            'category'=>'exists:categories,id',
            'level_id' => 'exists:levels,id',
            'no_of_lessons' => 'integer',
            'start_date' => 'required_with:year',
            'end_date' =>'required_with:year',
            'mandatory' => 'in:0,1',
            'short_name' => 'unique:courses'
        ])->validate();

        $Class='class_id';
        $classCount=1;
        $classes_ids=[];
        $course = Course::firstOrCreate([
            'name' => $row['name'],
            'short_name' => $row['short_name'],
            'category_id' => isset($row['category']) ? $row['category'] : null,
            'mandatory' => isset($row['mandatory']) ? $row['mandatory'] : 1,
            'description' => isset($row['description']) ? $row['description'] : null
        ]);

        if (isset($row['no_of_lessons'])) 
            $no_of_lessons = $row['no_of_lessons'];

        if(isset($row['level_id'])){
            $yearLevel = YearLevel::where('level_id', $row['level_id'])->first();
            $classLevel=ClassLevel::where('year_level_id',$yearLevel->id)->pluck('id');
        }
        while(isset($row[$Class.$classCount])){
            $classes_ids[] = ClassLevel::where('class_id', $row[$Class.$classCount])->pluck('id')->first();
            $classCount++;
        }
        if(count($classes_ids) > 0)
            $classLevel=$classes_ids;
        
        //get current segment in case there is one segment active in all types of all system
        $segment = Segment::where('current',1)->pluck('id')->first();
        if(isset($row['segment_id']))
            $segment=$row['segment_id'];
        $segmentClass = SegmentClass::whereIn('class_level_id',$classLevel)->where('segment_id',$segment)->get();
        foreach($segmentClass as $one)
        {
            $courseSegment = CourseSegment::firstOrCreate([
                'course_id' => $course->id,
                'segment_class_id' => $one->id,
                'is_active' => 1,
                'start_date' =>  Date::excelToDateTimeObject($row['start_date']),
                'end_date' =>  Date::excelToDateTimeObject($row['end_date']),
            ]);
            $gradeCat = GradeCategory::firstOrCreate([
                'name' => $course->name . ' Total',
                'course_segment_id' => $courseSegment->id,
                'id_number' => isset($row['level_id']) ? $yearLevel->id : null
            ]);

            //Creating defult question category
            $quest_cat = QuestionsCategory::firstOrCreate([
                'name' => $course->name . ' Category',
                'course_id' => $course->id,
                'course_segment_id' => $courseSegment->id
            ]);

            for ($i = 1; $i <= $no_of_lessons; $i++) {
                $courseSegment->lessons()->firstOrCreate([
                    'name' => 'Lesson ' . $i,
                    'index' => $i,
                ]);
            }
        }
    }
}
