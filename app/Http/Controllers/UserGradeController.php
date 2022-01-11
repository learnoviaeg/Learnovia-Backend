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
        ]);
        foreach($request->user as $user){
            $percentage = 0;
            $instance = GradeCategory::find($user['item_id']);

            if($instance->max != null && $instance->max > 0)
                    $percentage = ($user['grade'] / $instance->max) * 100;

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

    public function graderReport(Request $request)
    {
        $request->validate([
            'course' => 'required|exists:courses,id',
            'class'  => 'required|exists:classes,id',
            'user'   => 'nullable|exists:users,id',
            'search' => 'nullable|string'
        ]);
        $courseSegment = CourseSegment::GetWithClassAndCourse($request->class, $request->course);
        if ($courseSegment == null)
            return HelperController::api_response_format(200, null, 'This Course not assigned to this class');
        LastAction::lastActionInCourse($request->course);
        $users = User::whereIn('id', Enroll::where('course_segment', $courseSegment->id)->where('role_id', 3)->pluck('user_id'));
        if($request->filled('user'))
            $users->whereId($request->user);

        if($request->filled('search'))
            $users->where('username' , 'like', '%' . $request->search. '%');
        $users = $users->get(['id', 'firstname', 'lastname', 'username', 'arabicname', 'picture']);
        $gradeCategories = $courseSegment->where('id', $courseSegment->id)->with('GradeCategory.GradeItems')->get()->pluck('GradeCategory')[0];
        $first = true;
        $grades = [];
        $ids = [];
        foreach ($users as $user) {
            UserGrade::quizUserGrade($user);
            $user->grades = collect();
            $i = 0;
            if(isset($user->attachment))
                $user->picture=$user->attachment->path;
            foreach ($gradeCategories as $category) {
                $grades[$i]['items'] = collect();
                $grades[$i]['name'] = $category->name;
                $grades[$i]['id'] = $category->id;
                $grades[$i]['weight'] = $category->weight();
                $grades[$i]['max'] = $category->total();
                $user->grades[$i] = collect();
                $user->grades[$i]['total'] = 0;
                $user->grades[$i]['name'] = $category->name;
                $user->grades[$i]['id'] = $category->id;
                $user->grades[$i]['data'] = collect();
                foreach ($category->GradeItems as $item) {
                    $temp = UserGrade::where('user_id', $user->id)->where('grade_item_id', $item->id)->first();
                    if ($temp != null && $first) {
                        $user->grades[$i]['total'] = $temp->calculateGrade();
                        $first = false;
                        $temp->grade_items = null;
                    }
                    $usergrade = new stdClass();
                    $usergrade->name = $item->name;
                    $usergrade->id = $item->id;
                    $ids[] = $item->id;
                    $usergrade->final_grade = null;
                    $usergrade->max = $item->grademax;
                    if ($temp != null)
                        $usergrade->final_grade = $temp->final_grade;
                    $user->grades[$i]['data']->push($usergrade);
                    $grades[$i]['items']->push(collect(['name' => $item->name, 'id' => $item->id, 'max' => $item->grademax, 'weight' => $item->weight()]));
                }
                $first = true;
                $i++;
            }
        }
        return HelperController::api_response_format(200, ['schema' => $grades, 'users' => $users, 'ids' => array_unique($ids)]);
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

        // $callbacks = function ($qu) use ($request) {
        //     $qu->where('user_id', $request->user_id);
        // };

        // $callback = function ($query) use ($callbacks) {
        //     $query->where('name', 'First Term');
        //     $query->whereHas('userGrades' , $callbacks)
        //           ->with(['userGrades' => $callbacks]);
        // };
        // $result = User::whereId($request->user_id)->whereHas('enroll.courses.gradeCategory' , $callback)
        //                 ->with(['enroll.courses.gradeCategory' => $callback])->first();
        $allowed_levels=Permission::where('name','report_card/fgl')->pluck('allowed_levels')->first();
        $allowed_levels=json_decode($allowed_levels);
        $check=(array_intersect($allowed_levels,Enroll::where('user_id',Auth::id())->pluck('level')->toArray()));

        if(count($check) == 0)
            return response()->json(['message' => 'You are not allowed to see report card', 'body' => null ], 200);

        $result = User::whereId($request->user_id)->with(['enroll' => function($query) use ($request){
            $query->where("role_id", 3);
            }, 'enroll.courses.gradeCategory'=> function($query) use ($request){
                $query->where("name", 'First Term');
                $query->with(['userGrades' => function ($q) use ($request) {
                    $q->where('user_id', $request->user_id);
                }]);
            }])->first();
 
        foreach($result->enroll as $key => $course){
            if(count($course->courses->gradeCategory) == 0)
                unset($result->enroll[$key]);

        }

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

        $grade_categories = GradeCategory::whereIn('course_id', $request->courses)->get()->pluck('name')->toArray();
        array_walk($grade_categories, function(&$value, $key) { $value = 'item_'.$value; } );
        $headers =array_merge(array('username' , 'course'), $grade_categories);

        $students = $this->chain->getEnrollsByManyChain($request)->where('role_id',3)->select('user_id')->distinct('user_id')
        ->with(array('user' => function($query) {
            $query->addSelect(array('id' , 'username'));
        }))->get();
        
        $filename = uniqid();
        $file = Excel::store(new GradesExport($headers , $students , $request->courses[0]), 'Grades'.$filename.'.xlsx','public');
        $file = url(Storage::url('Type'.$filename.'.xlsx'));
        return HelperController::api_response_format(201,$file, __('messages.success.link_to_file'));
    }
}


