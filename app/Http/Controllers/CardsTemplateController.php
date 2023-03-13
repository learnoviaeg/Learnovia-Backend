<?php

namespace App\Http\Controllers;
use App\Repositories\ChainRepositoryInterface;
use App\Enroll;
use App\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Auth;

class CardsTemplateController extends Controller
{
    public function __construct(ChainRepositoryInterface $chain)
    {
        $this->chain = $chain;
        $this->middleware('auth');
    }

    // course mogame3
    public function template1(Request $request)
    {
        $request->validate([
            'course_id'=> 'exists:courses,id',
        ]);
                
        $GLOBALS['user_id'] = Auth::id();
        $user=User::find(Auth::id());
        $allowed_levels=null;
        $check=[];

        if($user->can('report_card/mfisb/first-term-2022-b'))
            $allowed_levels=Permission::where('name','report_card/mfisb/first-term-2022-b')->pluck('allowed_levels')->first();

        if($user->can('report_card/mfisg/first-term-2022-g'))
            $allowed_levels=Permission::where('name','report_card/mfisg/first-term-2022-g')->pluck('allowed_levels')->first();

        if($user->can('report_card/green-city/first-term-2022'))
            $allowed_levels=Permission::where('name','report_card/green-city/first-term-2022')->pluck('allowed_levels')->first();

        if($user->can('report_card/alraya/first-term-2022'))
            $allowed_levels=Permission::where('name','report_card/alraya/first-term-2022')->pluck('allowed_levels')->first();

        if($user->can('report_card/child-palace/first-term-2022'))
            $allowed_levels=Permission::where('name','report_card/child-palace/first-term-2022')->pluck('allowed_levels')->first();

        $student_levels = Enroll::where('user_id', $user->id)->pluck('level')->toArray();
        if($allowed_levels != null){
            $allowed_levels=json_decode($allowed_levels);
            $check=(array_intersect($allowed_levels, $student_levels));
        }

        if(count($check) == 0)
           return response()->json(['message' => 'You are not allowed to see report card', 'body' => null ], 200);

        $obj=null;
        $obj=new ReportCardsController($this->chain);
        $courses=$obj->getGradesCourses($request,1);

        $grade_category_callback = function ($qu) use ($request,$user) {
            $qu->whereNull('parent')->select('id','name','course_id','type','parent','min','max')
                ->with(['Children:id,name,course_id,type,parent,min,max','Children.userGrades' => function($query) use ($request,$user){
                    $query->where("user_id", $user->id)
                        ->select('item_id','user_id','grade','scale','letter','percentage');
                },'Children.GradeItems:id,name,course_id,parent,min,max,type','GradeItems.userGrades' => function($query) use ($request,$user){
                    $query->where("user_id", $user->id);
                    // ->select('item_id','user_id','grade','scale','letter','percentage');
                },'userGrades' => function($query) use ($request,$user){
                    $query->where("user_id", $user->id)
                     ->select('item_id','user_id','grade','scale','letter','percentage');
                }]); 
        };

        $course_callback = function ($qu) use ($courses) {
            $qu->where('id', $courses);
        };

        if(isset($request->course_id))
        {
            $course_callback = function ($qu) use ($request) {
                $qu->where('id', $request->course_id);
            };
        }

        $callback = function ($qu) use ($request , $course_callback ,$grade_category_callback) {
            $qu->where('role_id', 3);
            $qu->whereHas('courses' , $course_callback)
                ->with(['courses' => $course_callback]); 
            $qu->whereHas('courses.gradeCategory' , $grade_category_callback)
                ->with(['courses:id','courses.gradeCategory' => $grade_category_callback]); 
        };

        $result = User::select('id','firstname','lastname')->whereId($user->id)->whereHas('enroll' , $callback)
                        ->with(['enroll' => $callback , 'enroll.levels:id,name' ,'enroll.year:id,name' , 'enroll.type:id,name' , 'enroll.classees:id,name'])->first();
        return response()->json(['message' => null, 'body' => $result ], 200);
    }

    public function template1All(Request $request)
    {
        $request->validate([
            'years'    => 'nullable|array',
            'years.*' => 'exists:academic_years,id',
            'types'    => 'nullable|array',
            'types.*' => 'exists:academic_types,id',
            'levels'    => 'nullable|array',
            'levels.*' => 'exists:levels,id',
            'classes'    => 'nullable|array',
            'classes.*' => 'exists:classes,id',
            'segments'    => 'nullable|array',
            'segments.*' => 'exists:segments,id',
            'courses' => 'required|array',
            'courses.*' => 'required|exists:courses,id',
            // 'term'    => 'required|in:first,final',
        ]);
        $result_collection = collect([]);
        $user_ids = $this->chain->getEnrollsByManyChain($request)->distinct('user_id')->pluck('user_id');
        foreach($user_ids as $user_id){
            $GLOBALS['user_id'] = $user_id;
            $grade_category_callback = function ($qu) use ($user_id , $request) {
                $qu->whereNull('parent')->select('id','name','course_id','type','parent','min','max')
                    ->with(['Children:id,name,type,course_id,parent,min,max','Children.userGrades' => function($query) use ($user_id , $request){
                        $query->where("user_id", $user_id)
                            ->select('item_id','user_id','grade','scale','letter','percentage');
                    },'Children.GradeItems:id,name,course_id,parent,min,max,type','GradeItems.userGrades' => function($query) use ($user_id , $request){
                        $query->where("user_id", $user_id);
                    },'userGrades' => function($query) use ($user_id , $request){
                        $query->where("user_id", $user_id);
                    }]); 
            };

            $course_callback = function ($qu) use ($request ) {
                $qu->where('id',$request->courses);
            };

            $callback = function ($qu) use ($course_callback, $grade_category_callback) {
                $qu->where('role_id', 3);
                $qu->whereHas('courses' , $course_callback)
                    ->with(['courses' => $course_callback]); 
                $qu->whereHas('courses.gradeCategory' , $grade_category_callback)
                    ->with(['course:id','courses.gradeCategory' => $grade_category_callback]); 
            };
            $result = User::select('id','username','lastname', 'firstname', 'profile_fields')->whereId($user_id)->whereHas('enroll' , $callback)
                            ->with(['enroll' => $callback , 'enroll.levels:id,name' ,'enroll.year:id,name' , 'enroll.type:id,name' , 'enroll.classes:id,name'])->first();
            if($result != null)
                $result_collection->push($result);
        }
        return response()->json(['message' => null, 'body' => $result_collection ], 200);
    }

    public function template2(Request $request)
    {
        $request->validate([
            // 'month'   => 'required|in:October,November,December',
            'years'    => 'nullable|array',
            'years.*' => 'exists:academic_years,id',
            'types'    => 'nullable|array',
            'types.*' => 'exists:academic_types,id',
            'levels'    => 'nullable|array',
            'levels.*' => 'exists:levels,id',
            'trimester' => 'required|in:T2,T3'
        ]);

        $check=null;
        $allowed_levels=null;
        if($request->user()->can('report_card/gci/progress-report'))
            $allowed_levels=Permission::where('name','report_card/gci/progress-report')->pluck('allowed_levels')->first();

        $allowed_levels=json_decode($allowed_levels);
        $student_levels = Enroll::where('user_id',Auth::id())->pluck('level')->toArray();
        if($allowed_levels != null)
            $check=array_intersect($allowed_levels, $student_levels);
        if($check == null)
            return response()->json(['message' => 'You are not allowed to see report card', 'body' => null ], 200);

        $result_collection = collect([]);
        $user_id = Auth::id();

        $grade_CatsT2=['T2-Quiz','T2-Homework','T2-Assignment','T2-Classwork','T2-Project'];
        $grade_CatsT3=['T3-Quiz','T3-Homework','T3-Assignment','T3-Classwork','T3-Project'];
        
        $GLOBALS['user_id'] = $user_id;
        $grade_category_callback = function ($qu) use ($user_id , $request) {
            // $qu->whereNull('parent')
            $qu->where('name','LIKE',"%$request->trimester%");
            $qu->where('name','NOT LIKE',"%Total coursework%");
            $qu->where('name','NOT LIKE',"%Trimester exam%")
                ->select('id','name','course_id','type','parent','min','max');

            $qu->with([
            //     'Children.userGrades' => function($query) use ($user_id , $request){
            //     $query->where("user_id", $user_id);
            // }
            // ,'GradeItems.userGrades' => function($query) use ($user_id , $request){
            //     $query->where("user_id", $user_id);
            // },
            'userGrades' => function($query) use ($user_id , $request){
                $query->where("user_id", $user_id);
            }
        ]); 
        };

        $callback = function ($qu) use ($request ,$grade_category_callback) {
            $qu->where('role_id', 3);
            $qu->whereHas('courses.gradeCategory' , $grade_category_callback)
                ->with(['courses:id','courses.gradeCategory' => $grade_category_callback]); 
        };
        $result = User::select('id','username','lastname', 'firstname')->whereId($user_id)->whereHas('enroll' , $callback)
            ->with(['enroll' => $callback , 'enroll.levels:id,name' ,'enroll.year:id,name' , 'enroll.type:id,name' , 'enroll.classes:id,name'])->first();
        
        if($result != null)
            $result_collection->push($result);
        
        return response()->json(['message' => null, 'body' => $result_collection ], 200);
    }

    public function template2All(Request $request)
    {
        $request->validate([
            // 'month'   => 'required|in:October,November,December',
            'years'    => 'nullable|array',
            'years.*' => 'exists:academic_years,id',
            'types'    => 'nullable|array',
            'types.*' => 'exists:academic_types,id',
            'levels'    => 'nullable|array',
            'levels.*' => 'exists:levels,id',
            'trimester' => 'required|in:T2,T3'
        ]);
        $result_collection = collect([]);
        $user_ids = $this->chain->getEnrollsByManyChain($request)->distinct('user_id')->pluck('user_id');

        $grade_CatsT2=['T2-Quiz','T2-Homework','T2-Assignment','T2-Classwork','T2-Project'];
        $grade_CatsT3=['T3-Quiz','T3-Homework','T3-Assignment','T3-Classwork','T3-Project'];

        foreach($user_ids as $user_id){
            $GLOBALS['user_id'] = $user_id;
            $grade_category_callback = function ($qu) use ($user_id , $request) {
                // $qu->whereNull('parent')
                $qu->where('name','LIKE',"%$request->trimester%");
                $qu->where('name','NOT LIKE',"%Total coursework%");
                $qu->where('name','NOT LIKE',"%Trimester exam%")
                ->select('id','name','course_id','type','parent','min','max');
                $qu->with([
                    // 'Children.userGrades' => function($query) use ($user_id , $request){
                //     $query->where("user_id", $user_id);
                // }
                // ,'GradeItems.userGrades' => function($query) use ($user_id , $request){
                //     $query->where("user_id", $user_id);
                // },
                'userGrades' => function($query) use ($user_id , $request){
                    $query->where("user_id", $user_id);
                }
            ]); 
            };

            $callback = function ($qu) use ($request ,$grade_category_callback) {
                $qu->where('role_id', 3);
                $qu->whereHas('courses.gradeCategory' , $grade_category_callback)
                    ->with(['courses:id','courses.gradeCategory' => $grade_category_callback]); 
            };
            $result = User::select('id','username','lastname', 'firstname')->whereId($user_id)->whereHas('enroll' , $callback)
                ->with(['enroll' => $callback , 'enroll.levels:id,name' ,'enroll.year:id,name' , 'enroll.type:id,name' , 'enroll.classes:id,name'])->first();

            if($result != null)
                $result_collection->push($result);
        }
        return response()->json(['message' => null, 'body' => $result_collection ], 200);
    }
}
