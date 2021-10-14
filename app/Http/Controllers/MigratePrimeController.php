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
use App\Log;
use App\Lesson;
use App\Announcement;
use App\GradeCategory;
use Modules\QuestionBank\Entities\QuestionsCategory;



use Illuminate\Http\Request;

class MigratePrimeController extends Controller
{
    public function type()
    {
      
        
        $academicYearTypes = AcademicYearType::get();
        foreach($academicYearTypes as $academicYearType)
        {
            $types = $academicYearType->academictype;
            //insert types
            foreach($types as $type)
            {
             $year = AcademicYearType::where('academic_type_id' , $type->id)->first();
             DB::connection('mysql2')->table('academic_types')->update(
                array(
                       'id'     =>   $type->id, 
                       'name'   =>   $type->name,
                       'academic_year_id'  =>  $year->academic_year_id  ,
                       'segment_no'   =>   $type->segment_no ,
                       'created_at'   =>   $year->created_at,
                       'updated_at'   =>   $year->updated_at,
                )
           );
           
            //  DB::connection('mysql2')->insert('insert into academic_types (id , name , academic_year_id ,segment_no , created_at , updated_at) 
            //  values ( ? ,? ,? ,? ,? ,?)', [$type->id , $type->name , $year->academic_year_id , $type->segment_no ,$year->created_at , $year->updated_at ]);
            }
        }
        echo 'Done';

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
                DB::connection('mysql2')->table('levels')->update(
                    array(
                           'id'     =>   $class->id, 
                           'name'   =>   $class->name,
                           'academic_type_id'  =>  $type->id ,
                           'created_at'   =>   $level->created_at,
                           'updated_at'   =>   $level->updated_at,
                    )
               );

                // DB::connection('mysql2')->insert('insert into levels (id , name , academic_type_id ,created_at , updated_at ) 
                // values ( ? ,? ,? ,? ,?  )', [$level->id , $level->name , $type->id ,$level->created_at , $level->updated_at ]);
              }
            }
        }
        echo 'Done';


    }
    public function segment()
    {
        $oldSegments = Segment::get();
        foreach($oldSegments as $oldSegment)
        {
            $type = $oldSegment->academicType;
            $year = AcademicYearType::find($type->id);
            DB::connection('mysql2')->table('segments')->update(
                array(
                       'id'     =>   $class->id, 
                       'name'   =>   $class->name,
                       'academic_type_id'  =>  $type->id ,
                       'academic_year_id'   =>   $year->id ,
                       'created_at'   =>   $oldSegment->created_at,
                       'updated_at'   =>   $oldSegment->updated_at,
                )
           );
            // DB::connection('mysql2')->insert('insert into segments (id , name , academic_type_id , academic_year_id ,created_at , updated_at ) 
            // values ( ? ,? ,? ,?,?,? )', [$oldSegment->id , $oldSegment->name , $type->id , $year->id ,$oldSegment->created_at , $oldSegment->updated_at ]);
        }
        echo 'Done';

    }
    public function class()
    {
        $oldClassLevels = ClassLevel::get();
        
        foreach($oldClassLevels as $oldClassLevel)
        {
            $class = Classes::find($oldClassLevel->class_id);
            $yearLevel = YearLevel::find($oldClassLevel->year_level_id);
            DB::connection('mysql2')->table('classes')->update(
                array(
                       'id'     =>   $class->id, 
                       'name'   =>   $class->name,
                       'level_id'  =>  $yearLevel->level_id,
                       'type'   =>   'class',
                       'created_at'   =>   $class->created_at,
                       'updated_at'   =>   $class->updated_at,
                )
           );

            // DB::connection('mysql2')->insert('insert into classes (id , name , level_id  ,type , created_at , updated_at ) 
            // values ( ? ,? ,? , ? ,? ,? )', [$class->id , $class->name , $yearLevel->level_id , 'class' ,$class->created_at , $class->updated_at ]);
        }
        echo 'Done';

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
            DB::connection('mysql2')->table('courses')->update(
                array(
                       'id'     =>   $course->id, 
                       'name'   =>   $course->name,
                       'mandatory'  =>  $course->mandatory,
                       'level_id'   =>   $level,
                       'segment_id'   => $segment,
                       'short_name'   =>   $course->short_name  ,
                       'progress'   =>   $course->progress  ,
                       'classes'   =>   $classes,
                       'created_at'   =>   $lesson->created_at,
                       'updated_at'   =>   $lesson->updated_at,
                )
           );
            // DB::connection('mysql2')->insert('insert into courses (id,name ,mandatory, level_id , segment_id , short_name , progress , classes, created_at , updated_at ) 
            // values (?, ? ,? , ? ,? ,? ,?,?,?,?)',
            //  [$course->id ,$course->name  ,$course->mandatory, $level , $segment , $course->short_name , $course->progress , $classes ,$course->created_at , $course->updated_at]);
        }
        echo 'Done';

    }

    public function lesson()
    {
        $lessons = Lesson::get();
        foreach($lessons as $lesson)
        {

            $classes = array();
            $courseSegment = CourseSegment::find($lesson->course_segment_id);
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
            DB::connection('mysql2')->table('lessons')->update(
                array(
                       'id'     =>   $lesson->id, 
                       'name'   =>   $lesson->name,
                       'image'   =>   $lesson->image,
                       'index'   =>   $lesson->index,
                       'description'   => $lesson->description,
                       'course_id'   =>   $courseSegment->course_id  ,
                       'created_at'   =>   $lesson->created_at,
                       'updated_at'   =>   $lesson->updated_at,
                       'shared_classes'   =>   $classes,
                )
           );
        }
    }


    public function enrolls()
    {
        $enrolls = Enroll::get();
        // dd($enrolls);
        foreach($enrolls as $enroll)
        {
            DB::connection('mysql2')->table('enrolls')->insert(
                array(
                       'id'     =>   $enroll->id, 
                       'user_id'   =>   $enroll->user_id,
                       'role_id'   =>   $enroll->role_id,
                       'created_at'   =>   $enroll->created_at,
                       'updated_at'   => $enroll->updated_at,
                       'level'   =>   $enroll->level  ,
                       'type'   =>   $enroll->type,
                       'group'   =>   $enroll->class,
                       'year'   =>   $enroll->year,
                       'course'   =>   $enroll->course,
                       'segment'   =>   $enroll->segment,
                )
           );
             $classes = array();
             $courseSegment = CourseSegment::where('course_id', $enroll->course)->first();
             $segmentClass = SegmentClass::find($courseSegment->segment_class_id);
             $segment = $segmentClass->segment_id;
             $classLevel = ClassLevel::find($segmentClass->class_level_id);
             $yearLevel = YearLevel::find($classLevel->year_level_id);
             $classLevels= ClassLevel::where('year_level_id' , $yearLevel->id)->get();
             foreach($classLevels as $classLevel)
             {
                 $classes[] = $classLevel->class_id;
             } 
             foreach($classes as $class)   
             {
                 $lessons = Lesson::where('course_segment_id' , $courseSegment->id)->get();
                 foreach($lessons as $lesson)
                 {
                    DB::connection('mysql2')->table('secondary_chains')->insert(
                        array(
                               'enroll_id'   =>   $enroll->id,
                               'course_id'   =>   $enroll->course,
                               'group_id'   =>   $class,
                               'lesson_id'   =>  $lesson->id,
                               'user_id'   =>   $enroll->user_id  ,
                               'role_id'   =>   $enroll->role_id,
                               'created_at' => $enroll->created_at,
                               'updated_at' => $enroll->updated_at,
                        )
                   );
                 }
             }
        
        }
        echo 'Done';
    }

    public function gradeCategory()
    {
        $gradeCategories= GradeCategory::get();
        foreach($gradeCategories as $gradeCategory)
        {
            $course_id = $gradeCategory->CourseSegment->course_id;

            DB::connection('mysql2')->table('grade_categories')->update(
                array(
                       'id'   =>   $gradeCategory->id ,
                       'name'   =>   $gradeCategory->name ,
                       'course_id'   =>   $gradeCategory->course,
                       'created_at' => $gradeCategory->created_at,
                       'updated_at' => $gradeCategory->updated_at,
                )
           );
            // DB::connection('mysql2')->insert('insert into grade_categories (id,name , course_id , created_at , updated_at ) 
            // values ( ?,? ,? ,? , ?)',
            //  [$gradeCategory->id ,$gradeCategory->name  ,$course_id, $gradeCategory->created_at , $gradeCategory->updated_at ]);

        }
        echo 'Done';

    }

    public function announcement()
    {
        $announcements = Announcement::get();
        foreach($announcements as $announcement)
        {
            DB::connection('mysql2')->insert('insert into announcements (id,title,attached_file,start_date,due_date,created_at,updated_at,publish_date,created_by,description)
             values(?,?,?,?,?,?,?,?,?,?)',
             [$announcement->id,$announcement->title,$announcement->attached_file,$announcement->start_date,$announcement->due_date,$announcement->created_at,
             $announcement->updated_at,$announcement->publish_date,$announcement->created_by,$announcement->description]);
        }

        echo 'Done';

    }
    public function questionCateory()
    {
        $questionCategories = QuestionsCategory::get();
        foreach($questionCategories as $questionCategory)
        {
            $courseSegment = CourseSegment::find($questionCategory->course_segment_id);

            DB::connection('mysql2')->table('grade_categories')->update(
                array(
                       'id'   =>   $questionCategory->id ,
                       'name'   =>   $questionCategory->name ,
                       'created_at' => $questionCategory->created_at,
                       'updated_at' => $questionCategory->updated_at,
                       'course_id'   =>  $courseSegment->course_id
                )
           );

            // DB::connection('mysql2')->insert('insert into questions_categories (id,name,created_at,updated_at,course_id)
            //  values(?,?,?,?,?)',
            //  [$questionCategory->id,$questionCategory->name,$questionCategory->created_at,$questionCategory->updated_at,$courseSegment->course_id]);
        }
      echo 'Done';
    }
    public function logs()
    {
        $logs = Log::get();
        foreach($logs as $log)
        {
            DB::connection('mysql2')->table('logs')->insert(
                array(
                       'id'=>   $log->id,
                       'user'=>  $log->user,
                       'action'=>  $log->action,
                       'model'=> $log->model,
                       'data'=> $log->data,
                       'created_at'=> $log->created_at,
                       'updated_at'=> $log->updated_at,
                )
           );
        }

        echo 'Done';

    }



    
}
