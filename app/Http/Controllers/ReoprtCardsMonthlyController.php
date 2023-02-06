<?php

namespace App\Http\Controllers;
use App\Repositories\ChainRepositoryInterface;
use App\User;
use Spatie\Permission\Models\Permission;
use App\Enroll;
use Illuminate\Support\Facades\Auth;


use Illuminate\Http\Request;

class ReoprtCardsMonthlyController extends Controller
{
    public function __construct(ChainRepositoryInterface $chain)
    {
        $this->chain = $chain;
    }

    public function gciProgressReportAll(Request $request)
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
                    ->with(['courses.gradeCategory' => $grade_category_callback]); 
            };
            $result = User::select('id','username','lastname', 'firstname')->whereId($user_id)->whereHas('enroll' , $callback)
                            ->with(['enroll' => $callback , 'enroll.levels:id,name' ,'enroll.year:id,name' , 'enroll.type:id,name' , 'enroll.classes:id,name'])->first();
            if($result != null)
                $result_collection->push($result);
        }
        return response()->json(['message' => null, 'body' => $result_collection ], 200);
    }

    public function gciProgressReport(Request $request)
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
        if($check !=null)
            return response()->json(['message' => 'You are not allowed to see report card', 'body' => null ], 200);

        $result_collection = collect([]);
        $user_id = Auth::id();

        $grade_CatsT2=['T2-Quiz','T2-Homework','T2-Assignment','T2-Classwork','T2-Project'];
        $grade_CatsT3=['T3-Quiz','T3-Homework','T3-Assignment','T3-Classwork','T3-Project'];
        
        $GLOBALS['user_id'] = $user_id;
        $grade_category_callback = function ($qu) use ($user_id , $request) {
            // $qu->whereNull('parent')
            $qu->where('name','LIKE',"%$request->trimester%");
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
                ->with(['courses.gradeCategory' => $grade_category_callback]); 
        };
        $result = User::select('id','username','lastname', 'firstname')->whereId($user_id)->whereHas('enroll' , $callback)
                        ->with(['enroll' => $callback , 'enroll.levels:id,name' ,'enroll.year:id,name' , 'enroll.type:id,name' , 'enroll.classes:id,name'])->first();
        if($result != null)
            $result_collection->push($result);
        
        return response()->json(['message' => null, 'body' => $result_collection ], 200);
    }
}
