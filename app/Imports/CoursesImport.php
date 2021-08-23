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
        $no_of_lessons = 4;

        Validator::make($row,[
            'name'=>'required',
            'category'=>'exists:categories,id',
            'level_id' => 'required|exists:levels,id',
            'segment_id' => 'required|exists:segments,id',
            'no_of_lessons' => 'integer',
            'mandatory' => 'in:0,1',
            'short_name' => 'unique:courses',
            'shared_lesson' => 'required|in:0,1'
        ])->validate();

        $course = Course::firstOrCreate([
            'name' => $row['name'],
            'short_name' => $row['short_name'],
            'segment_id' => $row['segment_id'],
            'level_id' => $row['level_id'],
            'category_id' => isset($row['category']) ? $row['category'] : null,
            'mandatory' => isset($row['mandatory']) ? $row['mandatory'] : 1,
            'description' => isset($row['description']) ? $row['description'] : null
        ]);

        $level_id=$row['level_id'];
        $segment=Segment::find($row['segment_id']);
        $segment_id=$segment->id;
        $year_id=$segment->academic_year_id;
        $type_id=$segment->academic_type_id;
        $classes=Classes::where('level_id',$row['level_id'])->get()->unique();
        // dd($classes);

        foreach($classes as $class)
        {
            $enroll=Enroll::firstOrCreate([
                'user_id'=> 1,
                'role_id' => 1,
                'year' => $year_id,
                'type' => $type_id,
                'segment' => $segment_id,
                'level' => $level_id,
                'group' => $class->id,
                'course' => $course->id
            ]);

            if (isset($row['no_of_lessons'])) 
                $no_of_lessons = $row['no_of_lessons'];

            for ($i = 1; $i <= $no_of_lessons; $i++) {
                $lesson=lesson::firstOrCreate([
                    'name' => 'Lesson ' . $i,
                    'index' => $i,
                ]);

                SecondaryChain::firstOrCreate([
                    'user_id' => 1,
                    'role_id' => 1,
                    'group_id' => $class->id,
                    'lesson_id' => $lesson->id,
                    'enroll_id' => $enroll->id
                ]);
            }
        }

        //Creating defult question category
        $quest_cat = QuestionsCategory::firstOrCreate([
            'name' => $course->name . ' Category',
            'course_id' => $course->id,
        ]);
    }
}
