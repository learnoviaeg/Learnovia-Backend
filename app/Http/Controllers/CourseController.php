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
use Modules\QuestionBank\Entities\UserQuiz;
use Modules\QuestionBank\Entities\UserQuizAnswer;
use App\AcademicType;
use App\attachment;
use App\LessonComponent;
use App\User;
use Modules\QuestionBank\Entities\QuizLesson;
use Modules\Assigments\Entities\AssignmentLesson;
use Modules\Assigments\Entities\UserAssigment;
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
use App\Exports\CoursesExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;



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
            // 'short_name' => 'required|unique:courses',
            'category' => 'exists:categories,id',
            'chains.*.year' => 'array',
            'chains.*.year.*' => 'required|exists:academic_years,id',
            'chains.*.type' => 'array|required_with:chains.*.year',
            'chains.*.type.*' => 'exists:academic_types,id',
            'chains.*.level' => 'array|required_with:chains.*.year',
            'chains.*.level.*' => 'required|exists:levels,id',
            'chains.*.class' => 'array',
            'chains.*.class.*' => 'exists:classes,id',
            'chains.*.segment' => 'array|required_with:chains.*.year',
            'chains.*.segment.*' => 'required|exists:segments,id',
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
            // 'short_name' => $request->short_name,
        ]);
        // if course has an image
        if ($request->hasFile('image')) {
            $course->image = attachment::upload_attachment($request->image, 'course')->id;
            $course->save();
        }
        if ($request->filled('category')) {
            $course->category_id = $request->category;
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
        foreach ($request->chains as $chain){
            // dd($chain);
        if(count($chain['year'])>0){
            foreach ($chain['year'] as $year) {
                # code...
                foreach ($chain['type'] as $type) {
                    # code...
                    $yeartype = AcademicYearType::checkRelation($year, $type);
                    foreach ($chain['level'] as $level) {
                        # code...
                        $yearlevel = YearLevel::checkRelation($yeartype->id, $level);
                        if(!isset($chain['class'])){
                            $chain['class'] =  ClassLevel::where('year_level_id',$yearlevel->id)->pluck('class_id');
                        }
                        foreach ($chain['class'] as $class) {
                            # code...
                            $classLevel = ClassLevel::checkRelation($class, $yearlevel->id);
                            foreach ($chain['segment'] as $segment) {
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
    }
        $course->attachment;
        $courses =  Course::with(['category', 'attachment','courseSegments.segmentClasses.classLevel.yearLevels.levels'])->get();
        foreach($courses as $le){
            $le['levels'] = $le->courseSegments->pluck('segmentClasses.*.classLevel.*.yearLevels.*.levels')->collapse()->collapse()->unique()->values();
            $teacher = User::whereIn('id',Enroll::where('role_id', '4')->whereIn('course_segment',  $le->courseSegments->pluck('id'))
                                                ->pluck('user_id')
                            )->with('attachment')->get(['id', 'username', 'firstname', 'lastname', 'picture']);
            $le['teachers']  = $teacher ;
            unset($le->courseSegments);
        }
        $request['returnmsg'] = 'Course Created Successfully';
        $request['year'] = $request->chains[0]['year'][0];
        $request = new Request($request->only(['name', 'category','returnmsg','year']));
        $print=self::get($request);
        return $print;
        // return HelperController::api_response_format(201, $courses->paginate(HelperController::GetPaginate($request)), 'Course Created Successfully');
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
            'category' => 'nullable|exists:categories,id',
            'id' => 'required|exists:courses,id',
            'image' => 'nullable',
            'description' => 'nullable',
            'mandatory' => 'nullable|in:0,1'
        ]);
        $editable = ['name', 'category_id', 'description', 'mandatory'];
        $course = Course::find($request->id);
        $course->name = $request->name;
        if($request->filled('category'))
            $course->category_id = $request->category;

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
        $req = new Request();

          return HelperController::api_response_format(200, $this->get($req,2)->paginate(HelperController::GetPaginate($request)), 'Course Updated Successfully');
          // return HelperController::api_response_format(200, Course::with(['category', 'attachment'])->paginate(HelperController::GetPaginate($request)), 'Course Updated Successfully');
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
    public static function get(Request $request,$call=0)
    {
        $request->validate([
            'id' => 'exists:courses,id',
            'category_id' => 'nullable|exists:categories,id',
            'year' => 'nullable|exists:academic_years,id',
            'type' => 'nullable|exists:academic_types,id',
            'level' => 'nullable|exists:levels,id',
            'class' => 'nullable|exists:classes,id',
            'segment' => 'nullable|exists:segments,id',
            'search' => 'nullable',
            'for' => 'in:enroll'
        ]);
        $cs=[];
        if (isset($request->id))
        {
            $cor=Course::find($request->id);
            $cor->levels=$cor->courseSegments->pluck('segmentClasses.*.classLevel.*.yearLevels.*.levels')->collapse()->collapse()->unique()->values();
            unset($cor->courseSegments);
            $cor->category;
            $cor->attachmnet;
            return HelperController::api_response_format(200, $cor);
        }

        if(!isset($request->year))
        {
            $year = AcademicYear::Get_current();
            if(!$year)
                return HelperController::api_response_format(200, null, ' No Active year here');
        }

        $couresegs = GradeCategoryController::getCourseSegment($request);
        if(count($couresegs) == 0)
            return HelperController::api_response_format(200, null, 'No Courses' );

        foreach($couresegs as $one){
            $cc=CourseSegment::find($one);
            if($request->for == 'enroll')
                if(!($cc->start_date <= Carbon::now() && $cc->end_date >= Carbon::now()))
                    continue;

            $cs[]=$cc->course_id;
        }

        $courses =  Course::whereIn('id',$cs)->with(['category', 'attachment','courseSegments.segmentClasses.classLevel.yearLevels.levels'])->where('name', 'LIKE', "%$request->search%")->get();
        if($call == 1 ){
            return $courses;
        }
        foreach($courses as $le){
            $le['levels'] = $le->courseSegments->pluck('segmentClasses.*.classLevel.*.yearLevels.*.levels')->collapse()->collapse()->unique()->values();
            $teacher = User::whereIn('id',
                        Enroll::where('role_id', '4')
                            ->whereIn('course_segment',  $le->courseSegments->pluck('id'))
                            ->pluck('user_id')
                            )->with('attachment')->get(['id', 'username', 'firstname', 'lastname', 'picture']);
                            $le['teachers']  = $teacher ;
            unset($le->courseSegments);
        }
        if($call == 2 ){ //$call by function update 
            return $courses;
        }
        if(isset($request->returnmsg))
            return HelperController::api_response_format(200, $courses->paginate(HelperController::GetPaginate($request)),$request->returnmsg );
        else
            return HelperController::api_response_format(200, $courses->paginate(HelperController::GetPaginate($request)) );
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
        $enrolls = Enroll::where('course_segment',CourseSegment::where('course_id',$request->id)->pluck('id'))->get();
        if(count($enrolls)>0){
            return HelperController::api_response_format(400, [], 'This course assigned to users, cannot be deleted');

        }
        CourseSegment::where('course_id',$request->id)->delete();
        $course->delete();
        $request['returnmsg'] = 'Course Deleted Successfully';
        $request = new Request($request->only(['returnmsg']));
        $print=self::get($request);
        return $print;
        // return HelperController::api_response_format(200, Course::with(['category', 'attachment'])->paginate(HelperController::GetPaginate($request)), 'Course Deleted Successfully');
    }


    /**
     * get current enrolledCourses
     *
     * @return [object] current course_name with teacher and category with all chain
     */
    public function CurrentCourses(Request $request)
    {
        $request->validate([
            'year' => 'exists:academic_years,id',
            'type' => 'exists:academic_types,id',
            'level' => 'exists:levels,id',
            'class' => 'exists:classes,id',
            'segment' => 'exists:segments,id',
        ]);
        $all = collect();
        $testCourse=array();
        $adminCourses=collect();
        $couuures=array();
        $active_year = AcademicYear::where('current',1)->get();
        if(!isset($request->year) && !count($active_year)>0)
            return HelperController::api_response_format(200, null, 'There is no active year,please send year');
            
        $CS = GradeCategoryController::getCourseSegment($request);

        if($request->user()->can('site/show-all-courses'))
        {
            foreach ($CS as $coco) {
                $cocos=CourseSegment::find($coco);
                if ($cocos->end_date > Carbon::now() && $cocos->start_date < Carbon::now()) {
                    if($request->filled('year') || $request->filled('segment') || $request->filled('type') || $request->filled('level') || $request->filled('class') ){
                        if(in_array($coco, $CS->toArray()))
                            array_push($couuures,$coco);
                    }
                    else
                        array_push($couuures,$coco);
                }
            }
        }
        else
        {
            foreach ($request->user()->enroll as $enroll) {  
                if(in_array($enroll->CourseSegment->id, $CS->toArray())){
                    if ($enroll->CourseSegment->end_date > Carbon::now() && $enroll->CourseSegment->start_date < Carbon::now()) {
                        if($request->filled('year') || $request->filled('segment') || $request->filled('type') || $request->filled('level') || $request->filled('class') ){
                            if(in_array($enroll->CourseSegment->id, $CS->toArray()))
                                array_push($couuures,$enroll->CourseSegment->id);
                        }
                        else
                            array_push($couuures,$enroll->CourseSegment->id);
                    }
                }         
            }
        }
        $request->validate([
            'course_id' => 'exists:courses,id'
        ]);
        if($request->filled('course_id'))
            $couuures= CourseSegment::where('course_id', $request->course_id)->pluck('id');
 
        foreach ($couuures as $enroll) {
                $teacherz = array();
                $segment_Class_id = CourseSegment::where('id', $enroll)->get(['segment_class_id', 'course_id'])->first();
                $course = Course::where('id', $segment_Class_id->course_id)->with(['category', 'attachment'])->first();
                    
                if(in_array($course->id,$testCourse))
                    continue;
                array_push($testCourse,$course->id);
                $segment = SegmentClass::where('id', $segment_Class_id->segment_class_id)->get(['segment_id', 'class_level_id'])->first();
                $flag = new stdClass();
                $flag->segment = Segment::find($segment->segment_id)->name;
                $class_id = ClassLevel::where('id', $segment->class_level_id)->get(['class_id', 'year_level_id'])->first();
                $class_object = Classes::find($class_id->class_id);
                $flag->class = 'Not_Found';
                if(isset($class_object))
                    $flag->class = $class_object->name;
                $level_id = YearLevel::where('id', $class_id->year_level_id)->get(['level_id', 'academic_year_type_id'])->first();
                $level_object = Level::find($level_id->level_id);
                $flag->level = 'Not_Found';
                if(isset($level_object))
                    $flag->level = $level_object->name;
                $AC_type = AcademicYearType::where('id', $level_id->academic_year_type_id)->get(['academic_year_id', 'academic_type_id'])->first();
                if(isset($AC_type)){
                    $year_object = AcademicYear::find($AC_type->academic_year_id);
                    $type_object = AcademicType::find($AC_type->academic_type_id);

                    $flag->year = 'Not_Found';
                    $flag->type = 'Not_Found';
                    
                    if(isset($year_object))
                        $flag->year = $year_object->name;
                    if(isset($type_object))
                        $flag->type = $type_object->name;
                }
                $userr=Enroll::where('role_id', 4)->where('course_segment', $enroll)->pluck('user_id');

                // $userr=Enroll::where('role_id', 4)->where('course_segment', $enroll)->pluck('user_id')->first();
                // $teacher = User::whereId($userr)->get(['id', 'username', 'firstname', 'lastname', 'picture'])->first();
                // if(isset($teacher->attachment))
                //     $teacher->picture=$teacher->attachment->path;
                foreach($userr as $teach){
                    $teacher = User::whereId($teach)->with('attachment')->get(['id', 'username', 'firstname', 'lastname', 'picture'])->first();
                    if(isset($teacher)){
                        if(isset($teacher->attachment))
                        $teacher->picture=$teacher->attachment->path;
                        array_push($teacherz, $teacher);
                    }
                }
                $en=Enroll::where('course_segment',$enroll)->where('user_id',Auth::id())->first();
                if(isset($en->id)  && isset($teacher))
                    $teacher->class = $en->CourseSegment->segmentClasses[0]->classLevel[0]->classes[0];
                $course->flag = $flag;
                $coursa =  Course::where('id', $course->id)->with(['category', 'attachment','courseSegments.segmentClasses.classLevel.yearLevels.levels'])->where('name', 'LIKE', "%$request->search%")->first();
                $course->levels = $coursa->courseSegments->pluck('segmentClasses.*.classLevel.*.yearLevels.*.levels')->collapse()->collapse()->unique()->values();
                $course->teachers = $teacherz;
                if(!isset($course->attachment)){
                    $course->attachment = null;
                }
                $all->push($course);
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
        $request->validate([
            'year' => 'exists:academic_years,id',
            'type' => 'exists:academic_types,id',
            'level' => 'exists:levels,id',
            'class' => 'exists:classes,id',
            'segment' => 'exists:segments,id',
        ]);
        $all = collect();
        $testCourse=array();
        $adminCourses=collect();
        $couuures=array();
        $CS = GradeCategoryController::getCourseSegment($request);

        if($request->user()->can('site/show-all-courses'))
        {
            foreach ($CS as $coco) {
                $cocos=CourseSegment::find($coco);
                if ($cocos->end_date < Carbon::now() && $cocos->start_date < Carbon::now()) {
                    if($request->filled('year') || $request->filled('segment') || $request->filled('type') || $request->filled('level') || $request->filled('class') ){
                        if(in_array($coco, $CS->toArray()))
                            array_push($couuures,$coco);
                    }
                    else
                        array_push($couuures,$coco);
                }
            }
        }
        else
        {
            foreach ($request->user()->enroll as $enroll) {           
                if ($enroll->CourseSegment->end_date < Carbon::now() && $enroll->CourseSegment->start_date < Carbon::now()) {
                    if($request->filled('year') || $request->filled('segment') || $request->filled('type') || $request->filled('level') || $request->filled('class') ){
                        if(in_array($enroll->CourseSegment->id, $CS->toArray()))
                            array_push($couuures,$enroll->CourseSegment->id);
                    }
                    else
                        array_push($couuures,$enroll->CourseSegment->id);
                }
            }
        }

        foreach ($couuures as $enroll) {
            $teacherz = array();
                $segment_Class_id = CourseSegment::where('id', $enroll)->get(['segment_class_id', 'course_id'])->first();
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

                $segment_object = Segment::find($segment->segment_id);
                $flag->segment = 'Not_Found';
                if(isset($segment_object))
                    $flag->segment = $segment_object->name;
                $class_id = ClassLevel::where('id', $segment->class_level_id)->get(['class_id', 'year_level_id'])->first();
                $check_class = Classes::find($class_id->class_id);
                if(isset($check_class))
                    $flag->class = Classes::find($class_id->class_id)->name;
                $level_id = YearLevel::where('id', $class_id->year_level_id)->get(['level_id', 'academic_year_type_id'])->first();
                $Check_level = Level::find($level_id->level_id);
                if(isset($Check_level))
                    $flag->level = Level::find($level_id->level_id)->name;
                $AC_type = AcademicYearType::where('id', $level_id->academic_year_type_id)->get(['academic_year_id', 'academic_type_id'])->first();
                if(isset($AC_type)){

                    $year_object = AcademicYear::find($AC_type->academic_year_id);
                    $type_object = AcademicType::find($AC_type->academic_type_id);

                    $flag->year = 'Not_Found';
                    $flag->type = 'Not_Found';
                    
                    if(isset($year_object))
                        $flag->year = $year_object->name;
                    if(isset($type_object))
                        $flag->type = $type_object->name;

                }
                $userr=Enroll::where('role_id', 4)->where('course_segment', $enroll)->pluck('user_id');
                foreach($userr as $teach){
                    $teacher = User::whereId($teach)->with('attachment')->get(['id', 'username', 'firstname', 'lastname', 'picture'])->first();
                    if(isset($teacher->attachment))
                        $teacher->picture=$teacher->attachment->path;
                        array_push($teacherz, $teacher);
    
                    // $en=Enroll::where('course_segment',$enroll)->where('user_id',Auth::id())->first();
                    // if(isset($en->id))
                    //     $teacher->class = $en->CourseSegment->segmentClasses[0]->classLevel[0]->classes[0];
                }
               
                $course->flag = $flag;
                $coursa =  Course::where('id', $course->id)->with(['category', 'attachment','courseSegments.segmentClasses.classLevel.yearLevels.levels'])->where('name', 'LIKE', "%$request->search%")->first();
                $course->levels = $coursa->courseSegments->pluck('segmentClasses.*.classLevel.*.yearLevels.*.levels')->collapse()->collapse()->unique()->values();
                $course->teachers = $teacherz;
                $course->attachment;
                $all->push($course);
            }

        if (isset($all))
            return HelperController::api_response_format(200, (new Collection($all))->paginate(HelperController::GetPaginate($request)));

        return HelperController::api_response_format(200, null, 'there is no courses');
    }

    /**
     * get future enrolledCourses
     *
     * @return [object] future courses_name with teacher and category with all chain
     */
    public function FutureCourses(Request $request)
    {
        $request->validate([
            'year' => 'exists:academic_years,id',
            'type' => 'exists:academic_types,id',
            'level' => 'exists:levels,id',
            'class' => 'exists:classes,id',
            'segment' => 'exists:segments,id',
        ]);
        $all = collect();
        $testCourse=array();
        $adminCourses=collect();
        $couuures=array();
        $CS = GradeCategoryController::getCourseSegment($request);

        if($request->user()->can('site/show-all-courses'))
        {
            foreach ($CS as $coco) {
                $cocos=CourseSegment::find($coco);
                if ($cocos->end_date > Carbon::now() && $cocos->start_date > Carbon::now()) {
                    if($request->filled('year') || $request->filled('segment') || $request->filled('type') || $request->filled('level') || $request->filled('class') ){
                        if(in_array($coco, $CS->toArray()))
                            array_push($couuures,$coco);
                    }
                    else
                        array_push($couuures,$coco);
                }
            }
        }
        else
        {
            foreach ($request->user()->enroll as $enroll) {           
                if ($enroll->CourseSegment->end_date < Carbon::now() && $enroll->CourseSegment->start_date < Carbon::now()) {
                    if($request->filled('year') || $request->filled('segment') || $request->filled('type') || $request->filled('level') || $request->filled('class') ){
                        if(in_array($enroll->CourseSegment->id, $CS->toArray()))
                            array_push($couuures,$enroll->CourseSegment->id);
                    }
                    else
                        array_push($couuures,$enroll->CourseSegment->id);
                }
            }
        }

        foreach ($couuures as $enroll) {
            $teacherz = array();
                $segment_Class_id = CourseSegment::where('id', $enroll)->get(['segment_class_id', 'course_id'])->first();
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
                $check_class = Classes::find($class_id->class_id);
                if(isset($check_class))
                    $flag->class = Classes::find($class_id->class_id)->name;
                $level_id = YearLevel::where('id', $class_id->year_level_id)->get(['level_id', 'academic_year_type_id'])->first();
                $Check_level = Level::find($level_id->level_id);
                if(isset($Check_level))
                    $flag->level = Level::find($level_id->level_id)->name;
                $AC_type = AcademicYearType::where('id', $level_id->academic_year_type_id)->get(['academic_year_id', 'academic_type_id'])->first();
                if(isset($AC_type)){

                    $year_object = AcademicYear::find($AC_type->academic_year_id);
                    $type_object = AcademicType::find($AC_type->academic_type_id);

                    $flag->year = 'Not_Found';
                    $flag->type = 'Not_Found';
                    
                    if(isset($year_object))
                        $flag->year = $year_object->name;
                    if(isset($type_object))
                        $flag->type = $type_object->name;
                }
                $userr=Enroll::where('role_id', 4)->where('course_segment', $enroll)->pluck('user_id');
                foreach($userr as $teach){
                    $teacher = User::whereId($teach)->with('attachment')->get(['id', 'username', 'firstname', 'lastname', 'picture'])->first();
                    if(isset($teacher->attachment))
                        $teacher->picture=$teacher->attachment->path;
                        array_push($teacherz, $teacher);
    
                    // $en=Enroll::where('course_segment',$enroll)->where('user_id',Auth::id())->first();
                    // if(isset($en->id))
                    //     $teacher->class = $en->CourseSegment->segmentClasses[0]->classLevel[0]->classes[0];
                }
               
                $course->flag = $flag;
                $coursa =  Course::where('id', $course->id)->with(['category', 'attachment','courseSegments.segmentClasses.classLevel.yearLevels.levels'])->where('name', 'LIKE', "%$request->search%")->first();
                $course->levels = $coursa->courseSegments->pluck('segmentClasses.*.classLevel.*.yearLevels.*.levels')->collapse()->collapse()->unique()->values();
                $course->teachers = $teacherz;
                $course->attachment;
                $all->push($course);
            }
        // }
        if (isset($all))
            return HelperController::api_response_format(200, (new Collection($all))->paginate(HelperController::GetPaginate($request)));

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

    // public function detailsQuizAssignment(Request $)

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

        if($request->user()->can('site/show-all-courses')){
            $CourseSeg = CourseSegment::where('course_id',$request->course_id)->pluck('id');
        }
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
            if($request->user()->can('site/show-all-courses'))
                $userrole[$segggg] = 1;
            else
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
                                        $le['visible'] = MediaLesson::where('media_id',$le->id)->where('lesson_id',$le->pivot->lesson_id)->pluck('visible')->first();
                                        $le['item_lesson_id']=MediaLesson::where('media_id',$le->id)->where('lesson_id',$le->pivot->lesson_id)->pluck('id')->first();
                                    }
                                    if($le->pivot->file_id)
                                    {
                                        $le['visible'] = FileLesson::where('file_id',$le->id)->where('lesson_id',$le->pivot->lesson_id)->pluck('visible')->first();
                                        $le['item_lesson_id']=FileLesson::where('file_id',$le->id)->where('lesson_id',$le->pivot->lesson_id)->pluck('id')->first();
                                    }
                                    if($le->pivot->page_id)
                                    {
                                        $le['visible'] = pageLesson::where('page_id',$le->id)->where('lesson_id',$le->pivot->lesson_id)->pluck('visible')->first();
                                        $le['item_lesson_id']=pageLesson::where('page_id',$le->id)->where('lesson_id',$le->pivot->lesson_id)->pluck('id')->first();
                                    }
                                }
                                if($com->name == 'Quiz'){
                                 foreach ($lessonn['Quiz'] as $one){  
                                    $quiz_lesson=QuizLesson::where('quiz_id',$one->id)->where('lesson_id',$one->pivot->lesson_id)
                                                                    ->with('grading_method')->get()->first();
                                    $userquizze = UserQuiz::where('quiz_lesson_id', $quiz_lesson->id)->where('user_id', Auth::id())->pluck('id');
                                    // return u
                                    $count_answered=UserQuizAnswer::whereIn('user_quiz_id',$userquizze)->where('force_submit','1')->pluck('user_quiz_id')->unique()->count();
                                    $one['attempts_left'] = ($quiz_lesson->max_attemp - $count_answered);
                                    $one['taken_attempts'] = $count_answered;
                                    $one['questions']=$quiz_lesson->quiz->Question;
                                    unset($quiz_lesson->quiz);
                                    $one['visible'] = $quiz_lesson->visible;
                                    $one['item_lesson_id']=$quiz_lesson->id;
                                    $one->quiz_lesson=$quiz_lesson;
                                    if($one->pivot->publish_date > Carbon::now() &&  $request->user()->can('site/course/student'))
                                        $one->Started = false;
                                    else
                                        $one->Started = true;

                                        // $item->status = 'new';
                                        // if($count_answered != 0){
                                        //     foreach($user_quiz_answer->get() as $oneUserQuiz_answer)
                                        //         if((($oneUserQuiz_answer->Question->question_type->id == 4 || $oneUserQuiz_answer->Question->question_type->id == 5) && $oneUserQuiz_answer->user_grade == null) 
                                        //             || ($oneUserQuiz_answer->Question->question_type->id == 1 && $oneUserQuiz_answer->Question->And_why == 1 && $oneUserQuiz_answer->feedback != null))
                                        //             $item->status = 'submitted';
                                        //     $item->status='graded';
                                        // }
                                 }
                                }
                                if($com->name == 'Assigments'){
                                    foreach ($lessonn['Assigments'] as $one){
                                        $assignment_lesson=AssignmentLesson::where('assignment_id',$one->pivot->assignment_id)->where('lesson_id',$one->pivot->lesson_id)->get()->first();
                                        $one['user_submit']=null;
                                        $studentassigment = UserAssigment::where('assignment_lesson_id', $assignment_lesson->id)->where('user_id', Auth::id())->first();
                                        if(isset($studentassigment)){
                                            $one['user_submit'] =$studentassigment;
                                            $usr=User::find($studentassigment->user_id);
                                            if(isset($usr->attachment))
                                                $usr->picture=$usr->attachment->path;
                                            $one['user_submit']->User=$usr;
                                            if (isset($studentassigment->attachment_id)) {
                                                $one['user_submit']->attachment_id = attachment::where('id', $studentassigment->attachment_id)->first();
                                            }
                                        }

                                        $one->assignment_lesson=$assignment_lesson;
                                        $one['allow_attachment'] = $assignment_lesson->allow_attachment;
                                        $override_satrtdate = assignmentOverride::where('user_id',Auth::user()->id)->where('assignment_lesson_id',$assignment_lesson->id)->pluck('start_date')->first();
                                        $one->start_date = $assignment_lesson->start_date;
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
                                        $one['visible'] = $assignment_lesson->visible;
                                        $one['item_lesson_id']=$assignment_lesson->id;
                                    }
                                }

                                // $lessonn[$com->name][$com->name . $count] =  count($lessonn[$com->name]);
                                // if (isset($com->name))
                                //     $clase[$i][$com->name . $count] += count($lessonn[$com->name]);
                            }

                            $h5p_comp = Component::where('model', 'h5pLesson')->first();
                            if(isset($h5p_comp)){
                                $h5p_content=collect();
                                $url= substr($request->url(), 0, strpos($request->url(), "/api"));
                                $h5p_all= $lessonn->H5PLesson;
                                if ($request->user()->can('site/course/student')) {
                                    $h5p_all= $lessonn->H5PLesson->where('visible', '=', 1)->where('publish_date', '<=', Carbon::now());
                                }
                                foreach($h5p_all as $h5p){                                
                                    $content = response()->json(DB::table('h5p_contents')->whereId($h5p->content_id)->first());
                                    $content->original->link =  $url.'/api/h5p/'.$h5p->content_id;
                                    $content->original->item_lesson_id = $h5p->id;
                                    $content->original->visible = $h5p->visible;
                                    $content->original->edit_link = $url.'/api/h5p/'.$h5p->content_id.'/edit'.'?editting_done=false';
                                    if(!$request->user()->can('h5p/lesson/allow-edit') && $h5p->user_id != Auth::id() ){
                                        $content->original->edit_link = null;
                                    }
                                    $content->original->pivot = [
                                        'lesson_id' =>  $h5p->lesson_id,
                                        'content_id' =>  $h5p->content_id,
                                        'publish_date' => $h5p->publish_date,
                                        'created_at' =>  $h5p->created_at,
                                    ];
                                    $h5p_content->push($content->original);
                                }
                                $lessonn['interactive']= $h5p_content;
                                unset($lessonn['H5PLesson']);
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
                if (count($cs->optionalCourses) > 0){
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
                        $courseSegment = CourseSegment::find($course_Segment->id);
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
        return HelperController::api_response_format(201, null ,'Course Assigned Successfully');
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
        if ($request->filled('components')) 
            $components->whereIn('id', $request->components);
            
        $components = $components->get();
        $result = [];
        $CourseSegments=[];
        $h5p_comp = Component::where('model', 'h5pLesson')->first();
        foreach ($components as $component)
            $result[$component->name] = [];

        if(isset($h5p_comp))
            $result['interactive'] = [];
            
        $cs=GradeCategoryController::getCourseSegment($request);
        if($request->user()->can('site/show-all-courses'))
        {
            $CourseSegments=CourseSegment::whereIn('id',$cs)->get();
        }
        else
        {
            foreach ($request->user()->enroll as $enroll) {
                if ($enroll->courseSegment != null) {
                    if(in_array($enroll->courseSegment->id, $cs->toArray())){
                        $CourseSegments[]=$enroll->courseSegment;
                    }
                }
            }
        }
        $k=0;
        foreach ($CourseSegments as $oneCouSeg) {
            if ($oneCouSeg != null) {
                if(isset($request->course)){
                    if($oneCouSeg->course_id != $request->course)
                        continue;
                }

                foreach ($oneCouSeg->lessons as $lesson) {
                    foreach ($components as $component) {
                        $temp = $lesson->module($component->module, $component->model);

                        if ($request->user()->can('site/course/student')) {
                            $temp->where('visible', '=', 1);
                                // ->where('publish_date', '<=', Carbon::now());
                        }
                        //&& $component->model != 'assignment'
                        if($component->model != 'quiz'){
                            if($request->user()->can('site/course/student')){
                                $temp->where('publish_date', '<=', Carbon::now());
                            }
                        }
                        if(count($temp->get()) == 0)
                            continue;
                        $tempBulk = $temp->get();

                        foreach($tempBulk as $item){
                            if(isset($item->pivot))
                            {
                                // self::detailsQuizAssignment($lesson_id,$component_id);
                                $item->course = Course::find(Lesson::find($item->pivot->lesson_id)->courseSegment->course_id);
                                $item->class= Classes::find(Lesson::find($item->pivot->lesson_id)->courseSegment->segmentClasses[0]->classLevel[0]->class_id);
                                $item->level = Level::find(Lesson::find($item->pivot->lesson_id)->courseSegment->segmentClasses[0]->classLevel[0]->yearLevels[0]->level_id);
                                $item->lesson = Lesson::find($item->pivot->lesson_id);

                                if($item->pivot->quiz_id){
                                    $item->quiz_lesson = QuizLesson::where('quiz_id',$item->pivot->quiz_id)->where('lesson_id',$item->pivot->lesson_id);
                                    if(isset($request->timeline) && $request->timeline == 1 ){
                                        $item->quiz_lesson->where('due_date','>=',Carbon::now());
                                    }
                                    $item->due_date=  $item->quiz_lesson->pluck('due_date')->first();
                                    if(!isset ($item->due_date)){
                                        continue;
                                    }
                                    $item->quiz_lesson=$item->quiz_lesson->with('grading_method')->first();
                                    $userquizze = UserQuiz::where('quiz_lesson_id', $item->quiz_lesson->id)->where('user_id', Auth::id())->pluck('id');
                                    $user_quiz_answer=UserQuizAnswer::whereIn('user_quiz_id',$userquizze)->where('force_submit','1');
                                    $count_answered=$user_quiz_answer->pluck('user_quiz_id')->unique()->count();
                                    $item->attempts_left = ($item->quiz_lesson->max_attemp - $count_answered);
                                    $item->taken_attempts = $count_answered;
                                    $item->questions=$item->quiz_lesson->quiz->Question;
                                    unset($item->quiz_lesson->quiz);
                                    $item->visible = $item->quiz_lesson->visible;
                                    $item->item_lesson_id=$item->quiz_lesson->id;
                                    if($item->pivot->publish_date > Carbon::now() &&  $request->user()->can('site/course/student'))
                                        $item->Started = false;
                                    else
                                        $item->Started = true;

                                    $item->status = 'new';
                                    if($count_answered != 0){
                                        foreach($user_quiz_answer->get() as $oneUserQuiz_answer){
                                            if((($oneUserQuiz_answer->Question->question_type->id == 4 || $oneUserQuiz_answer->Question->question_type->id == 5) && $oneUserQuiz_answer->user_grade == null) 
                                                || ($oneUserQuiz_answer->Question->question_type->id == 1 && $oneUserQuiz_answer->Question->And_why == 1 && $oneUserQuiz_answer->feedback != null))
                                                $item->status = 'submitted';
                                            else
                                                $item->status='graded';
                                        }
                                    }
                                }
                                if($item->pivot->assignment_id)
                                {   
                                    $item->assignment_lesson = AssignmentLesson::where('assignment_id',$item->pivot->assignment_id)->where('lesson_id',$item->pivot->lesson_id);
                                    if(isset($request->timeline) && $request->timeline == 1 ){
                                        $item->assignment_lesson->where('due_date','>=',Carbon::now());
                                    }
                                    $item->due_date=  $item->assignment_lesson->pluck('due_date')->first();
                                    if(!isset ($item->due_date)){
                                        continue;
                                    }
                                    $item->assignment_lesson = $item->assignment_lesson->first();
                                    $item->user_submit=null;
                                    $studentassigment = UserAssigment::where('assignment_lesson_id', $item->assignment_lesson->id)->where('user_id', Auth::id())->first();
                                    if(isset($studentassigment)){
                                        $item->user_submit =$studentassigment;
                                        $usr=User::find($studentassigment->user_id);
                                        if(isset($usr->attachment))
                                            $usr->picture=$usr->attachment->path;
                                        $item->user_submit->User=$usr;
                                        if (isset($studentassigment->attachment_id)) {
                                            $item->user_submit->attachment_id = attachment::where('id', $studentassigment->attachment_id)->first();
                                        }
                                    }
                                    $item->allow_attachment = $item->assignment_lesson->allow_attachment;
                                    $override_satrtdate = assignmentOverride::where('user_id',Auth::user()->id)->where('assignment_lesson_id',$item->assignment_lesson->id)->pluck('start_date')->first();
                                    $item->start_date=$item->assignment_lesson->start_date;
                                    if($item->start_date > Carbon::now() &&  $request->user()->can('site/course/student'))
                                        $item->Started = false;
                                    else
                                        $item->Started = true;
                                    if($override_satrtdate != null){
                                        $item->start_date = $override_satrtdate;
                                        // $one->pivot->publish_date = $override_satrtdate;
                                        if($one->start_date > Carbon::now() &&  $request->user()->can('site/course/student'))
                                            $item->Started = false;
                                        else
                                            $item->Started = true;
                                    } 
                                    $item->visible = $item->assignment_lesson->visible;
                                    $item->item_lesson_id=$item->assignment_lesson->id;
                                    $item->status = 'new';
                                    if(isset($studentassigment)){
                                        if($studentassigment->grade == null)
                                            $item->status = 'submitted';
                                        else
                                            $item->status='graded';
                                    }
                                }
                                // $quickaction =collect([]);
                                if($item->pivot->media_id)
                                {
                                    $item['flag'] = 'media';
                                    $quickaction[]=$item;
                                    $k++;
                                }
                                if($item->pivot->file_id)
                                {
                                    $item['flag'] = 'file';
                                    $quickaction[]=$item;
                                    $k++;
                                }
                                if($item->pivot->page_id)
                                {
                                    $item['flag'] = 'page';
                                    $quickaction[]=$item;
                                    $k++;
                                }
                                $result[$component->name][] = $item;
                            }
                        }
                    }
                        $h5p_comp = Component::where('model', 'h5pLesson')->first();
                            if(isset($h5p_comp)){
                                $url= substr($request->url(), 0, strpos($request->url(), "/api"));
                                $h5p_all= $lesson->H5PLesson;
                                if ($request->user()->can('site/course/student')) {
                                    $h5p_all= $lesson->H5PLesson->where('visible', '=', 1)->where('publish_date', '<=', Carbon::now());
                                }
                                foreach($h5p_all as $h5p){                                
                                    $content = response()->json(DB::table('h5p_contents')->whereId($h5p->content_id)->first());
                                    $content->original->link =  $url.'/api/h5p/'.$h5p->content_id;
                                    $content->original->item_lesson_id = $h5p->id;
                                    $content->original->visible = $h5p->visible;
                                    $content->original->edit_link = $url.'/api/h5p/'.$h5p->content_id.'/edit';
                                    if(!$request->user()->can('h5p/lesson/allow-edit') && $h5p->user_id != Auth::id() ){
                                        $content->original->edit_link = null;
                                    }
                                    $content->original->course = Course::find(Lesson::find($h5p->lesson_id)->courseSegment->course_id);
                                    $content->original->class= Classes::find(Lesson::find($h5p->lesson_id)->courseSegment->segmentClasses[0]->classLevel[0]->class_id);
                                    $content->original->level = Level::find(Lesson::find($h5p->lesson_id)->courseSegment->segmentClasses[0]->classLevel[0]->yearLevels[0]->level_id);
                                    $content->original->lesson = Lesson::find($h5p->lesson_id);
                                    $content->original->pivot = [
                                        'lesson_id' =>  $h5p->lesson_id,
                                        'content_id' =>  $h5p->content_id,
                                        'publish_date' => $h5p->publish_date,
                                        'created_at' =>  $h5p->created_at,
                                    ];
                                    unset($content->original->parameters);
                                    unset($content->original->filtered);
                                    $result['interactive'][]=$content->original;
                                }
                            }
                }
            }
        }
        //quick actions
        if($request->quick_action == 1){
            if($k == 0){  
                $quickaction = [];
                return HelperController::api_response_format(200,$quickaction);
            }
            //will be order in desc awl ma yft7 bl due date
            $quick = collect($quickaction);
            $i=0;
            // return $quick;
            foreach($quick as $mm){
                $quick_sort[$i]['id'] = $mm->id;
                $quick_sort[$i]['date'] = $mm->pivot->publish_date;
                $quick_sort[$i]['type'] = $mm->flag;
                $i++;
            }
            $a = collect($quick_sort)->sortByDesc('date')->values();
            $j=0;
            // return $quickaction;
            foreach($a as $as)
            {
                $tryyyy [$j]= collect($quickaction)->where('id', $as['id'])->where('flag',$as['type'])->values()[0];
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
                        $course_quiz_sort[$i]['id'] = $qu->id;
                        $course_quiz_sort[$i]['name'] = $qu->course->name; 
                        $i++;
                    }
                    if($request->order == 'asc')
                        $quii = collect($course_quiz_sort)->sortBy('name')->values();
                    else
                        $quii = collect($course_quiz_sort)->sortByDesc('name')->values();

                    $j=0;
                    foreach($quii as $qqq)
                    {                          
                        $try [$j]= collect($result["Quiz"])->where('id', $qqq['id'])->values()[0];
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
        if($request->user()->can('site/show-all-courses'))
        {
            $CourseSeg=GradeCategoryController::getCourseSegment($request);
        }
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

    public function export(Request $request)
    {
        $courses=self::get($request,1);
        $filename = uniqid();
        $file = Excel::store(new CoursesExport($courses), 'Courses'.$filename.'.xls','public');
        $file = url(Storage::url('Courses'.$filename.'.xls'));
        return HelperController::api_response_format(201,$file, 'Link to file ....');
    }
}
