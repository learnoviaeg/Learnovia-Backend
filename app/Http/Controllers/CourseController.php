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
use App\Paginate;


class CourseController extends Controller
{
    public function __construct(ChainRepositoryInterface $chain)
    {
        $this->chain = $chain;
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

        $editable = ['name', 'category_id','show', 'description', 'mandatory','short_name','is_template','shared_lesson'];
        $course = Course::find($request->id);
        // if course has an image
        if ($request->hasFile('image')) 
            $course->image = attachment::upload_attachment($request->image, 'course')->id;

        if(isset($request->shared_lesson) && $request->shared_lesson == 0)
        {
            $countAllLessons = Lesson::where('course_id', $request->id)->where('shared_lesson',1)->count();
            if($countAllLessons > 0)
                return HelperController::api_response_format(400, $course, __('messages.course.canNot_update'));

            $editable[]='shared_lesson';    
        }
        
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

        $course->save();
        return HelperController::api_response_format(200, $course, __('messages.course.update'));
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

        $course->delete();
        $request['returnmsg'] = 'Course Deleted Successfully';
        $request = new Request($request->only(['returnmsg']));
        $print=self::get($request);
        return $print;
        // return HelperController::api_response_format(200, Course::with(['category', 'attachment'])->paginate(HelperController::GetPaginate($request)), 'Course Deleted Successfully');
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
        $request->validate([
            'year' => 'required|exists:academic_years,id',
            'type' => 'required|exists:academic_types,id',
            'segment' => 'required|exists:segments,id',
            'level' => 'required|exists:levels,id',
            'class' => 'required|exists:classes,id',
        ]);

        $courses_of_level = Course::where('level_id' , $request->level)->where('segment_id' , $request->segment)->select('id')->pluck('id');
        $admins=User::select('id')->whereHas('roles',function($q){  $q->where('id',1);  })->get();

        foreach($courses_of_level as $course){
            $lessons = Lesson::where('course_id', $course)->where('shared_lesson', 1)->select(['shared_classes','course_id','id']);
            foreach($lessons->cursor() as $lesson){
                $array = json_decode($lesson->getOriginal('shared_classes'));
                if(!in_array($request->class, $array))
                    array_push($array , $request->class);
                    $lesson->update(['shared_classes' => json_encode($array)]);
            }
             $class_of_course = Course::whereId($course);
             $shared_classes = $class_of_course->first()->classes;

             if(!in_array($request->class, $shared_classes))
                array_push($shared_classes , $request->class);
             $class_of_course->update(['classes' => json_encode($shared_classes)]);

             foreach($admins as $admin){
                Enroll::firstOrCreate([
                    'user_id'=> $admin->id,
                    'role_id' => 1,
                    'year' => $request->year,
                    'type' => $request->type,
                    'segment' => $request->segment,
                    'level' => $request->level,
                    'group' => $request->class,
                    'course' => $course,
                ]);
            }
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
        $lessons = SecondaryChain::whereIn('enroll_id',$enrolls->pluck('id'))->pluck('lesson_id')->unique();
        if(isset($request->lesson_id))
            $lessons = [$request->lesson_id];

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


    public function sorted_components_in_lesson(Request $request,$count = null)
    {
        $request->validate([
            'lesson_id' => 'required|integer|exists:lessons,id'
        ]);

        $page = Paginate::GetPage($request);
        $paginate = Paginate::GetPaginate($request);

        $materials_query =  LessonComponent::orderBy('index','ASC');


        $material = $materials_query->with(['lesson.course:id'])->where('lesson_id',$request->lesson_id);
        if($request->user()->can('site/course/student')){
            $material
            ->where('visible',1)
            // ->where('publish_date' ,'<=', Carbon::now())
            ->where(function($query) {                //Where accessible
                $query->whereHasMorph(
                    'item',
                    [
                        'Modules\Page\Entities\page',
                        'Modules\UploadFiles\Entities\media',
                        'Modules\UploadFiles\Entities\file',
                        'Modules\QuestionBank\Entities\quiz',
                        'Modules\Assigments\Entities\assignment',
                    ],
                    function($query){
                        $query->doesntHave('courseItem')
                        ->orWhereHas('courseItem.courseItemUsers', function (Builder $query){
                            $query->where('user_id', Auth::id());
                        });
                    }
                );
            });
        }
        $result['last_page'] = Paginate::allPages($material->count(),$paginate);
        $result['total']= $material->count();

        $AllMat=$material->skip(($page)*$paginate)->take($paginate)
                    ->with('item')->get();
        $result['data'] =  $AllMat;
        $result['current_page']= $page + 1;
        $result['per_page']= count($result['data']);

        return response()->json(['message' => __('messages.materials.list'), 'body' =>$result], 200);
    }
}
