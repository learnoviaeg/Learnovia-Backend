<?php

namespace App\Exports;

use App\Course;
use App\CourseSegment;
use App\Segment;
use App\AcademicYear;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use App\Http\Controllers\HelperController;


class CoursesExport implements FromCollection,WithHeadings
{
    protected $fields = ['id','name','short_name'];

    /**
    * @return \Illuminate\Support\Collection
    */

    function __construct($courses) {
        $this->courses = $courses;
 
    }
    public function collection()
    {
        foreach ($this->courses as $course) {
            $course->setHidden([])->setVisible($this->fields);
        }
        return $this->courses;
        
    }
    public function headings(): array
    {
        return $this->fields;
    }
}
