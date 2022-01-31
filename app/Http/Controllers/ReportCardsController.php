<?php

namespace App\Http\Controllers;
use App\Repositories\ChainRepositoryInterface;
use App\Enroll;
use App\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;

class ReportCardsController extends Controller
{
    public function __construct(ChainRepositoryInterface $chain)
    {
        $this->chain = $chain;
        $this->middleware('auth');
        $this->middleware(['permission:report_card/haramain'],   ['only' => ['haramainReport']]);
    }

    public function haramainReport(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $allowed_levels=Permission::where('name','report_card/haramain')->pluck('allowed_levels')->first();
        $allowed_levels=json_decode($allowed_levels);

        $student_levels = Enroll::where('user_id',$request->user_id)->pluck('level')->toArray();
        $check=(array_intersect($allowed_levels, $student_levels));

        if(count($check) == 0)
            return response()->json(['message' => 'You are not allowed to see report card', 'body' => null ], 200);

        $grade_category_callback = function ($qu) use ($request ) {
            $qu->where('type', 'item');
            $qu->with(['userGrades' => function($query) use ($request){
                $query->where("user_id", $request->user_id);
            }]);     
        };

        $course_callback = function ($qu) use ($request ) {
            $qu->Where(function ($query) {
                $query->where('name', 'LIKE' , "%Grades%")
                      ->orWhere('name','LIKE' , "%درجات%");
            });     
        };

        $callback = function ($qu) use ($request , $course_callback , $grade_category_callback) {
            $qu->where('role_id', 3);
            $qu->whereHas('courses' , $course_callback)
                ->with(['courses' => $course_callback]); 
            $qu->whereHas('courses.gradeCategory' , $grade_category_callback)
                ->with(['courses.gradeCategory' => $grade_category_callback]); 
        };

        $result = User::whereId($request->user_id)->whereHas('enroll' , $callback)
                        ->with(['enroll' => $callback , 'enroll.levels' , 'enroll.type'])->first();

        return response()->json(['message' => null, 'body' => $result ], 200);
    }

    public function forsanReport(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);
        $GLOBALS['user_id'] = $request->user_id;

        // $allowed_levels=Permission::where('name','report_card/haramain')->pluck('allowed_levels')->first();
        // $allowed_levels=json_decode($allowed_levels);

        // $student_levels = Enroll::where('user_id',$request->user_id)->pluck('level')->toArray();
        // $check=(array_intersect($allowed_levels, $student_levels));

        // if(count($check) == 0)
        //     return response()->json(['message' => 'You are not allowed to see report card', 'body' => null ], 200);

        $grade_category_callback = function ($qu) use ($request ) {
            $qu->whereNull('parent')
            ->with(['Children.userGrades' => function($query) use ($request){
                $query->where("user_id", $request->user_id);
            },'GradeItems.userGrades' => function($query) use ($request){
                $query->where("user_id", $request->user_id);
            },'userGrades' => function($query) use ($request){
                $query->where("user_id", $request->user_id);
            }]);

            // $qu->with(['userGrades' => function($query) use ($request){
            //     $query->where("user_id", $request->user_id);
            // }]);     
        };

        $course_callback = function ($qu) use ($request ) {
            $qu->Where(function ($query) {
                $query->where('name', 'LIKE' , "%Grades%")
                      ->orWhere('name','LIKE' , "%درجات%"); 
            });     
        };

        $callback = function ($qu) use ($request , $course_callback , $grade_category_callback) {
            $qu->where('role_id', 3);
            $qu->whereHas('courses' , $course_callback)
                ->with(['courses' => $course_callback]); 
            $qu->whereHas('courses.gradeCategory' , $grade_category_callback)
                ->with(['courses.gradeCategory' => $grade_category_callback]); 
        };

        $result = User::whereId($request->user_id)->whereHas('enroll' , $callback)
                        ->with(['enroll' => $callback , 'enroll.levels' , 'enroll.type'])->first();

        return response()->json(['message' => null, 'body' => $result ], 200);
    }
}
