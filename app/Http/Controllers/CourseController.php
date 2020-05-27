<?php

namespace App\Http\Controllers;

use App\AcademicYearType;
use App\ClassLevel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use App\Classes;
use Nwidart\Modules\Collection;
use App\Course;
use App\CourseSegment;
use App\Lesson;
use App\Level;
use App\SegmentClass;
use App\Component;
use App\YearLevel;
use Illuminate\Http\Request;
use App\Enroll;
use App\Segment;
use App\AcademicYear;
use App\GradeCategory;
use Modules\QuestionBank\Entities\QuizOverride;
use App\AcademicType;
use App\attachment;
use App\LessonComponent;
use App\User;
use Modules\QuestionBank\Entities\QuizLesson;
use Modules\Assigments\Entities\AssignmentLesson;
use Carbon\Carbon;
use App\Letter;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use stdClass;
use Modules\Assigments\Entities\assignmentOverride;
use Modules\Page\Entities\pageLesson;
use Modules\UploadFiles\Entities\FileLesson;
use Modules\UploadFiles\Entities\MediaLesson;

class CourseController extends Controller
{
    /**
     * Add course
     *
     * @param  [string] name, description
     * @param  [int] category, no_of_lessons
     * @param  [array..id] year, type, level, class, segment
     * @param  [string..path] image
     * @param [boolean] mandatory, typical
     * @return [object] course with attachment and category in paginate
     */
    public static function add(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'category' => 'exists:categories,id',
            'year' => 'array',
            'year.*' => 'required|exists:academic_years,id',
            'type' => 'array|required_with:year',
            'type.*' => 'exists:academic_types,id',
            'level' => 'array|required_with:year',
            'level.*' => 'required|exists:levels,id',
            'class' => 'array|required_with:year',
            'class.*' => 'required|exists:classes,id',
            'segment' => 'array|required_with:year',
            'segment.*' => 'required|exists:segments,id',
            'no_of_lessons' => 'integer',
            'image' => 'file|distinct|mimes:jpg,jpeg,png,gif',
            'description' => 'string',
            'mandatory' => 'nullable',
            'typical' => 'nullable|boolean',
            'start_date' => 'required_with:year|date',
            'end_date' =>'required_with:year|date|after:start_date'
        ]);
        $no_of_lessons = 4;
        $course = Course::create([
            'name' => $request->name,
        ]);
        // if course has an image
        if ($request->hasFile('image')) {
            $course->image = attachment::upload_attachment($request->image, 'course')->id;
            $course->save();
        }
        if ($request->filled('category_id')) {
            $course->category_id = $request->category_id;
            $course->save();
        }

        // if course has description
        if ($request->filled('description')) {
            $course->description = $request->description;
            $course->save();
        }

        // if course is mandatory
        if ($request->filled('mandatory')) {
            $course->mandatory = $request->mandatory;
            $course->save();
        }
        if($request->filled('year')){
            foreach ($request->year as $year) {
                # code...
                foreach ($request->type as $type) {
                    # code...
                    $yeartype = AcademicYearType::checkRelation($year, $type);
                    foreach ($request->level as $level) {
                        # code...
                        $yearlevel = YearLevel::checkRelation($yeartype->id, $level);
                        foreach ($request->class as $class) {
                            # code...
                            $classLevel = ClassLevel::checkRelation($class, $yearlevel->id);
                            foreach ($request->segment as $segment) {
                                # code...
                                $segmentClass = SegmentClass::checkRelation($classLevel->id, $segment);
                                $courseSegment = CourseSegment::firstOrCreate([
                                    'course_id' => $course->id,
                                    'segment_class_id' => $segmentClass->id,
                                    'is_active' => 1,
                                    'typical' => $request->typical,
                                    'start_date' => $request->start_date,
                                    'end_date' => $request->end_date,
                                    'letter' => 1,
                                    'letter_id' => 1
                                ]);
                                $gradeCat = GradeCategory::firstOrCreate([
                                    'name' => 'Course Total',
                                    'course_segment_id' => $courseSegment->id,
                                    'id_number' => $yearlevel->id
                                ]);
                                if ($request->filled('no_of_lessons')) {
                                    $no_of_lessons = $request->no_of_lessons;
                                }
                                for ($i = 1; $i <= $no_of_lessons; $i++) {
                                    $courseSegment->lessons()->create([
                                        'name' => 'Lesson ' . $i,
                                        'index' => $i,
                                    ]);
                                }
                            }
                        }
                    }
                }
            }
        }
        $course->attachment;
        return HelperController::api_response_format(201, Course::with(['category', 'attachment'])->paginate(HelperController::GetPaginate($request)), 'Course Created Successfully');
    }

    /**
     * update course
     *
     * @param  [string] name, description
     * @param  [int] category_id, id
     * @param  [string..path] image
     * @param  [boolean] mandatory
     * @return [object] course with attachment and category in paginate
     */
    public function update(Request $request)
    {
        $request->validate([
            'name' => 'nullable',
            'category_id' => 'nullable|exists:categories,id',
            'id' => 'required|exists:courses,id',
            'image' => 'nullable',
            'description' => 'nullable',
            'mandatory' => 'nullable|in:0,1'
        ]);
        $editable = ['name', 'category_id', 'description', 'mandatory'];
        $course = Course::find($request->id);
        $course->name = $request->name;
        if($request->filled('category_id'))
            $course->category_id = $request->category_id;

        // if course has an image
        if ($request->hasFile('image')) {
            $course->image = attachment::upload_attachment($request->image, 'course')->id;
        }
        foreach ($editable as $key) {
            if ($request->filled($key)) {
                $course->$key = $request->$key;
            }
        }
        $course->save();
        return HelperController::api_response_format(200, Course::with(['category', 'attachment'])->paginate(HelperController::GetPaginate($request)), 'Course Updated Successfully');
    }

    /**
     * get course
     *
     * @param  [int] id, category_id
     * @param  [array..id] year, type, level, class, segment
     * @param  [string] search
     * @return [object] course with attachment and category in paginate with search
     * @return [object] course with attachment and category in paginate if id
     */
    public function get(Request $request)
    {
        $request->validate([
            'id' => 'exists:courses,id',
            'category_id' => 'nullable|exists:categories,id',
            'year' => 'nullable|exists:academic_years,id',
            'type' => 'nullable|exists:academic_types,id|required_with:year',
            'level' => 'nullable|exists:levels,id|required_with:year',
            'class' => 'nullable|exists:classes,id|required_with:year',
            'segment' => 'nullable|exists:segments,id',
            'search' => 'nullable'
        ]);
        if ($request->filled('year')) {
            $academic_year_type = AcademicYearType::checkRelation($request->year, $request->type);
            $year_level = YearLevel::checkRelation($academic_year_type->id, $request->level);
            $class_level = ClassLevel::checkRelation($request->class, $year_level->id);
            if ($request->filled('segment'))
                $segment = $request->segment;
            else
                $segment = Segment::Get_current($request->type)->id;
            $segment_class = SegmentClass::checkRelation($class_level->id, $segment);
            $courseSegment = $segment_class->courseSegment->pluck('course_id');
            return HelperController::api_response_format(200, Course::with(['category', 'attachment'])->whereIn('id', $courseSegment)->paginate(HelperController::GetPaginate($request)));
        }
        if (isset($request->id))
            return HelperController::api_response_format(200, Course::with(['category', 'attachment'])->whereId($request->id)->first());
        return HelperController::api_response_format(200, Course::with(['category', 'attachment'])->where('name', 'LIKE', "%$request->search%")->get()
            ->paginate(HelperController::GetPaginate($request)));
    }

    /**
     * delete course
     *
     * @param  [int] id
     * @return [object] course with attachment and category in paginate
     */
    public function delete(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:courses,id'
        ]);
        $course = Course::find($request->id);
        $course->delete();
        return HelperController::api_response_format(200, Course::with(['category', 'attachment'])->paginate(HelperController::GetPaginate($request)), 'Course Deleted Successfully');
    }


    /**
     * get current enrolledCourses
     *
     * @return [object] currenr course_name with teacher and category with all chain
     */
    public function CurrentCourses(Request $request)
    {
        $request->validate([
            'type' => 'array',
            'type.*' => 'exists:academic_types,id',
            'levels' => 'array',
            'levels.*' => 'exists:levels,id',
            'classes' => 'array',
            'classes.*' => 'exists:classes,id',
        ]);
        $all = collect();
        $testCourse=array();
        if($request->filled('type') || $request->filled('levels') || $request->filled('classes') )
            $CS = GradeCategoryController::getCourseSegmentWithArray($request);

            foreach ($request->user()->enroll as $enroll) {
           
            if ($enroll->CourseSegment->end_date > Carbon::now() && $enroll->CourseSegment->start_date < Carbon::now()) {
                if($request->filled('type') || $request->filled('levels') || $request->filled('classes') ){
                    if(!in_array($enroll->CourseSegment->id, $CS->toArray()))
                        continue;
                }

                $segment_Class_id = CourseSegment::where('id', $enroll->CourseSegment->id)->get(['segment_class_id', 'course_id'])->first();
                $course = Course::where('id', $segment_Class_id->course_id)->with(['category', 'attachment'])->first();

                $request->validate([
                    'course_id' => 'exists:courses,id'
                ]);
                if($request->filled('course_id'))
                    $course = Course::where('id', $request->course_id)->with(['category', 'attachment'])->first();
                    
                if(in_array($course->id,$testCourse))
                    continue;
                array_push($testCourse,$course->id);
                $segment = SegmentClass::where('id', $segment_Class_id->segment_class_id)->get(['segment_id', 'class_level_id'])->first();
                $flag = new stdClass();
                $flag->segment = Segment::find($segment->segment_id)->name;
                $class_id = ClassLevel::where('id', $segment->class_level_id)->get(['class_id', 'year_level_id'])->first();
                $flag->class = Classes::find($class_id->class_id)->name;
                $level_id = YearLevel::where('id', $class_id->year_level_id)->get(['level_id', 'academic_year_type_id'])->first();
                $flag->level = Level::find($level_id->level_id)->name;
                $AC_type = AcademicYearType::where('id', $level_id->academic_year_type_id)->get(['academic_year_id', 'academic_type_id'])->first();
                if(isset(AcademicYear::find($AC_type->academic_type_id)->name)){
                $flag->year = AcademicYear::find($AC_type->academic_type_id)->name;
                $flag->type = AcademicYear::find($AC_type->academic_type_id)->name;}
                $teacher = User::whereIn('id',
                Enroll::where('role_id', '4')
                    ->where('course_segment', $enroll->CourseSegment->id)
                    ->pluck('user_id')
                    )->get(['id', 'username', 'firstname', 'lastname', 'picture']);
                    
                foreach($teacher as $one)
                    if(isset($one->attachment))
                        $one->picture=$one->attachment->path;

                $teacher->class = $enroll->CourseSegment->segmentClasses[0]->classLevel[0]->classes[0];
                $course->flag = $flag;
                $course->teacher = $teacher;
                if(!isset($course->attachment)){
                    $course->attachment = null;
                }
                $all->push($course);
            }
        }
        if (isset($all))
            return HelperController::api_response_format(200, (new Collection($all))->paginate(HelperController::GetPaginate($request)));

        return HelperController::api_response_format(200, null, 'there is no courses');
    }

    /**
     * get past enrolledCourses
     *
     * @return [object] past courses_name with teacher and category with all chain
     */
    public function PastCourses(Request $request)
    {
        $all = collect();
        $testCourse=array();
        $i = 0;
        foreach ($request->user()->enroll as $enroll) {
            if ($enroll->CourseSegment->end_date < Carbon::now() && $enroll->CourseSegment->start_date < Carbon::now()) {
                $segment_Class_id = CourseSegment::where('id', $enroll->CourseSegment->id)->get(['segment_class_id', 'course_id'])->first();
                $course = Course::where('id', $segment_Class_id->course_id)->with(['category', 'attachment'])->first();

                $request->validate([
                    'course_id' => 'exists:courses,id'
                ]);
                if($request->filled('course_id'))
                    $course = Course::where('id', $request->course_id)->with(['category', 'attachment'])->first();

                if(in_array($course->id,$testCourse))
                    continue;
                array_push($testCourse,$course->id);
                $segment = SegmentClass::where('id', $segment_Class_id->segment_class_id)->get(['segment_id', 'class_level_id'])->first();
                $flag = new stdClass();
                $flag->segment = Segment::find($segment->segment_id)->name;
                $class_id = ClassLevel::where('id', $segment->class_level_id)->get(['class_id', 'year_level_id'])->first();
                $flag->class = Classes::find($class_id->class_id)->name;
                $level_id = YearLevel::where('id', $class_id->year_level_id)->get(['level_id', 'academic_year_type_id'])->first();
                $flag->level = Level::find($level_id->level_id)->name;
                $AC_type = AcademicYearType::where('id', $level_id->academic_year_type_id)->get(['academic_year_id', 'academic_type_id'])->first();
                $flag->year = AcademicYear::find($AC_type->academic_type_id)->name;
                $flag->type = AcademicYear::find($AC_type->academic_type_id)->name;
                $teacher = User::whereId(Enroll::where('role_id', 4)
                    ->where('course_segment', $enroll->CourseSegment->id)
                    ->pluck('user_id')
                )->get(['id', 'username', 'firstname', 'lastname', 'picture'])[0];
                $teacher->class = $enroll->CourseSegment->segmentClasses[0]->classLevel[0]->classes[0];
                $course->flag = $flag;
                $course->teacher = $teacher;
                $all->push($course);
            }
        }
        if (isset($all))
            return HelperController::api_response_format(200, $all);

        return HelperController::api_response_format(200, null, 'there is no courses');
    }

    /**
     * get future enrolledCourses
     *
     * @return [object] future courses_name with teacher and category with all chain
     */
    public function FutureCourses(Request $request)
    {
        $all = collect();
        $testCourse=array();
        $i = 0;
        foreach ($request->user()->enroll as $enroll) {
            if ($enroll->CourseSegment->end_date > Carbon::now() && $enroll->CourseSegment->start_date > Carbon::now()) {
                $segment_Class_id = CourseSegment::where('id', $enroll->CourseSegment->id)->get(['segment_class_id', 'course_id'])->first();
                $course = Course::where('id', $segment_Class_id->course_id)->with(['category', 'attachment'])->first();

                $request->validate([
                    'course_id' => 'exists:courses,id'
                ]);
                if($request->filled('course_id'))
                    $course = Course::where('id', $request->course_id)->with(['category', 'attachment'])->first();

                if(in_array($course->id,$testCourse))
                    continue;
                array_push($testCourse,$course->id);
                $segment = SegmentClass::where('id', $segment_Class_id->segment_class_id)->get(['segment_id', 'class_level_id'])->first();
                $flag = new stdClass();
                $flag->segment = Segment::find($segment->segment_id)->name;
                $class_id = ClassLevel::where('id', $segment->class_level_id)->get(['class_id', 'year_level_id'])->first();
                $flag->class = Classes::find($class_id->class_id)->name;
                $level_id = YearLevel::where('id', $class_id->year_level_id)->get(['level_id', 'academic_year_type_id'])->first();
                $flag->level = Level::find($level_id->level_id)->name;
                $AC_type = AcademicYearType::where('id', $level_id->academic_year_type_id)->get(['academic_year_id', 'academic_type_id'])->first();
                $flag->year = AcademicYear::find($AC_type->academic_type_id)->name;
                $flag->type = AcademicYear::find($AC_type->academic_type_id)->name;
                $teacher = User::whereId(Enroll::where('role_id', '4')->where('course_segment', $enroll->CourseSegment->id)->pluck('user_id'))->get(['id', 'username', 'firstname', 'lastname', 'picture'])[0];
                $teacher->class = $enroll->CourseSegment->segmentClasses[0]->classLevel[0]->classes[0];
                $course->flag = $flag;
                $course->teacher = $teacher;
                $all->push($course);
            }
        }
        if (isset($all))
            return HelperController::api_response_format(200, $all);

        return HelperController::api_response_format(200, null, 'there is no courses');
    }

    /**
     * get course with teacher
     *
     * @return [object] course with teacher
     */
    public function course_with_teacher()
    {
        $teacherrole  = Permission::whereName('site/course/teacher')->first()->roles->pluck('id');
        $teachers = Enroll::whereIn('role_id', $teacherrole)->get(['course_segment']);
        foreach ($teachers as $tech) {
            $coursesegment = CourseSegment::find($tech->course_segment);
            $tech['course'] = $coursesegment->courses;
        }
        return HelperController::api_response_format(200, $teachers);
    }

    /**
     * get UserCourseLessons
     *
     * @param [int] course_id
     * @return [object] course with lessons[all attachments; assignments,....]
     */
    public function GetUserCourseLessons(Request $request)
    {
        $request->validate([
            'course_id' => 'required|exists:course_segments,course_id'
        ]);
        $CourseSeg = Enroll::where('user_id', $request->user()->id)->pluck('course_segment');
        
        $seggg = array();
        $userrole = array();
        foreach ($CourseSeg as $cour) {
            $check = CourseSegment::where('course_id', $request->course_id)->where('id', $cour)->pluck('id')->first();
            if ($check != null) {
                $seggg[] = $check;
            }
        }
        $CourseSeg = array();
        foreach ($seggg as $segggg) {
            $userrole[$segggg] = Enroll::where('user_id', $request->user()->id)->where('course_segment', $segggg)->pluck('role_id')->first();
            $CourseSeg[] = CourseSegment::where('id', $segggg)->get();
        }
        $clase = array();
        $lessons = null;
        $i = 0;
        $count = 'Counter';
        $lessoncounter = array();
        $comp = Component::where('type', 1)->orWhere('type', 3)->get();
        foreach ($CourseSeg as $seg) {
            $role = $userrole[$seg[0]['id']];
            $lessons = $seg->first()->lessons;
            foreach ($seg->first()->segmentClasses as $key => $segmentClas) {
                # code...
                foreach ($segmentClas->classLevel as $key => $classlev) {
                    # code...
                    foreach ($classlev->classes as $key => $class) {
                        # code...
                        $clase[$i] = $class;
                        $clase[$i]->lessons = $lessons;
                        foreach ($clase[$i]->lessons as $lessonn) {
                            $lessoncounter = Lesson::find($lessonn->id);
                            foreach ($comp as $com) {
                                $Component = $lessoncounter->module($com->module, $com->model);
                                if ($request->user()->can('site/course/student')) {
                                    $Component->where('visible', '=', 1);
                                        // ->where('publish_date', '<=', Carbon::now());
                                }
                                //&& $com->model != 'assignment' 
                                if($com->model != 'quiz'){ 
                                    if($role == 3){
                                        $Component->where('publish_date', '<=', Carbon::now()); 
                                    }
                                }

                                $lessonn[$com->name] = $Component->get();
                                foreach($lessonn[$com->name] as $le){
                                    $le['course_id']=(int)$request->course_id;
                                    if($le->pivot->media_id)
                                    {
                                        $le['item_lesson_id']=MediaLesson::where('media_id',$le->id)->where('lesson_id',$le->pivot->lesson_id)->pluck('id')->first();
                                    }
                                    if($le->pivot->file_id)
                                    {
                                        $le['item_lesson_id']=FileLesson::where('file_id',$le->id)->where('lesson_id',$le->pivot->lesson_id)->pluck('id')->first();
                                    }
                                    if($le->pivot->page_id)
                                    {
                                        $le['item_lesson_id']=pageLesson::where('page_id',$le->id)->where('lesson_id',$le->pivot->lesson_id)->pluck('id')->first();
                                    }
                                }
                                if($com->name == 'Quiz'){
                                 foreach ($lessonn['Quiz'] as $one){   
                                    $one['item_lesson_id']=QuizLesson::where('quiz_id',$one->id)->where('lesson_id',$one->pivot->lesson_id)->pluck('id')->first();
                                    if($one->pivot->publish_date > Carbon::now() &&  $request->user()->can('site/course/student'))
                                        $one->Started = false;
                                        else
                                        $one->Started = true;
                                 }
                                }
                                if($com->name == 'Assigments'){
                                    foreach ($lessonn['Assigments'] as $one){
                                        $assignLesson = AssignmentLesson::where('assignment_id',$one->pivot->assignment_id)->where('lesson_id',$one->pivot->lesson_id)
                                        ->pluck('id')->first();
                                        $override_satrtdate = assignmentOverride::where('user_id',Auth::user()->id)->where('assignment_lesson_id',$assignLesson)->pluck('start_date')->first();
                                        $one->start_date = AssignmentLesson::where('assignment_id',$one->pivot->assignment_id)->where('lesson_id',$one->pivot->lesson_id)
                                        ->pluck('start_date')->first();
                                       if($one->start_date > Carbon::now() &&  $request->user()->can('site/course/student'))
                                           $one->Started = false;
                                           else
                                           $one->Started = true;
                                        
                                        if($override_satrtdate != null){
                                            $one->start_date = $override_satrtdate;
                                            // $one->pivot->publish_date = $override_satrtdate;
                                            if($one->start_date > Carbon::now() &&  $request->user()->can('site/course/student'))
                                                $one->Started = false;
                                            else
                                                $one->Started = true;
                                        } 
                                        $one['item_lesson_id']=AssignmentLesson::where('assignment_id',$one->id)->where('lesson_id',$one->pivot->lesson_id)->pluck('id')->first();
                                    }
                                }

                                // $lessonn[$com->name][$com->name . $count] =  count($lessonn[$com->name]);
                                // if (isset($com->name))
                                //     $clase[$i][$com->name . $count] += count($lessonn[$com->name]);
                            }
                        }
                        $i++;
                    }
                }
            }
        }
        return HelperController::api_response_format(200, $clase);
    }

    /**
     * get course by year type
     *
     * @return [object] course by year_type then year_level then class_level then segment_class then course_segment
     */
    public function get_course_by_year_type(Request $request)
    {
        $academic_year_type = array();
        $academic_year_type = null;
        if (isset($request->type_id) && !isset($request->year_id)) {
            $academic_year_type[] = AcademicYearType::where('academic_type_id', $request->type_id)->pluck('id');
        } else if (!isset($request->type_id) && isset($request->year_id)) {
            $academic_year_type[] = AcademicYearType::where('academic_year_id', $request->year_id)->pluck('id');
        } else if (isset($request->type_id) && isset($request->year_id)) {
            $academic_year_type[] = AcademicYearType::checkRelation($request->year_id, $request->type_id)->id;
        }
        return CourseController::get_year_level($request, $academic_year_type);
    }
    public static function get_year_level(Request $request, $academic_year_type)
    {
        $year_level = array();
        $year_level = null;
        if (isset($request->level_id) && !isset($academic_year_type)) {
            $year_level[] = YearLevel::where('level_id', $request->level_id)->pluck('id');
        } else if (!isset($request->level_id) && isset($academic_year_type)) {
            foreach ($academic_year_type as $ac) {
                $year_level[] = YearLevel::where('academic_year_type_id', $ac)->pluck('id');
            }
        } else if (isset($request->level_id) && isset($academic_year_type)) {
            foreach ($academic_year_type as $ac) {
                $year_level[] = YearLevel::checkRelation($ac, $request->level_id)->id;
            }
        }
        return CourseController::get_class_level($request, $year_level);
    }
    public static function get_class_level(Request $request, $year_level)
    {
        $class_level = array();
        $class_level = null;
        if (isset($request->class_id) && !isset($year_level)) {
            $class_level[] = ClassLevel::where('class_id', $request->class_id)->pluck('id');
        } else if (!isset($request->class_id) && isset($year_level)) {
            foreach ($year_level as $ac) {
                $class_level[] = ClassLevel::where('year_level_id', $ac)->pluck('id');
            }
        } else if (isset($request->class_id) && isset($year_level)) {
            foreach ($year_level as $ac) {
                $class_level[] = ClassLevel::checkRelation($request->class_id, $ac)->id;
            }
        }
        return CourseController::get_segment_class_level($request, $class_level);
    }
    public static function get_segment_class_level(Request $request, $class_level)
    {
        $segment_class = array();
        $segment_class = null;
        if (isset($request->segment_id) && !isset($class_level)) {
            $segment_class[] = SegmentClass::where('segment_id', $request->segment_id)->pluck('id');
        } else if (!isset($request->segment_id) && isset($class_level)) {
            foreach ($class_level as $ac) {
                $segment_class[] = SegmentClass::where('class_level_id', $ac)->pluck('id');
            }
        } else if (isset($request->segment_id) && isset($class_level)) {
            foreach ($class_level as $ac) {
                $segment_class[] = SegmentClass::checkRelation($ac, $request->segment_id)->id;
            }
        }
        return CourseController::get_course_segment_level($segment_class);
    }
    public static function get_course_segment_level($segment_class)
    {
        $course_segment = array();
        $course_segment = null;
        if (isset($segment_class)) {
            foreach ($segment_class as $sc) {
                $course_segment[] = CourseSegment::where('segment_class_id', $sc)->pluck('course_id');
            }
        }
        return $course_segment;
    }

    /**
     * get optional course
     *
     * // Request
     * @param  [int..id] year, type, level, class, segment
     * @return if No current segment or year [string] There is no current segment or year
     * @return if No optional COurse in these corse_segment  [string] There is no optional coures here
     * @return [object] courses optional
     */
    public function getCoursesOptional(Request $request)
    {
        $test = 0;
        $course_segment = HelperController::Get_Course_segment($request);
        if ($course_segment == null)
            return HelperController::api_response_format(404, 'There is no current segment or year');
        else {
            if (!$course_segment['result'])
                return HelperController::api_response_format(400, $course_segment['value']);
            foreach ($course_segment['value'] as $cs) {
                if (count($cs->optionalCourses) <= 0)
                    continue;
                else {
                    $optional[] = $cs->optionalCourses[0];
                    $test += 1;
                }
            }
            if ($test > 0)
                return HelperController::api_response_format(200, $optional);
            else
                return HelperController::api_response_format(200,null, 'there is no course optional here');
        }
    }

    /**
     * ToggleCourseLetter .. update course IS letter or not
     *
     * @param  [array..id] year, type, level, class, segment, course
     * @return [object] course Assigned
     */
    public function Assgin_course_to(Request $request)
    {
        $rules = [
            'year' => 'array',
            'year.*' => 'exists:academic_years,id',
            'type' => 'array',
            'type.*' => 'required|exists:academic_types,id',
            'level' => 'array',
            'level.*' => 'required|exists:levels,id',
            'class' => 'array',
            'class.*' => 'required|exists:classes,id',
            'segment' => 'array',
            'segment.*' => 'exists:segments,id',
            'course' => 'required|exists:courses,id',
            'start_date' => 'required|date',
            'end_date' =>'required|date|after:start_date'
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails())
            return ['result' => false, 'value' => $validator->errors()];
        $no_of_lessons = 4;
        if ((count($request->type) == count($request->level)) && (count($request->level) == count($request->segment))) {
            foreach ($request->class as $class) {
                $count = 0;
                while (isset($request->segment[$count])) {
                    if (isset($request->year[$count]))
                        $year = $request->year[$count];
                    else {
                        $year = AcademicYear::Get_current();
                        if ($year == null)
                            return HelperController::api_response_format(404, 'There is no current segment or year');
                        else
                            $year = $year->id;
                    }
                    if (isset($request->segment[$count]))
                        $segment = $request->segment[$count];
                    else {
                        $segment = Segment::Get_current($request->type[$count])->id;
                        if ($segment == null)
                            return HelperController::api_response_format(404, 'There is no current segment or year');
                        else
                            $segment = $segment->id;
                    }
                    $academic_year_type = AcademicYearType::checkRelation($year, $request->type[$count]);
                    $year_level = YearLevel::checkRelation($academic_year_type->id, $request->level[$count]);
                    $class_level = ClassLevel::checkRelation($class, $year_level->id);
                    $segment_class = SegmentClass::checkRelation($class_level->id, $segment);
                    $course_Segment = CourseSegment::checkRelation($segment_class->id, $request->course);
                    $courseSegment = CourseSegment::where('id',$course_Segment->id)->update([
                        'start_date' => $request->start_date,
                        'end_date' => $request->end_date,
                    ]);
                    if ($request->filled('no_of_lessons')) {
                        $no_of_lessons = $request->no_of_lessons;
                    }
                    for ($i = 1; $i <= $no_of_lessons; $i++) {
                        $courseSegment->lessons()->create([
                            'name' => 'Lesson ' . $i,
                            'index' => $i,
                        ]);
                    }
                    $gradeCat = GradeCategory::firstOrCreate([
                        'name' => 'Course Total',
                        'course_segment_id' => $course_Segment->id,
                        'id_number' => $year_level->id
                    ]);
                    $count++;
                }
            }
        } else {
            return HelperController::api_response_format(201, 'Please Enter Equal number of array');
        }
        return HelperController::api_response_format(201, 'Course Assigned Successfully');
    }

    /**
     * get sorted lessons of user
     *
     * @param  [int] course_id, class_id
     * @return [object] sorted lessons
     */
    public function GetUserCourseLessonsSorted(Request $request)
    {
        $request->validate([
            'course_id' => 'required|exists:course_segments,course_id',
            'class_id' => 'exists:classes,id',
        ]);
        $result = [];
        $courseSegments = CourseSegment::whereIn('id' , Auth::user()->enroll->pluck('course_segment'))->where('course_id',$request->course_id)->get();

        if($request->filled('class_id')){
            $course_Segment = CourseSegment::GetWithClassAndCourse($request->class_id,$request->course_id);
            $courseSegments=[CourseSegment::find($course_Segment->id)];
        }
        $j = 0;
        foreach($courseSegments as $courseSegment){
            if ($courseSegment != null) {
                $result[$j] = [];
                $i = 0;
                foreach ($courseSegment->lessons as $lesson) {
                    $components = LessonComponent::whereLesson_id($lesson->id)->get();
                    $result[$j][$i]['name'] = $lesson->name;
                    $result[$j][$i]['LessonID'] =$lesson->id;
                    
                    $class=Classes::find(Lesson::find($lesson->id)->courseSegment->segmentClasses[0]->classLevel[0]->class_id);
                    $result[$j][$i]['ClassName']=$class->name ;
                    $result[$j][$i]['ClassID']= $class->id;

                    $result[$j][$i]['data'] = [];
                    foreach ($components as $component) {
                        $temp = $lesson->module($component->module, $component->model);
                        if ($request->user()->can('site/course/student')) {
                            $temp->where('visible', '=', 1)
                                ->where('publish_date', '<=', Carbon::now());
                        }
                        if(count($temp->get()) == 0)
                            continue;
                        $tempBulk = $temp->get();
                        foreach($tempBulk as $item){
                            $item->flag = $component->model;
                            if(!in_array($item, $result[$j][$i]['data']))
                                $result[$j][$i]['data'][] = $item;
                        }
                    }
                    $i++;
                }
            }
            $j++;
        }
        return HelperController::api_response_format(200, $result , null);
    }

    /**
     * ToggleCourseLetter .. update course IS letter or not
     *
     * @param [boolean] letter
     * @param [int] letter_id, course, class
     * @return [object] course with toggled or not
     */
    public function ToggleCourseLetter(Request $request)
    {
        $request->validate([
            'letter' => 'required|boolean',
            'letter_id' => 'exists:letters,id|required_if:letter,==,1',
            'course'  => 'required|exists:courses,id',
            'class'  => 'required|exists:classes,id',
        ]);

        $coursesSegment = CourseSegment::GetWithClassAndCourse($request->class, $request->course);
        if (isset($coursesSegment)) {
            $course = CourseSegment::find($coursesSegment->id);
            $course->update([
                'letter' => $request->letter,
                'letter_id' => $request->letter_id,
            ]);

            return HelperController::api_response_format(201, $course, 'Toggled Success');
        } else
            return HelperController::api_response_format(201, 'There is no Active Course Segment');
    }

    public function EnrolledCourses(Request $request)
    {
        $i = 0;
        foreach ($request->user()->enroll as $enroll) {
            $all[$i]['id'] = $enroll->CourseSegment->id;
            $segment_Class_id = CourseSegment::where('id', $enroll->CourseSegment->id)->get(['segment_class_id', 'course_id'])->first();
            $all[$i]['Course'] = Course::where('id', $segment_Class_id->course_id)->pluck('name')->first();
            $segment = SegmentClass::where('id', $segment_Class_id->segment_class_id)->get(['segment_id', 'class_level_id'])->first();

            $class_id = ClassLevel::where('id', $segment->class_level_id)->get(['class_id', 'year_level_id'])->first();
            $all[$i]['class'] = Classes::find($class_id->class_id)->name;
            $i++;
        }
        if (isset($all))
            return HelperController::api_response_format(200, $all);

        return HelperController::api_response_format(200, null, 'there is no courses');
    }
    public function Count_Components(Request $request)
    {
        $request->validate([
            'course'  => 'required|exists:courses,id',
            'class'  => 'integer|exists:classes,id',
        ]);
        if (isset($request->class)) {
            $course_segments = [CourseSegment::GetWithClassAndCourse($request->class, $request->course)->id];
        } else {
            $course_segments = CourseSegment::where('course_id', $request->course)->where('is_active', '1')->pluck('id');
        }
        if (!isset($course_segments)) {
            return HelperController::api_response_format(400, null, 'doesn\'t have course segment ');
        }
        $lessons_id = Lesson::whereIn('course_segment_id', $course_segments)->pluck('id');
        $components =  LessonComponent::whereIn('lesson_id', $lessons_id)
            ->select('model as name', DB::raw('count(*) as total'))
            ->groupBy('model')
            ->get();
        return HelperController::api_response_format(200, $components, 'component are ...');
    }

    public function getAllCoursesWithChain(Request $request)
    {
        $courseSegments = GradeCategoryController::getCourseSegment($request);
        if ($courseSegments == null)
            return HelperController::api_response_format(400, null, 'No Courses In this Chain');
        $courses = CourseSegment::whereIn('id', $courseSegments)->paginate(HelperController::GetPaginate($request));
        foreach ($courses as $item) {
            $item->name     = $item->courses[0]->name;
            $item->year     = $item->segmentClasses[0]->classLevel[0]->yearLevels[0]->yearType[0]->academicyear[0]->name;
            $item->type     = $item->segmentClasses[0]->classLevel[0]->yearLevels[0]->yearType[0]->academictype[0]->name;
            $item->level    = $item->segmentClasses[0]->classLevel[0]->yearLevels[0]->levels[0]->name;
            $item->class    = $item->segmentClasses[0]->classLevel[0]->classes[0]->name;
            $item->segment  = $item->segmentClasses[0]->segments[0]->name;
            $item->attachment = $item->courses[0]->attachment;
            unset($item->courses);
            unset($item->segmentClasses);
        }
        return HelperController::api_response_format(400, $courses);
    }

    public function getAllMyComponenets(Request $request)
    {
        $request->validate([
            'course'    => 'nullable|integer|exists:courses,id',
            'start'     => 'nullable|date',
            'end'       => 'nullable|date',
            'components' => 'nullable|array',
            'components.*' => 'required|integer|exists:components,id',
            'timeline' => 'integer',
            'assort' => 'string|in:name,due_date,course',
            'order' => 'string|in:asc,desc',
            'quick_action' => 'integer|in:1',
            'flag' => 'string|in:page,media,file'
        ]);
        // $components  = Component::where('active', 1)->whereIn('type', [3,1])->where('name','not like', "%page%");
        $components  = Component::where('active', 1)->whereIn('type', [3,1]);
        if ($request->filled('components')) {
            $components->whereIn('id', $request->components);
        };
        $components = $components->get();
        $result = [];
        foreach ($components as $component) {
            $result[$component->name] = [];
        }
        $user = User::whereId($request->user()->id)->with(['enroll.courseSegment' => function ($query) use ($request) {
            if ($request->filled('course'))
                $query->where('course_id', $request->course);
        }])->first();

        foreach ($user->enroll as $enroll) {
            if ($enroll->courseSegment != null) {
                foreach ($enroll->courseSegment->lessons as $lesson) {
                    foreach ($components as $component) {
                        $temp = $lesson->module($component->module, $component->model);

                        if ($request->user()->can('site/course/student')) {
                            $temp->where('visible', '=', 1);
                                // ->where('publish_date', '<=', Carbon::now());
                        }
                        //&& $component->model != 'assignment'
                        if($component->model != 'quiz'){
                            if($enroll['role_id'] == 3){
                                $temp->where('publish_date', '<=', Carbon::now());
                            }
                        }
                        if(count($temp->get()) == 0)
                            continue;
                        $tempBulk = $temp->get();

                        foreach($tempBulk as $item){
                            
                            if(isset($item->pivot))
                            {
                                $item->course = Course::find(Lesson::find($item->pivot->lesson_id)->courseSegment->course_id);
                                $item->class= Classes::find(Lesson::find($item->pivot->lesson_id)->courseSegment->segmentClasses[0]->classLevel[0]->class_id);
                                $item->level = Level::find(Lesson::find($item->pivot->lesson_id)->courseSegment->segmentClasses[0]->classLevel[0]->yearLevels[0]->level_id);
                                if($item->pivot->quiz_id){
                                    $item->due_date = QuizLesson::where('quiz_id',$item->pivot->quiz_id)->where('lesson_id',$item->pivot->lesson_id);
                                    if(isset($request->timeline) && $request->timeline == 1 ){
                                        $item->due_date->where('due_date','>=',Carbon::now());
                                    }
                                    $item->due_date=  $item->due_date->pluck('due_date')->first();
                                    if(!isset ($item->due_date)){
                                        continue;
                                    }
                                    if($item->pivot->publish_date > Carbon::now() &&  $request->user()->can('site/course/student'))
                                    $item->Started = false;
                                    else
                                    $item->Started = true;
                                }
                                if($item->pivot->assignment_id)
                                 {   $item->due_date = AssignmentLesson::where('assignment_id',$item->pivot->assignment_id)->where('lesson_id',$item->pivot->lesson_id);
                                    if(isset($request->timeline) && $request->timeline == 1 ){
                                        $item->due_date->where('due_date','>=',Carbon::now());
                                    }
                                    $item->due_date=  $item->due_date->pluck('due_date')->first();
                                    if(!isset ($item->due_date)){
                                        continue;
                                    }
                                    $item->start_date = AssignmentLesson::where('assignment_id',$item->pivot->assignment_id)->where('lesson_id',$item->pivot->lesson_id)
                                    ->pluck('start_date')->first();
                                    if($item->start_date > Carbon::now() &&  $request->user()->can('site/course/student'))
                                    $item->Started = false;
                                    else
                                    $item->Started = true;
                                }
                                // $quickaction =collect([]);
                                if($item->pivot->media_id)
                                {
                                    $item['flag'] = 'media';
                                    $quickaction[]=$item;
                                }
                                if($item->pivot->file_id)
                                {
                                    $item['flag'] = 'file';
                                    $quickaction[]=$item;
                                }
                                if($item->pivot->page_id)
                                {
                                    $item['flag'] = 'page';
                                    $quickaction[]=$item;
                                }
                                    $result[$component->name][] = $item;
                            }
                        }
                    }
                }
            }
        }
        //quick actions
        if($request->quick_action == 1){
            //will be order in desc awl ma yft7 bl due date
            $quick = collect($quickaction);
            $i=0;
            foreach($quick as $mm){
                    $quick_sort[$i]['id'] = $mm->id;
                    $quick_sort[$i]['date'] = $mm->pivot->publish_date; 
                    $i++;
                }
                $a = collect($quick_sort)->sortByDesc('date')->values();
                $j=0;
                foreach($a as $as)
                {
                    $tryyyy [$j]= collect($quickaction)->where('id', $as['id'])->values()[0];
                    $j++;
                }
            $quick = $tryyyy;
            $quickaction   = $quick;
            //in case date -> asc and desc
            if($request->order == 'asc'){
                $quickasc = collect($quickaction);
                $l=0;
                foreach($quickasc as $mm){
                        $quickasc_sort[$l]['id'] = $mm->id;
                        $quickasc_sort[$l]['date'] = $mm->pivot->publish_date; 
                        $l++;
                    }
                    $a = collect($quickasc_sort)->sortBy('date')->values();
                    $m=0;
                    foreach($a as $as)
                    {
                        $tryyy [$m]= collect($quickaction)->where('id', $as['id'])->values()[0];
                        $m++;
                    }
                $quickasc = $tryyy;
                $quickaction   = $quickasc;
            }
            //in case category -> file ,media and page
            if(isset($request->flag))
            {
                $quickaction = collect($quickaction)->where('flag',$request->flag)->values();
            }
            return HelperController::api_response_format(200,$quickaction);
            
        }
        //sort assignments and quiz bt due_date
        if(isset($result["Assigments"])){
            $assignmet = collect($result["Assigments"])->sortByDesc('due_date');
            $ass=collect();
            foreach($assignmet as $item){
                $assignLesson = AssignmentLesson::where('assignment_id',$item->pivot->assignment_id)->where('lesson_id',$item->pivot->lesson_id)->pluck('id')->first();
                $override = assignmentOverride::where('user_id',Auth::user()->id)->where('assignment_lesson_id',$assignLesson)->first();
                if($override != null){
                    $item->start_date = $override->start_date;
                    $item->due_date = $override->due_date;
                    // $item->pivot->publish_date = $override->start_date;
                    if($item->start_date > Carbon::now() &&  $request->user()->can('site/course/student'))
                        $item->Started = false;
                    else
                        $item->Started = true;
                }
                $ass[] = $item;
            }
            $ass = collect($ass)->sortByDesc('due_date');

            if(isset($request->assort)){
                if($request->assort == 'course'){
                    $i=0;
                    foreach($ass as $assign){
                        $course_sort[$i]['id'] = $assign->id;
                        $course_sort[$i]['name'] = $assign->course->name; 
                        $i++;
                    }
                    if($request->order == 'asc')
                        $a = collect($course_sort)->sortBy('name')->values();
                    else
                        $a = collect($course_sort)->sortByDesc('name')->values();

                    $j=0;
                    foreach($a as $as)
                    {
                        $try [$j]= collect($result["Assigments"])->where('id', $as['id'])->values()[0];
                        $j++;
                    }
                     $ass = $try;
                }
                else{
                    if($request->order == 'asc')
                        $ass = collect($ass)->sortBy($request->assort)->values();
                    else
                        $ass = collect($ass)->sortByDesc($request->assort)->values();
                }
            }
                $result['Assigments']   = $ass;  
        }
        if(isset($result["Quiz"])){

          $quizzesSorted = collect($result["Quiz"])->sortByDesc('due_date');
          $quiz=collect();
          
          foreach($quizzesSorted as $q){
            $quizLesson = QuizLesson::where('quiz_id',$item->pivot->quiz_id)->where('lesson_id',$item->pivot->lesson_id)->pluck('id')->first();
            $override = QuizOverride::where('user_id',Auth::user()->id)->where('quiz_lesson_id',$quizLesson)->first();
            if($override != null){
                $item->start_date = $override->start_date;
                $item->due_date = $override->due_date;
                if($item->pivot->publish_date > Carbon::now() &&  $request->user()->can('site/course/student'))
                    $item->Started = false;
                else
                    $item->Started = true;
            }
            $quiz[] = $q;
            }

            if(isset($request->assort)){
                if($request->assort == 'course'){
                    $i=0;
                    foreach($quiz as $qu){
                        $course_sort[$i]['id'] = $qu->id;
                        $course_sort[$i]['name'] = $qu->course->name; 
                        $i++;
                    }
                    if($request->order == 'asc')
                        $a = collect($course_sort)->sortBy('name')->values();
                    else
                        $a = collect($course_sort)->sortByDesc('name')->values();

                    $j=0;
                    foreach($a as $as)
                    {
                        $try [$j]= collect($result["Quiz"])->where('id', $as['id'])->values()[0];
                        $j++;
                    }
                     $quiz = $try;
                }
                else{
                    if($request->order == 'asc')
                        $quiz = collect($result["Quiz"])->sortBy($request->assort)->values();
                    else
                        $quiz = collect($result["Quiz"])->sortByDesc($request->assort)->values();
                }
            }

            $result['Quiz']   = $quiz;  
        }
        return HelperController::api_response_format(200,$result);
    }
    public function getLessonsFromCourseAndClass(Request $request){
        $validator =  Validator::make($request->all() , [
            'class'  => 'required|exists:classes,id',
            'course' => 'required|exists:courses,id',
        ]);
        if($validator->fails())
            return HelperController::api_response_format(200, $validator->errors());
        $courseSegment = CourseSegment::GetWithClassAndCourse($request->class , $request->course);
        if($courseSegment == null)
            return HelperController::api_response_format(200, null, 'This Course have no activated to this class');
        return HelperController::api_response_format(200, $courseSegment->lessons);
    }
    public function get_class_from_course(Request $request){
        $request->validate([
            'course_id' => 'required|exists:course_segments,course_id'
        ]);
        $CourseSeg = Enroll::where('user_id', Auth::id())->pluck('course_segment');
        $seggg = array();
        foreach ($CourseSeg as $cour) {
            $check = CourseSegment::where('course_id', $request->course_id)->where('id', $cour)->pluck('id')->first();
            if ($check != null) {
                $seggg[] = $check;
            }
        }
        $CourseSeg = array();
        foreach ($seggg as $segggg) {
            $CourseSeg[] = CourseSegment::where('id', $segggg)->get();
        }
        $classs = array();
        $i = 0;       
        foreach ($CourseSeg as $seg) {
            $lessons = $seg->first()->lessons;
            foreach ($seg->first()->segmentClasses as $key => $segmentClas) {
                foreach ($segmentClas->classLevel as $key => $classlev) {
                    foreach ($classlev->classes as $key => $class) {
                        $classs[$i] = $class;
                        $i++;
                    }
                }
            }
        }
        return HelperController::api_response_format(200,$classs,'classes are.....');
    }

    public function get_courses_with_classes(Request $request){
        $request->validate([
            'class' => 'array',
            'class.*' => 'nullable|exists:classes,id',
        ]);
        
        if($request->filled('class'))
        {
            $courseId=[];
            $allcourses=collect([]);
            $final=collect([]);
            $i=0;
            foreach($request->class as $cId)
            {
                $courseId[$i]=CourseSegment::Join('segment_classes' , 'segment_classes.id' , 'course_segments.segment_class_id')
                ->Join('class_levels' , 'class_levels.id' , 'segment_classes.class_level_id')
                ->where('class_levels.class_id' , '=' , $cId)
                ->where('course_segments.is_active' , '=' , 1)->pluck('course_id');
                $i++;
            }

            foreach($courseId as $cou)
            {
                $allcourses->push(Course::whereIn('id' , $cou)->get());
            }
            foreach($allcourses as $all)
            {
                foreach($all as $a)
                {
                    $final->push($a);
                }
            }
            return HelperController::api_response_format(200,$final->unique()->values(),'Here is all courses Linked to provided Classes');
        }

        $courses=Course::get();
        return HelperController::api_response_format(200,$courses,'Here is all courses');
    }
}
