<?php

namespace App\Http\Controllers\TemplateCards;

use App\User;
use App\Enroll;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Permission;
use App\Http\Controllers\Controller;
use App\Repositories\ChainRepositoryInterface;
use App\Http\Controllers\ReportCardsController;

class OneCourseTemplateController extends Controller
{
    public function __construct(ChainRepositoryInterface $chain)
    {
        $this->chain = $chain;
    }

    public function oneCourseGrades(Request $request)
    {
        $request->validate([
            'course_id'=> 'exists:courses,id',
        ]);
        
        $user_id= Auth::id();
        $GLOBALS['user_id'] = $user_id;

        $allowed_levels=null;
        $check=[];

        // if($request->user()->can('report_card/'.substr(env('App_URL'),8,-18).'/first-term'))
        if($request->user()->can('report_card/gci/first-term'))
            $allowed_levels=Permission::where('name','report_card/gci/first-term')->pluck('allowed_levels')->first();
            // $allowed_levels=Permission::where('name','report_card/'.substr(env('App_URL'),8,-18).'/first-term-2022')->pluck('allowed_levels')->first();

        $student_levels = Enroll::where('user_id',$user_id)->pluck('level')->toArray();
        if($allowed_levels != null){
            $allowed_levels=json_decode($allowed_levels);
            $check=(array_intersect($allowed_levels, $student_levels));
        }

        if(count($check) == 0)
            return response()->json(['message' => 'You are not allowed to see report card', 'body' => null ], 200);

        $grade_category_callback = function ($qu) use ($user_id) {
            $qu->whereNull('parent')->select('id','name','course_id','type','parent','min','max')
                ->with(['Children:id,name,course_id,type,parent,min,max','Children.userGrades' => function($query) use ($user_id){
                    $query->where("user_id", $user_id);
                },'GradeItems.userGrades' => function($query) use ($user_id){
                    $query->where("user_id", $user_id);
                },'userGrades' => function($query) use ($user_id){
                    $query->where("user_id", $user_id);
                }]);
        };

        if(isset($request->course_id))
        {
            $course_callback = function ($qu) use ($request) {
                $qu->where('id', $request->course_id);
            };
        }
        else{
            $obj=new ReportCardsController($this->chain);
            $courses=$obj->getGradesCourses($request,1);
            $course_callback = function ($qu) use ($courses) {
                $qu->whereIn('id', $courses);
            };
        }

        $callback = function ($qu) use ($request , $course_callback ,$grade_category_callback) {
            $qu->where('role_id', 3);
            $qu->whereHas('courses' , $course_callback)
                ->with(['courses' => $course_callback]); 
            $qu->whereHas('courses.gradeCategory' , $grade_category_callback)
                ->with(['courses:id','courses.gradeCategory' => $grade_category_callback]); 
        };

        $result = User::select('id','firstname','lastname')->whereId($user_id)->whereHas('enroll' , $callback)
                        ->with(['enroll' => $callback , 'enroll.levels:id,name' ,'enroll.year:id,name' , 'enroll.type:id,name' , 'enroll.classes:id,name'])->first();

        return response()->json(['message' => null, 'body' => $result ], 200);
    }
}
