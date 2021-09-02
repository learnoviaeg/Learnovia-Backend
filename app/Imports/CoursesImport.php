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
use App\Http\Controllers\CoursesController;
// use App\SegmentClass;
use Illuminate\Http\Request;
use App\Events\CourseCreatedEvent;
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
            'shared_lesson' => 'required_with:no_of_lessons|in:0,1',
            'short_name' =>'required'
            // 'short_name' => 'unique:courses',
        ])->validate();


        $short_names=Course::where('segment_id',$row['segment_id'])->where('short_name',$row['short_name'])->get();
        if(count($short_names)>0)
            die('short name must be unique');

        $no_of_lessons = 4;
        if (isset($row['no_of_lessons'])) 
            $no_of_lessons = $row['no_of_lessons'];

        $chains[0]['segment'][0]=$row['segment_id'];
        $chains[0]['level'][0]=$row['level_id'];
        // dd($chains);

        $req=new Request([
            'name' => $row['name'],
            'short_name' => $row['short_name'],
            'chains' => $chains,
            'chains' => $chains,
            'category_id' => isset($row['category']) ? $row['category'] : null,
            'mandatory' => isset($row['mandatory']) ? $row['mandatory'] : 1,
            'description' => isset($row['description']) ? $row['description'] : null,
            'shared_lesson' => isset($row['shared_lesson']) ? $row['shared_lesson'] : 0,
            'no_of_lessons' => isset($row['no_of_lessons']) ? $row['no_of_lessons'] : 4,
            'is_template' => isset($row['is_template']) ? $row['no_of_lessons'] : 0,
        ]);

        //should to find a way to call this without change it to static
        // CoursesController::store($req);
        app('App\Http\Controllers\CoursesController')->store($req);
    }
}
