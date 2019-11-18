<?php

namespace App\Http\Controllers;

use App\AcademicYearType;
use App\ClassLevel;
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
use App\AcademicType;
use App\attachment;
use App\LessonComponent;
use App\User;
use Carbon\Carbon;
use App\Letter;
use Illuminate\Support\Facades\Validator;
use stdClass;

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
            'category' => 'required|exists:categories,id',
            'year' => 'array|required',
            'year.*' => 'required|exists:academic_years,id',
            'type' => 'array|required',
            'type.*' => 'required|exists:academic_types,id',
            'level' => 'array|required',
            'level.*' => 'required|exists:levels,id',
            'class' => 'array|required',
            'class.*' => 'required|exists:classes,id',
            'segment' => 'array|required',
            'segment.*' => 'required|exists:segments,id',
            'no_of_lessons' => 'integer',
            'image' => 'file|distinct|mimes:jpg,jpeg,png,gif',
            'description' => 'string',
            'mandatory' => 'nullable',
            'typical' => 'nullable|boolean'
        ]);
        $no_of_lessons = 4;
        $course = Course::firstOrCreate([
            'name' => $request->name,
            'category_id' => $request->category,
        ]);

        // if course has an image
        if ($request->hasFile('image')) {
            $course->image = attachment::upload_attachment($request->image, 'course')->id;
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
                                'typical' => $request->typical
                            ]);
                            $gradeCat = GradeCategory::firstOrCreate([
                                'name' => 'Course Total',
                                'course_segment_id' => $courseSegment->id,
                            ]);
                            if ($request->filled('no_of_lessons')) {
                                $no_of_lessons = $request->no_of_lessons;
                            }

                            for ($i = 1; $i <= $no_of_lessons; $i++) {
                                $courseSegment->lessons()->firstOrCreate([
                                    'name' => 'Lesson ' . $i,
                                    'index' => $i,
                                ]);
                            }
                        }
                    }
                }
            }
        }

        $course->attachment;
        return HelperController::api_response_format(201, Course::with(['category' , 'attachment'])->paginate(HelperController::GetPaginate($request)), 'Course Created Successfully');
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
        $editable = ['name' , 'category_id' ,'description' , 'mandatory'];
        $course = Course::find($request->id);
        $course->name = $request->name;
        $course->category_id = $request->category;

        // if course has an image
        if ($request->hasFile('image')) {
            $course->image=attachment::upload_attachment($request->image, 'course')->id;
        }
        foreach ($editable as $key) {
            if($request->filled($key)){
                $course->$key = $request->$key;
            }
        }
        $course->save();
        return HelperController::api_response_format(200, Course::with(['category' , 'attachment'])->paginate(HelperController::GetPaginate($request)), 'Course Updated Successfully');
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
        if($request->filled('year')){
            $academic_year_type = AcademicYearType::checkRelation($request->year, $request->type);
            $year_level = YearLevel::checkRelation($academic_year_type->id, $request->level);
            $class_level = ClassLevel::checkRelation($request->class, $year_level->id);
            if ($request->filled('segment'))
                    $segment = $request->segment;
                else
                    $segment = Segment::Get_current($request->type)->id;
            $segment_class = SegmentClass::checkRelation($class_level->id, $segment);
            $courseSegment = $segment_class->courseSegment->pluck('course_id');
            return HelperController::api_response_format(200, Course::with(['category' , 'attachment'])->whereIn('id' , $courseSegment)->paginate(HelperController::GetPaginate($request)));
        }
        if (isset($request->id))
            return HelperController::api_response_format(200, Course::with(['category' , 'attachment'])->whereId($request->id)->first());
        return HelperController::api_response_format(200, Course::with(['category' , 'attachment'])->where('name', 'LIKE' , "%$request->search%")->get()
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
        return HelperController::api_response_format(200, Course::with(['category' , 'attachment'])->paginate(HelperController::GetPaginate($request)), 'Course Deleted Successfully');
    }


    /**
     * get current enrolledCourses
     *
     * @return [object] currenr course_name with teacher and category with all chain
    */
    public function CurrentCourses(Request $request)
    {
        $all = collect();
        foreach ($request->user()->enroll as $enroll) {
            if($enroll->CourseSegment->end_date > Carbon::now() && $enroll->CourseSegment->start_date < Carbon::now()) {
                $segment_Class_id=CourseSegment::where('id',$enroll->CourseSegment->id)->get(['segment_class_id','course_id'])->first();
                $course=Course::where('id',$segment_Class_id->course_id)->with(['category' , 'attachment'])->first();
                $segment=SegmentClass::where('id',$segment_Class_id->segment_class_id)->get(['segment_id','class_level_id'])->first();
                $flag = new stdClass();
                $flag->segment = Segment::find($segment->segment_id)->name;
                $class_id=ClassLevel::where('id',$segment->class_level_id)->get(['class_id','year_level_id'])->first();
                $flag->class = Classes::find($class_id->class_id)->name;
                $level_id=YearLevel::where('id',$class_id->year_level_id)->get(['level_id','academic_year_type_id'])->first();
                $flag->level = Level::find($level_id->level_id)->name;
                $AC_type=AcademicYearType::where('id',$level_id->academic_year_type_id)->get(['academic_year_id','academic_type_id'])->first();
                $flag->year =AcademicYear::find($AC_type->academic_type_id)->name;
                $flag->type = AcademicYear::find($AC_type->academic_type_id)->name;
                $teacher = User::whereId(Enroll::where('role_id', '4')->where('course_segment', $enroll->CourseSegment->id)->pluck('user_id'))->get(['id', 'username', 'firstname', 'lastname', 'picture'])[0];
                $teacher->class = $enroll->CourseSegment->segmentClasses[0]->classLevel[0]->classes[0];
                $course->flag = $flag;
                $course->teacher = $teacher;
                $all->push($course);
            }
        }
        if(isset($all))
            return HelperController::api_response_format(200, (new Collection($all))->paginate(HelperController::GetPaginate($request)));

        return HelperController::api_response_format(200, null,'there is no courses');
    }

    /**
     * get past enrolledCourses
     *
     * @return [object] past courses_name with teacher and category with all chain
    */
    public function PastCourses(Request $request)
    {   $all = collect();
        $i=0;
        foreach ($request->user()->enroll as $enroll) {
            if($enroll->CourseSegment->end_date < Carbon::now() && $enroll->CourseSegment->start_date < Carbon::now()) {
                $segment_Class_id=CourseSegment::where('id',$enroll->CourseSegment->id)->get(['segment_class_id','course_id'])->first();
                $course=Course::where('id',$segment_Class_id->course_id)->with(['category' , 'attachment'])->first();
                $segment=SegmentClass::where('id',$segment_Class_id->segment_class_id)->get(['segment_id','class_level_id'])->first();
                $flag = new stdClass();
                $flag->segment = Segment::find($segment->segment_id)->name;
                $class_id=ClassLevel::where('id',$segment->class_level_id)->get(['class_id','year_level_id'])->first();
                $flag->class = Classes::find($class_id->class_id)->name;
                $level_id=YearLevel::where('id',$class_id->year_level_id)->get(['level_id','academic_year_type_id'])->first();
                $flag->level = Level::find($level_id->level_id)->name;
                $AC_type=AcademicYearType::where('id',$level_id->academic_year_type_id)->get(['academic_year_id','academic_type_id'])->first();
                $flag->year =AcademicYear::find($AC_type->academic_type_id)->name;
                $flag->type = AcademicYear::find($AC_type->academic_type_id)->name;
                $teacher = User::whereId(Enroll::where('role_id', '4')->where('course_segment', $enroll->CourseSegment->id)->pluck('user_id'))->get(['id', 'username', 'firstname', 'lastname', 'picture'])[0];
                $teacher->class = $enroll->CourseSegment->segmentClasses[0]->classLevel[0]->classes[0];
                $course->flag = $flag;
                $course->teacher = $teacher;
                $all->push($course);
            }
        }
        if(isset($all))
            return HelperController::api_response_format(200, $all);

        return HelperController::api_response_format(200, null,'there is no courses');
    }

    /**
     * get future enrolledCourses
     *
     * @return [object] future courses_name with teacher and category with all chain
    */
    public function FutureCourses(Request $request)
    {
        $all=collect();
        $i=0;
        foreach ($request->user()->enroll as $enroll) {
            if($enroll->CourseSegment->end_date > Carbon::now() && $enroll->CourseSegment->start_date > Carbon::now()) {
                $segment_Class_id=CourseSegment::where('id',$enroll->CourseSegment->id)->get(['segment_class_id','course_id'])->first();
                $course=Course::where('id',$segment_Class_id->course_id)->with(['category' , 'attachment'])->first();
                $segment=SegmentClass::where('id',$segment_Class_id->segment_class_id)->get(['segment_id','class_level_id'])->first();
                $flag = new stdClass();
                $flag->segment = Segment::find($segment->segment_id)->name;
                $class_id=ClassLevel::where('id',$segment->class_level_id)->get(['class_id','year_level_id'])->first();
                $flag->class = Classes::find($class_id->class_id)->name;
                $level_id=YearLevel::where('id',$class_id->year_level_id)->get(['level_id','academic_year_type_id'])->first();
                $flag->level = Level::find($level_id->level_id)->name;
                $AC_type=AcademicYearType::where('id',$level_id->academic_year_type_id)->get(['academic_year_id','academic_type_id'])->first();
                $flag->year =AcademicYear::find($AC_type->academic_type_id)->name;
                $flag->type = AcademicYear::find($AC_type->academic_type_id)->name;
                $teacher = User::whereId(Enroll::where('role_id', '4')->where('course_segment', $enroll->CourseSegment->id)->pluck('user_id'))->get(['id', 'username', 'firstname', 'lastname', 'picture'])[0];
                $teacher->class = $enroll->CourseSegment->segmentClasses[0]->classLevel[0]->classes[0];
                $course->flag = $flag;
                $course->teacher = $teacher;
                $all->push($course);
            }
        }
        if(isset($all))
            return HelperController::api_response_format(200, $all);

        return HelperController::api_response_format(200, null,'there is no courses');
    }

    /**
     * get course with teacher
     *
     * @return [object] course with teacher
    */
    public function course_with_teacher()
    {
        $teachers = Enroll::where('role_id', '4')->get(['username', 'course_segment']);
        foreach ($teachers as $tech) {
            $coursesegment = CourseSegment::find($tech->course_segment);
            $tech['course'] = $coursesegment->courses;
        }
        return $teachers;
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
        $clase = array();
        $lessons = null;
        $i = 0;
        $count = 'Counter';
        $lessoncounter = array();
        $comp = Component::where('type', 1)->orWhere('type', 3)->get();

        foreach ($CourseSeg as $seg) {
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
                                if (($request->user()->roles->first()->id) == 3 ||($request->user()->roles->first()->id) == 7) {
                                    $Component = $lessoncounter->module($com->module, $com->model)
                                        ->where('visible' , '=' , 1)
                                        ->where('publish_date' , '<=' , Carbon::now())->get();
                                }else{
                                    $Component = $lessoncounter->module($com->module, $com->model)->get();
                                }

                                if($com->name == 'Media' && count($Component)>0 ){
                                    foreach($Component as $media){
                                        $userid = $media->user->id;
                                        $firstname = $media->user->firstname;
                                        $lastname = $media->user->lastname;
                                        $user = collect([
                                            'user_id' => $userid,
                                            'firstname' => $firstname,
                                            'lastname' => $lastname
                                        ]);
                                        unset($media->user);
                                        $media->owner = $user;

                                        $media->mediaType = ($media->type ==null)?'LINK':'MEDIA';
                                    }
                                }
                                else if($com->name == 'Assigments' && count($Component)>0 ){
                                    foreach($Component as $assignment){
                                        if(isset($assignment->attachment)){
                                            $path = $assignment->attachment->path;
                                            $assignment->url = 'https://docs.google.com/viewer?url=' . $path;
                                            $assignment->url2 = $path;
                                        }
                                    }
                                }
                                $lessonn[$com->name] = $Component;

                                //$lessonn[$com->name][$com->name . $count] =  count($lessonn[$com->name]);
                                if (isset($com->name))
                                    $clase[$i][$com->name . $count] += count($lessonn[$com->name]);
                            }
                        }
                        $i++;
                    }
                }
            }
        }
        //$clase['course'] = Course::find($request->course_id);
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
        $test=0;
        $course_segment = HelperController::Get_Course_segment($request);
        if($course_segment == null)
            return HelperController::api_response_format(404, 'There is no current segment or year');

        else {
            if (!$course_segment['result'])
                return HelperController::api_response_format(400, $course_segment['value']);
            foreach ($course_segment['value'] as $cs) {
                if(count($cs->optionalCourses) <= 0)
                    continue;
                else
                {
                    $optional[]=$cs->optionalCourses[0];
                    $test+=1;
                }
            }
            if($test > 0)
                return HelperController::api_response_format(200, $optional);
            else
                return HelperController::api_response_format(200,'there is no course optional here');
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
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails())
            return ['result' => false, 'value' => $validator->errors()];

        $count = 0;
        if ((count($request->type) == count($request->level)) && (count($request->level) == count($request->class))) {
            while (isset($request->class[$count])) {

                if (isset($request->year[$count]))
                    $year = $request->year[$count];
                else
                {
                    $year = AcademicYear::Get_current();
                    if($year == null)
                        return HelperController::api_response_format(404, 'There is no current segment or year');
                    else
                        $year=$year->id;
                }
                if (isset($request->segment[$count]))
                    $segment = $request->segment[$count];
                else
                {
                    $segment = Segment::Get_current($request->type[$count])->id;
                    if($segment == null)
                        return HelperController::api_response_format(404, 'There is no current segment or year');
                    else
                        $segment=$segment->id;
                }
                $academic_year_type = AcademicYearType::checkRelation($year, $request->type[$count]);
                $year_level = YearLevel::checkRelation($academic_year_type->id, $request->level[$count]);
                $class_level = ClassLevel::checkRelation($request->class[$count], $year_level->id);
                $segment_class = SegmentClass::checkRelation($class_level->id, $segment);
                $course_Segment=CourseSegment::checkRelation($segment_class->id, $request->course);
                $gradeCat = GradeCategory::firstOrCreate([
                    'name' => 'Course Total',
                    'course_segment_id' => $course_Segment->id,
                ]);
                $count++;
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
    public function GetUserCourseLessonsSorted(Request $request){
        $request->validate([
            'course_id' => 'required|exists:course_segments,course_id',
            'class_id'  => 'required|exists:classes,id'
        ]);
        $result = [];
        $courseSegment = CourseSegment::GetWithClassAndCourse($request->class_id , $request->course_id);
        if($courseSegment != null){
            foreach ($courseSegment->lessons as $lesson) {
                $components = LessonComponent::whereLesson_id($lesson->id)->get();
                $result[$lesson->name] = [];
                foreach($components as $component){
                    eval('$res = \Modules\\'.$component->module.'\Entities\\'.$component->model.'::find('.$component->comp_id.');');
                    $res->type = $component->model;
                    $result[$lesson->name][] = $res;
                }
            }
        }
        return HelperController::api_response_format(200, $result);
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

        $coursesSegment=CourseSegment::GetWithClassAndCourse($request->class,$request->course);
        if(isset($coursesSegment))
        {
            $course =CourseSegment::find($coursesSegment->id);
            $course->update([
                'letter'=> $request->letter,
                'letter_id'=> $request->letter_id,
            ]);

            return HelperController::api_response_format(201, $course,'Toggled Success');
        }
        else
            return HelperController::api_response_format(201, 'There is no Active Course Segment');
    }

    public function EnrolledCourses(Request $request)
    {
        $i=0;
        foreach ($request->user()->enroll as $enroll) {
                $all[$i]['id']=$enroll->CourseSegment->id;
                $segment_Class_id=CourseSegment::where('id',$enroll->CourseSegment->id)->get(['segment_class_id','course_id'])->first();
                $all[$i]['Course']=Course::where('id',$segment_Class_id->course_id)->pluck('name')->first();
                $segment=SegmentClass::where('id',$segment_Class_id->segment_class_id)->get(['segment_id','class_level_id'])->first();

                $class_id=ClassLevel::where('id',$segment->class_level_id)->get(['class_id','year_level_id'])->first();
                $all[$i]['class']=Classes::find($class_id->class_id)->name;
                $i++;
        }
        if(isset($all))
            return HelperController::api_response_format(200, $all);

        return HelperController::api_response_format(200, null,'there is no courses');
    }
}
