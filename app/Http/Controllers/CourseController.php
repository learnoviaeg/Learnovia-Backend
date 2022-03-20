<?php

namespace App\Http\Controllers;

use App\AcademicYearType;
use App\ClassLevel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use App\Classes;
use Nwidart\Modules\Collection;
use Log;
use App\Course;
use App\SecondaryChain;
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
use Modules\QuestionBank\Entities\QuestionsCategory;
use App\Repositories\ChainRepositoryInterface;


class CourseController extends Controller
{
    public function __construct(ChainRepositoryInterface $chain)
    {
        $this->chain = $chain;
    }
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
        
        $short_names=Course::where('segment_id',$row['segment_id'])->where('short_name',$row['short_name'])->get();
        if(count($short_names)>0)
            return HelperController::api_response_format(400, null, 'short_name must be unique');

        $no_of_lessons = 4;
        $course = Course::firstOrCreate([
            'name' => $request->name,
            'short_name' => $request->short_name,
            'image' => isset($request->image) ? attachment::upload_attachment($request->image, 'course')->id : null,
            'category_id' => isset($request->category) ? $request->category : null,
            'description' => isset($request->description) ? $request->description : null,
            'mandatory' => isset($request->mandatory) ? $request->mandatory : 1,

        ]);

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
                                    'name' => $request->name . ' Total',
                                    'course_segment_id' => $courseSegment->id
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
        $course_segment_id = null;
        if(count($course->courseSegments) > 0)
            $course_segment_id = $course->courseSegments[0]->id;

        //Creating defult question category
        $quest_cat = QuestionsCategory::firstOrCreate([
            'name' => $request->name . ' Category',
            'course_id' => $course->id,
            'course_segment_id' => $course_segment_id
        ]);

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
            'mandatory' => 'nullable|in:0,1',
            'shared_lesson' => 'nullable|in:0,1',
            'short_name' => 'unique:courses,short_name,'.$request->id,
            'start_date' => 'date',
            'end_date' =>'date|after:start_date',
            'course_template' => 'nullable|exists:courses,id',
            'is_template' => 'nullable|boolean|required_with:course_template',
            'old_lessons' => 'nullable|boolean|required_with:course_template',
        ]);

        $editable = ['name', 'category_id', 'description', 'mandatory','short_name','is_template','shared_lesson'];
        $course = Course::find($request->id);
        // if course has an image
        if ($request->hasFile('image')) 
            $course->image = attachment::upload_attachment($request->image, 'course')->id;
        
        foreach ($editable as $key) 
            if ($request->filled($key)) 
                $course->$key = $request->$key;

        if($request->filled('course_template')){
            if($request->old_lessons == 0){
                $old_lessons = Lesson::where('course_id', $request->id)->get();
                $secondary_chains = SecondaryChain::whereIn('lesson_id',$old_lessons)->where('course_id',$request->id)->delete();
            }
            $new_lessons = Lesson::where('course_id', $request->course_template);
            foreach($new_lessons->cursor() as $lesson){
                Lesson::create([
                    'name' => $lesson->name,
                    'course_id' => $request->id,
                    'shared_lesson' => 1,//$lesson->shared_lesson,
                    'index' => $lesson->index,
                    'description' => $lesson->description,
                    'image' => $lesson->image,
                ]);
            }            
        }

        if($request->filled('shared_lesson'))
            $lessons = Lesson::where('course_id', $request->id)->update(['shared_lesson' => $request->shared_lesson]);

        // $course_segment = CourseSegment::where("course_id",$request->id);
        // if ($request->filled('start_date')) 
        //      $course_segment->update(['start_date'=>$request->start_date]); 
        
        // if ($request->filled('end_date')) 
        //     $course_segment->update(['end_date' => $request->end_date]);
         
        $course->save();
        // $req = new Request();
        return HelperController::api_response_format(200, $course, __('messages.course.update'));
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
    public function get(Request $request,$call=0)
    {
        $request->validate([
            'id' => 'exists:courses,id',
            'category_id' => 'nullable|exists:categories,id',
            'year' => 'nullable|exists:academic_years,id',
            'type' => 'array',
            'type.*' => 'nullable|exists:academic_types,id',
            'levels' => 'array',
            'levels.*' => 'nullable|exists:levels,id',
            'classes' => 'array',
            'classes.*' => 'nullable|exists:classes,id',
            'segments' => 'array',
            'segments.*' => 'nullable|exists:segments,id',
            'search' => 'nullable',
            'for' => 'in:enroll'
        ]);
        $cs=[];
        // if(!isset($request->year))
        // {
        //     $year = AcademicYear::Get_current();
        //     if(!$year)
        //         return HelperController::api_response_format(200, null, __('messages.error.no_active_year'));
        // }

        $enrolls = $this->chain->getEnrollsByManyChain($request);
        $enrolls->where('user_id',Auth::id());

        $courses =  Course::whereIn('id',$enrolls->pluck('course'))->with(['category', 'attachment','level'])
                            ->where(function($q)use($request){
                                $q->orWhere('name', 'LIKE', "%$request->search%")
                                ->orWhere('short_name', 'LIKE' ,"%$request->search%");})->get();
        if($call == 1 ){
            return $courses;
        }
        foreach($courses as $le){
            $le['levels'] = $le->level;
            $teacher = User::whereIn('id',$enrolls->where('role_id', '4')
                            ->pluck('user_id')
                            )->with('attachment')->get(['id', 'username', 'firstname', 'lastname', 'picture']);
                            $le['teachers']  = $teacher ;
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
        $enrolls = Enroll::where('course',$request->id)->where('user_id','!=',1)->get();
        if(count($enrolls)>0)
            return HelperController::api_response_format(400, [], __('messages.error.cannot_delete'));

        // CourseSegment::where('course_id',$request->id)->delete();
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
            return HelperController::api_response_format(200, null, __('messages.error.no_active_year'));
            
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
            foreach ($request->user()->enroll->whereNotIn('role_id',[6]) as $enroll) {
                // foreach ($request->user()->enroll->whereIn('role_id',[3,4]) as $enroll) {
                // foreach ($request->user()->enroll as $enroll) {
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
            $userr=Enroll::where('role_id', 4)->where('course_segment', $enroll)->pluck('user_id')->unique();
            if(isset($request->course_id))
                $userr=Enroll::where('role_id', 4)->whereIn('course_segment', $couuures)->pluck('user_id')->unique();
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
            $coursa =  Course::where('id', $course->id)->with(['category', 'attachment','courseSegments.segmentClasses.classLevel.yearLevels.levels'])
                            ->where(function($q)use($request){
                                    $q->orWhere('name', 'LIKE', "%$request->search%")
                                    ->orWhere('short_name', 'LIKE' ,"%$request->search%");})->first();
            $course->levels = $coursa->courseSegments->pluck('segmentClasses.*.classLevel.*.yearLevels.*.levels')->collapse()->collapse()->unique()->values();
            $course->teachers = $teacherz;
            if(!isset($course->attachment)){
                $course->attachment = null;
            }
            $all->push($course);
        }
        if (isset($all))
            return HelperController::api_response_format(200, (new Collection($all))->paginate(HelperController::GetPaginate($request)));

        return HelperController::api_response_format(200, null, __('messages.error.no_available_data'));
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
                $active_year = AcademicYear::where('current',1)->get();
        if(!isset($request->year) && !count($active_year)>0)
            return HelperController::api_response_format(200, null, __('messages.error.no_active_year'));
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

                $flag = new stdClass();
                $segment = SegmentClass::where('id', $segment_Class_id->segment_class_id)->get(['segment_id', 'class_level_id'])->first();
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
                    if(isset($teacher)){
                        if(isset($teacher->attachment))
                        $teacher->picture=$teacher->attachment->path;
                        array_push($teacherz, $teacher);
                    }
                    // $en=Enroll::where('course_segment',$enroll)->where('user_id',Auth::id())->first();
                    // if(isset($en->id))
                    //     $teacher->class = $en->CourseSegment->segmentClasses[0]->classLevel[0]->classes[0];
                }
               
                $course->flag = $flag;
                $coursa =  Course::where('id', $course->id)->with(['category', 'attachment','courseSegments.segmentClasses.classLevel.yearLevels.levels'])
                                ->where(function($q)use($request){
                                    $q->orWhere('name', 'LIKE', "%$request->search%")
                                    ->orWhere('short_name', 'LIKE' ,"%$request->search%");})->first();
                $course->levels = $coursa->courseSegments->pluck('segmentClasses.*.classLevel.*.yearLevels.*.levels')->collapse()->collapse()->unique()->values();
                $course->teachers = $teacherz;
                $course->attachment;
                $all->push($course);
            }

        if (isset($all))
            return HelperController::api_response_format(200, (new Collection($all))->paginate(HelperController::GetPaginate($request)));

        return HelperController::api_response_format(200, null, __('messages.error.no_available_data'));
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
        $active_year = AcademicYear::where('current',1)->get();
        if(!isset($request->year) && !count($active_year)>0)
            return HelperController::api_response_format(200, null, __('messages.error.no_active_year'));
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

                $flag = new stdClass();
                $segment = SegmentClass::where('id', $segment_Class_id->segment_class_id)->get(['segment_id', 'class_level_id'])->first();
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
                    if(isset($teacher)){
                        if(isset($teacher->attachment))
                        $teacher->picture=$teacher->attachment->path;
                        array_push($teacherz, $teacher);
                    }
                    // $en=Enroll::where('course_segment',$enroll)->where('user_id',Auth::id())->first();
                    // if(isset($en->id))
                    //     $teacher->class = $en->CourseSegment->segmentClasses[0]->classLevel[0]->classes[0];
                }
               
                $course->flag = $flag;
                $coursa =  Course::where('id', $course->id)->with(['category', 'attachment','courseSegments.segmentClasses.classLevel.yearLevels.levels'])
                                    ->where(function($q)use($request){
                                        $q->orWhere('name', 'LIKE', "%$request->search%")
                                        ->orWhere('short_name', 'LIKE' ,"%$request->search%");})->first();
                $course->levels = $coursa->courseSegments->pluck('segmentClasses.*.classLevel.*.yearLevels.*.levels')->collapse()->collapse()->unique()->values();
                $course->teachers = $teacherz;
                $course->attachment;
                $all->push($course);
            }
        // }
        if (isset($all))
            return HelperController::api_response_format(200, (new Collection($all))->paginate(HelperController::GetPaginate($request)));

        return HelperController::api_response_format(200, null, __('messages.error.no_available_data'));
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
        // $test = 0;
        // $course_segment = GradeCategoryController::getCourseSegment($request);
        // if (!isset($course_segment))
        //     return HelperController::api_response_format(404,null,__('messages.error.no_available_data'));
        // foreach ($course_segment as $cs) {
        $enrolls = $this->chain->getEnrollsByChain($request);

        $courses=Course::whereIn('id',$enrolls->pluck('course'))->where('mandatory',0);
            // $courses->optionalCourses;
        //     if (count($cour_seg->optionalCourses) > 0){
        //         $optional[] = $cour_seg->optionalCourses[0];
        //         $test += 1;
        //     }
        // }
        // if ($test > 0)
        return HelperController::api_response_format(200, $courses->get());

        // return HelperController::api_response_format(200,null, __('messages.error.no_available_data'));
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
                            return HelperController::api_response_format(404, __('messages.error.no_active_year'));
                        else
                            $year = $year->id;
                    }
                    if (isset($request->segment[$count]))
                        $segment = $request->segment[$count];
                    else {
                        $segment = Segment::Get_current($request->type[$count])->id;
                        if ($segment == null)
                            return HelperController::api_response_format(404, __('messages.error.no_active_segment'));
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

                    if ($request->filled('no_of_lessons')) 
                        $no_of_lessons = $request->no_of_lessons;
                    
                    for ($i = 1; $i <= $no_of_lessons; $i++) {
                        Lesson::firstOrCreate([
                            'name' => 'Lesson ' . $i,
                            'index' => $i,
                            'course_segment_id' => $course_Segment->id,
                        ]);
                    }
                    $course=Course::find($request->course);
                    $gradeCat = GradeCategory::firstOrCreate([
                        'name' => $course->name . ' Total',
                        'course_segment_id' => $course_Segment->id,
                        // 'id_number' => $year_level->id
                    ]);
                    $count++;
                }
            }
        } else {
            return HelperController::api_response_format(201, __('messages.error.data_invalid'));
        }
        return HelperController::api_response_format(201, null ,__('messages.course.assign'));
    }

    /**
     * get sorted lessons of user
     *
     * @param  [int] course_id, class_id
     * @return [object] sorted lessons
     */
    public function GetUserCourseLessonsSorted(Request $request)
    {
        $result = [];
        $enrolls = $this->chain->getEnrollsByManyChain($request)->where('user_id', Auth::id());
        $lessons = SecondaryChain::where('enroll_id',$enrolls->pluck('id'))->pluck('lesson_id')->unique();

        $result = [];
        $i = 0;
        foreach ($lessons as $lesson) {
            $lesson=Lesson::find($lesson);
            $components = LessonComponent::whereLesson_id($lesson->id)->orderBy('index')->get();
            $result[$i]['name'] = $lesson->name;
            $result[$i]['LessonID'] =$lesson->id;

            $result[$i]['data'] = [];
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
                    if(!in_array($item, $result[$i]['data']))
                        $result[$i]['data'][] = $item;
                }
            }
            $i++;
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

            return HelperController::api_response_format(201, $course, __('messages.success.toggle'));
        } else
            return HelperController::api_response_format(201, __('messages.error.no_active_segment'));
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

        return HelperController::api_response_format(200, null, __('messages.error.no_available_data'));
    }

    public function Count_Components(Request $request)
    {
        $enrolls = $this->chain->getEnrollsByManyChain($request)->where('user_id', Auth::id());
        $lessons = SecondaryChain::where('enroll_id',$enrolls->pluck('id'))->pluck('lesson_id')->unique();
        $components =  LessonComponent::whereIn('lesson_id', $lessons)
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

    public function getLessonsFromCourseAndClass(Request $request){
        $validator =  Validator::make($request->all() , [
            'class'  => 'required|exists:classes,id',
            'course' => 'required|exists:courses,id',
        ]);
        if($validator->fails())
            return HelperController::api_response_format(200, $validator->errors());
        $courseSegment = CourseSegment::GetWithClassAndCourse($request->class , $request->course);
        if($courseSegment == null)
            return HelperController::api_response_format(200, null, __('messages.error.no_active_segment'));
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
        return HelperController::api_response_format(200,$classs,__('messages.class.list'));
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
            return HelperController::api_response_format(200,$final->unique()->values(),__('messages.course.list'));
        }

        $courses=Course::get();
        return HelperController::api_response_format(200,$courses,__('messages.course.list'));
    }

    public function export(Request $request)
    {
        $courses=self::get($request,1);
        $filename = uniqid();
        $file = Excel::store(new CoursesExport($courses), 'Courses'.$filename.'.xlsx','public');
        $file = url(Storage::url('Courses'.$filename.'.xlsx'));
        return HelperController::api_response_format(201,$file, __('messages.success.link_to_file'));
    }

    public function Upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|distinct|mimes:mp4,avi,flv,mpga,ogg,ogv,oga,jpg,jpeg,png,gif,doc,mp3,wav',
            'id' => 'exists:attachments,id',
        ]);

        $attachment = attachment::upload_attachment($request->file, 'For-Editor');
        if(isset($request->id))
            $attachment=attachment::where('id',$request->id)->first();
        return HelperController::api_response_format(201,$attachment, 'file');
    }

    public function AddQuestionCategorytoCourses(Request $request){
        $existing_courses = QuestionsCategory::pluck('course_id');
        
        $courses = Course::whereNotIn('id',$existing_courses->filter()->values())->get();
        
        foreach($courses as $course){

            $course_segment_id = null;
            if(count($course->courseSegments) > 0)
                $course_segment_id = $course->courseSegments[0]->id;
    
            //Creating defult question category
            $quest_cat = QuestionsCategory::firstOrCreate([
                'name' => 'Category one',
                'course_id' => $course->id,
                'course_segment_id' => $course_segment_id
            ]);
        }

        return HelperController::api_response_format(201,null,'done');
    }
}
