<?php

namespace App\Http\Controllers;

use App\Course;
use App\CourseSegment;
use App\Enroll;
use App\User;
use Illuminate\Http\Request;
use App\UserGrader;
use stdClass;
use App\GradeCategory;
use App\GradeItems;
use App\LastAction;
use App\Grader\gradingMethodsInterface;
use App\Events\RefreshGradeTreeEvent;
use Auth;
use App\Events\UserGradesEditedEvent;
use App\Events\GradeCalculatedEvent;
use Spatie\Permission\Models\Permission;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\GradesExport;
use App\Repositories\ChainRepositoryInterface;
use Illuminate\Support\Facades\Storage;
use App\LetterDetails;
use App\ScaleDetails;
use DB;

class UserGradeController extends Controller
{
    public function __construct(ChainRepositoryInterface $chain)
    {
        $this->chain = $chain;
        $this->middleware('auth');
    }

    /**
     * create User grade
     */
    public function store(Request $request)   
    { 
        $request->validate([
            'user'      =>'required|array',
            'user.*.user_id' => 'required|exists:users,id',
            'user.*.item_id'   => 'required|exists:grade_categories,id',
            'user.*.grade'     => 'nullable',
            'user.*.scale_id'     => 'nullable|exists:scale_details,id',
        ]);
        foreach($request->user as $user){
            $percentage = 0;
            $instance = GradeCategory::find($user['item_id']);

            if($instance->max != null && $instance->max > 0){

                if($instance->aggregation == 'Scale'){
                    $scale = ScaleDetails::find( $user['scale_id']);
                    $user['grade'] = $scale->grade;
                    $percentage = ($scale->grade / $instance->max) * 100;

                    UserGrader::updateOrCreate(
                        ['item_id'=>$user['item_id'], 'item_type' => 'category', 'user_id' => $user['user_id']],
                        ['scale' =>  $scale->evaluation , 'scale_id' => $scale->id ]
                    );

                }
                $percentage = ($user['grade'] / $instance->max) * 100;
            }            
            $grader = UserGrader::updateOrCreate(
                ['item_id'=>$user['item_id'], 'item_type' => 'category', 'user_id' => $user['user_id']],
                ['grade' =>  $user['grade'] , 'percentage' => $percentage ]
            );
            if($instance->parent != null)
                event(new UserGradesEditedEvent(User::find($user['user_id']) , $instance->Parents));
            event(new GradeCalculatedEvent($grader));
        }
        return response()->json(['message' => __('messages.user_grade.update'), 'body' => null ], 200);
    } 

    /**
     * update User grade
     *
     * @param  [int] id, grade_item_id, user_id, raw_grade, raw_grade_max, raw_grade_min, raw_scale_id, final_grade,
     *              letter_id
     * @param  [boolean] hidden, locked
     * @param  [string] feedback
     * @return [object] and [string] User Grade updated Successfully
     */
    public function update(Request $request)
    {
        $request->validate([
            'items'             => 'required|array',
            'items.*.id'        => 'required|integer|exists:user_grades,id',
            'items.*.raw_grade'     => 'required|integer',
            'items.*.feedback'  => 'nullable|string',
        ]);
        foreach($request->items as $item){
            $grade = UserGrade::find($item['id']);
            unset($item['id']);
            $grade->update($item);
        }
        return HelperController::api_response_format(200, null, 'User Grade Updated Successfully');
    }

    /**
     * delete User grade
     *
     * @param  [int] id
     * @return [string] User Grade deleted Successfully
     */
    public function delete(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:user_grades,id',
        ]);

        $grade = UserGrade::find($request->id);
        $grade->delete();

        return HelperController::api_response_format(201, null, 'User Grade Deleted Successfully');
    }

    /**
     * list User grades
     *
     * @return [objects] grades
     */
    public function list()
    {
        $grade = UserGrade::all();
        return HelperController::api_response_format(200, $grade);
    }

    public function SingleUserInSingleCourse(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'course' => 'required|exists:courses,id',
            'class' => 'required|exists:classes,id'
        ]);
        $courseSeg = CourseSegment::GetWithClassAndCourse($request->class, $request->course);
        if (!$courseSeg)
            return HelperController::api_response_format(200, 'this course haven\'t course segment');
        LastAction::lastActionInCourse($request->course);
        
        $gradeCat_item = GradeCategory::where('course_segment_id', $courseSeg->id)->first();
        if(isset($gradeCat_item))
        {
            $gradeCat_item->getUsergrades($request->user_id);
            return HelperController::api_response_format(200, $gradeCat_item);
        }
        else
            return HelperController::api_response_format(200, 'there is no grade item');
    }

    public function AllUserInCourse(Request $request)
    {
        $request->validate([
            'course' => 'required|exists:courses,id',
            'class' => 'required|exists:classes,id'
        ]);

        $courseSeg = CourseSegment::GetWithClassAndCourse($request->class, $request->course);
        if (!$courseSeg)
            return HelperController::api_response_format(201, 'this course haven\'t course segment');

        $gradeCat = GradeCategory::where('course_segment_id', $courseSeg->id)->with('GradeItems')->get();
        $gradeitems = $gradeCat->pluck('GradeItems');
        $userGrade = [];
        foreach ($gradeitems as $items)
            foreach ($items as $item) {
                if (!isset($item))
                    continue;
                $temp = UserGrade::where('grade_item_id', $item->id)->with('GradeItems', 'GradeItems.GradeCategory')->get();
                if (count($temp) > 0)
                    $userGrade[] = $temp;
            }
        return $userGrade;
    }

    public function AllUserInAllCourses(Request $request)
    {
        $request->validate([
            'year' => 'exists:academic_years,id',
            'type' => 'exists:academic_types,id',
            'level' => 'exists:levels,id|required_with:type',
            'class' => 'exists:classes,id|required_with:level',
        ]);

        $courses_segment = GradeCategoryController::getCourseSegment($request);
        if (isset($courses_segment)) {
            $gradeCat = GradeCategory::whereIn('course_segment_id', $courses_segment)->with('GradeItems')->get();
            $gradeitems = $gradeCat->pluck('GradeItems');
            $userGrade = [];
            foreach ($gradeitems as $items)
                foreach ($items as $item) {
                    if (!isset($item))
                        continue;
                    $temp = UserGrade::where('grade_item_id', $item->id)->with('GradeItems', 'GradeItems.GradeCategory')->get();
                    if (count($temp) > 0)
                        $userGrade[] = $temp;
                }
            return HelperController::api_response_format(201, $userGrade);
        }
        return HelperController::api_response_format(200, 'There is No Course segment available.');
    }

    public function TopStudent(Request $request)
    {
        $request->validate([
            'course' => 'required|exists:courses,id',
            'class' => 'required|exists:classes,id'
        ]);
        $courseSeg = CourseSegment::GetWithClassAndCourse($request->class, $request->course);
        if (!$courseSeg)
            return HelperController::api_response_format(201, 'this course haven\'t course segment');
            // $gradeCat = GradeCategory::where('name','Course Total')->whereIn('course_segment_id', $courseSeg)
            //                 ->with('GradeItems')->first();
        $gradeCat = GradeCategory::where('course_segment_id', $courseSeg->id)->with('GradeItems')->first();
        foreach ($gradeCat['GradeItems'] as $items) {
            if (!isset($items))
                continue;
            $temp = UserGrade::where('grade_item_id', $items->id)->with('GradeItems', 'GradeItems.GradeCategory')->get();
            if (count($temp) > 0)
                $userGrade[] = $temp;
        }

        $i = 0;
        $useGradesss = array();
        foreach ($userGrade as $userGra)
            foreach ($userGra as $useG) {
                $useGradesss[$i]['id'] = $useG->user_id;
                $useGradesss[$i]['grade'] = $useG->calculateGrade();
                $i++;
            }

        $col = collect($useGradesss);
        $return = $col->sortByDesc('grade');
        $r = $return->values()->take(5);
        $top = $r->all();
        $topusers = array();
        $j = 0;
        foreach ($top as $t) {
            $topusers[$j] = User::find($t['id']);
            $topusers[$j]['grade'] = $t['grade'];
            $j++;
        }
        return HelperController::api_response_format(200, $topusers, 'There is the Top Students');
    }

    public function courseGrades(Request $request)
    {
        $courseseg=Enroll::where('user_id',$request->user()->id)->pluck('course_segment')->unique();
        $cour=array();
        $i = 0;
        $userGrade=[];
        foreach($courseseg as $course)
        {
            $gradeCat = GradeCategory::where('name','Course Total')->where('course_segment_id', $course)
                            ->with('GradeItems')->first();
            if(isset($gradeCat)){
                foreach ($gradeCat['GradeItems'] as $items) {
                    if (!isset($items))
                        continue;
                    $temp = UserGrade::where('user_id',$request->user()->id)->where('grade_item_id', $items->id)->get();
                    if (count($temp) > 0)
                        $userGrade[] = $temp;
                }
                if(isset($userGrade))
                {
                    foreach ($userGrade as $userGra)
                    foreach ($userGra as $useG) {
                        //grade of course total
                        $cour[$i] = $useG->GradeItems->GradeCategory->CourseSegment->courses[0];
                        $cour[$i]['grade'] = $useG->calculateGrade();
                        $cour[$i]['class'] = User::find($useG->user_id)->class_id;
                    }
                    $i++;
                }
            }
        }
        return HelperController::api_response_format(200, array_values($cour));
    }


    public function fglReport(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $allowed_levels=Permission::where('name','report_card/fgls')->pluck('allowed_levels')->first();
        $allowed_levels=json_decode($allowed_levels);
        $student_levels = Enroll::where('user_id',$request->user_id)->pluck('level')->toArray();
        $check=(array_intersect($allowed_levels, $student_levels));

        $total_check=(array_intersect([6, 7 ,8 , 9, 10 , 11 , 12], $student_levels));

        if(count($check) == 0)
            return response()->json(['message' => 'You are not allowed to see report card', 'body' => null ], 200);
        $total = 0;
        $student_mark = 0;
        $grade_category_callback = function ($qu) use ($request ) {
            $qu->where('name', 'First Term');
            $qu->with(['userGrades' => function($query) use ($request){
                $query->where("user_id", $request->user_id);
            }]);     
        };

        $callback = function ($qu) use ($request , $grade_category_callback) {
            // $qu->orderBy('course', 'Asc');
            $qu->where('role_id', 3);
            $qu->whereHas('courses.gradeCategory' , $grade_category_callback)
                ->with(['courses.gradeCategory' => $grade_category_callback]); 

        };

        $result = User::whereId($request->user_id)->whereHas('enroll' , $callback)
                        ->with(['enroll' => $callback , 'enroll.levels' ,'enroll.year' , 'enroll.type' , 'enroll.classes'])->first();
        $result->enrolls =  collect($result->enroll)->sortBy('courses.created_at')->values();

        foreach($result->enrolls as $enroll){ 
            if($enroll->courses->gradeCategory != null)
                $total += $enroll->courses->gradeCategory[0]->max;

            if($enroll->courses->gradeCategory[0]->userGrades != null)
                $student_mark += $enroll->courses->gradeCategory[0]->userGrades[0]->grade;
            
            if(str_contains($enroll->courses->name, 'O.L'))
                break;

        }

         $percentage = 0;
         if($total != 0)
            $percentage = ($student_mark /$total)*100;

        $evaluation = LetterDetails::select('evaluation')->where('lower_boundary', '<=', $percentage)
                    ->where('higher_boundary', '>', $percentage)->first();

        if($percentage == 100)
            $evaluation = LetterDetails::select('evaluation')->where('lower_boundary', '<=', $percentage)
            ->where('higher_boundary', '>=', $percentage)->first();

        $result->total = $total;
        $result->student_total_mark = $student_mark;
        $result->evaluation = $evaluation->evaluation;
        $result->add_total = true;
        unset($result->enroll);
        if(count($total_check) == 0)
            $result->add_total = false;

        return response()->json(['message' => null, 'body' => $result ], 200);
    }


    public function export(Request $request)
    {
        $request->validate([
            'courses'    => 'required|array',
            'courses.*'  => 'required|integer|exists:courses,id', 
            'classes' => 'array',
            'classes.*' => 'exists:classes,id',
            ]);     

        $grade_categories = GradeCategory::whereIn('course_id', $request->courses)->withoutGlobalScopes();
        $cat_ids =  $grade_categories->get()->pluck('id')->toArray();

        $grade_Categroies_ids = $grade_categories
        ->select( DB::raw('CONCAT("item_",name, "_", id) AS name'))
        ->pluck('name')->toArray();
        
        $headers =array_merge(array('fullname','username' , 'course'), $grade_Categroies_ids);

        $students = $this->chain->getEnrollsByManyChain($request)->where('role_id',3)->select('user_id')->distinct('user_id')
        ->whereHas('user')->with(array('user' => function($query) {
            $query->addSelect(array('id' , 'username' , 'firstname' , 'lastname'));
        }))->get();
        $course_shortname = GradeCategory::whereIn('course_id', $request->courses)->first()->course->short_name;
        $filename = $course_shortname;
        $file = Excel::store(new GradesExport($headers , $students , $request->courses[0] , $cat_ids), 'Grades'.$filename.'.xlsx','public');
        $file = url(Storage::url('Grades'.$filename.'.xlsx'));
        return HelperController::api_response_format(201,$file, __('messages.success.link_to_file'));
    }

    public function user_report_in_course(Request $request)
    {
        $request->validate([
            'course_id'  => 'required|integer|exists:courses,id',
            'user_id' => 'exists:users,id',
            ]); 
            
        if(!$request->filled('user_id'))
            $request->user_id = Auth::id();

        $GLOBALS['user_id'] = $request->user_id;

        $grade_categories = GradeCategory::where('course_id', $request->course_id)->whereNull('parent')
                            ->with(['Children.userGrades' => function($query) use ($request){
                                $query->where("user_id", $request->user_id);
                            },'GradeItems.userGrades' => function($query) use ($request){
                                $query->where("user_id", $request->user_id);
                            },'userGrades' => function($query) use ($request){
                                $query->where("user_id", $request->user_id);
                            }])->get();

        return response()->json(['message' => __('messages.grade_category.list'), 'body' => $grade_categories], 200);

    }

    public function user_report_in_all_courses(Request $request)
    {
        $courses = $this->chain->getEnrollsByManyChain($request)->where('user_id',Auth::id())->pluck('course');
        if(!$request->filled('user_id'))
            $request->user_id = Auth::id();

        $GLOBALS['user_id'] = Auth::id();
        $grade_categories = GradeCategory::whereIn('course_id', $courses)->whereNull('parent')
                            ->with(['userGrades' => function($query) use ($request){
                                $query->where("user_id", $request->user_id);
                            }])->get();

        return response()->json(['message' => __('messages.grade_category.list'), 'body' => $grade_categories], 200);

    }
}


