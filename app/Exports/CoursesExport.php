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

    function __construct($request,$segment,$year) {
        $this->request = $request;
        $this->segment = $segment;
        $this->year = $year;
    }
    public function collection()
    {
        $allCourses = collect();
        $testCourse=array();
        $adminCourses=collect();
        $couuures=array();
        $course_Segmenttt=CourseSegment::where('id', '!=',0);
       
       $courses_ids= $course_Segmenttt->whereHas('enroll',function($q)
        {
            if(!$this->request->user()->can('site/show-all-courses'))
            {
            $q->where('user_id',Auth::id());
            }
            if ($this->request->has('level')) {
                $q->where('level',$this->request->level);
            }
            if ($this->request->has('type')) {
                $q->where('type',$this->request->type);
            } 
           if ($this->request->has('class')) {
                $q->where('class',$this->request->class);
            }
            $q->where('year',$this->year);
            $q->whereIn('segment',$this->segment);

        })->pluck('course_id');
        $courses = Course:: whereIn('id',$courses_ids)->get(); 
        foreach ($courses as $course) {
            $course->setHidden([])->setVisible($this->fields);
        }
        return $courses;
        
    }
    public function headings(): array
    {
        return $this->fields;
    }
}
