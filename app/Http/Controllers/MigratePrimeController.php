<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;

use App\AcademicYearType;
use App\AcademicYear;
use App\AcademicType;
use App\YearLevel;
use App\Segment;
use App\ClassLevel;
use App\Classes;
use App\Course;
use App\CourseSegment;
use App\SegmentClass;
use App\Enroll;
use App\GradeCategory;


use Illuminate\Http\Request;

class MigratePrimeController extends Controller
{
    public function yearType()
    {
        // insert years
        $years = AcademicYear::get();
        foreach($years as $year)
        {
            DB::connection('mysql2')->insert('insert into academic_years (id , name , current , created_at , updated_at ) 
            values ( ? ,? ,?, ? ,? )', [$year->id , $year->name , $year->current , $year->created_at , $year->updated_at]);
        }

        $academicYearTypes = AcademicYearType::get();
        foreach($academicYearTypes as $academicYearType)
        {
            $types = $academicYearType->academictype;
            //insert types
            foreach($types as $type)
            {
             $year = AcademicYearType::where('academic_type_id' , $type->id)->first();
             DB::connection('mysql2')->insert('insert into academic_types (id , name , academic_year_id ,segment_no , created_at , updated_at) 
             values ( ? ,? ,? ,? ,? ,?)', [$type->id , $type->name , $year->academic_year_id , $type->segment_no ,$year->created_at , $year->updated_at ]);
            }
        }
    }

    public function level()
    {
        $oldYearLevels = YearLevel::get();
        foreach($oldYearLevels as $oldYearLevel)
        {
            $levels = $oldYearLevel->levels;
            foreach($levels as $level)
            {
              $yearTypes = $oldYearLevel->yearType;
              
              foreach($yearTypes as $yearType)
              {
                $type = $yearType->academictype->first();
                DB::connection('mysql2')->insert('insert into levels (id , name , academic_type_id ,created_at , updated_at ) 
                values ( ? ,? ,? ,? ,?  )', [$level->id , $level->name , $type->id ,$level->created_at , $level->updated_at ]);
              }
            }
        }

    }
    public function segment()
    {
        $oldSegments = Segment::get();
        foreach($oldSegments as $oldSegment)
        {
            $type = $oldSegment->academicType;
            $year = AcademicYearType::find($type->id);
            DB::connection('mysql2')->insert('insert into segments (id , name , academic_type_id , academic_year_id ,created_at , updated_at ) 
            values ( ? ,? ,? ,?,?,? )', [$oldSegment->id , $oldSegment->name , $type->id , $year->id ,$oldSegment->created_at , $oldSegment->updated_at ]);
        }

    }
    public function class()
    {
        $oldClassLevels = ClassLevel::get();
        
        foreach($oldClassLevels as $oldClassLevel)
        {
            $class = Classes::find($oldClassLevel->class_id);
            $yearLevel = YearLevel::find($oldClassLevel->year_level_id);
            DB::connection('mysql2')->insert('insert into classes (id , name , level_id  ,type , created_at , updated_at ) 
            values ( ? ,? ,? , ? ,? ,? )', [$class->id , $class->name , $yearLevel->level_id , 'class' ,$class->created_at , $class->updated_at ]);
        }
    }
    public function course()
    {
        $courses = Course::get();
        foreach($courses as $course)
        {
            $classes = array();
            $courseSegment = CourseSegment::where('course_id', $course->id)->first();
            $segmentClass = SegmentClass::find($courseSegment->segment_class_id);
            $segment = $segmentClass->segment_id;
            $classLevel = ClassLevel::find($segmentClass->class_level_id);
            $yearLevel = YearLevel::find($classLevel->year_level_id);
            $classLevels= ClassLevel::where('year_level_id' , $yearLevel->id)->get();
            foreach($classLevels as $classLevel)
            {
                $classes[] = $classLevel->class_id;
            }
            $classes = json_encode($classes);
            $level = $yearLevel->level_id;
            DB::connection('mysql2')->insert('insert into courses ( name ,mandatory, level_id , segment_id , short_name , progress , classes, created_at , updated_at ) 
            values ( ? ,? , ? ,? ,? ,?,?,?,?)',
             [$course->name  ,$course->mandatory, $level , $segment , $course->short_name , $course->progress , $classes ,$course->created_at , $course->updated_at]);
        }
    }


    public function enrolls()
    {
        $enrolls = Enroll::get();
        foreach($enrolls as $enroll)
        {
            DB::connection('mysql2')->insert('insert into enrolls ( user_id , role_id, created_at , updated_at , level , type , group , year , course , segment ) 
            values ( ? ,? ,? , ? ,? ,? ,? , ?,?,?)',
             [$enroll->user_id  ,$enroll->role_id ,$enroll->created_at , $enroll->updated_at, $enroll->level , $enroll->type , $enroll->class , $enroll->year , $enroll->course , $enroll->segment]);
        }

    }

    public function gradeCategory()
    {
        $gradeCategories= GradeCategory::get();
        foreach($gradeCategories as $gradeCategory)
        {
            $course_id = $gradeCategory->CourseSegment->course_id;
            DB::connection('mysql2')->insert('insert into grade_categories ( name , course_id , created_at , updated_at ) 
            values ( ? ,? ,? , ?)',
             [$gradeCategory->name  ,$course_id, $gradeCategory->created_at , $gradeCategory->updated_at ]);

        }

    }
}
