<?php

namespace App\Http\Controllers;
use App\Repositories\ChainRepositoryInterface;
use App\Enroll;
use App\User;
use Illuminate\Http\Request;
use App\LetterDetails;
use App\ScaleDetails;
use Spatie\Permission\Models\Permission;

class ReportCardsController extends Controller
{
    public function __construct(ChainRepositoryInterface $chain)
    {
        $this->chain = $chain;
        $this->middleware('auth');
        $this->middleware(['permission:report_card/mfis/girls|report_card/mfis/boys'],   ['only' => ['manaraReport']]);
        $this->middleware(['permission:report_card/mfis/manara-boys/printAll|report_card/mfis/manara-girls/printAll'],   ['only' => ['manaraReportAll']]);
        $this->middleware(['permission:report_card/haramain/all'],   ['only' => ['haramaninReportAll']]);
        $this->middleware(['permission:report_card/forsan/all'],   ['only' => ['forsanReportAll']]);
        $this->middleware(['permission:report_card/fgl/all'],   ['only' => ['fglsReportAll']]);
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
            $qu->whereNull('parent')
            ->with(['Children.userGrades' => function($query) use ($request){
                $query->where("user_id", $request->user_id);
            },'GradeItems.userGrades' => function($query) use ($request){
                $query->where("user_id", $request->user_id);
            },'userGrades' => function($query) use ($request){
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

        $allowed_levels=Permission::where('name','report_card/forsan')->pluck('allowed_levels')->first();
        $allowed_levels=json_decode($allowed_levels);

        $student_levels = Enroll::where('user_id',$request->user_id)->pluck('level')->toArray();
        $check=(array_intersect($allowed_levels, $student_levels));

        if(count($check) == 0)
            return response()->json(['message' => 'You are not allowed to see report card', 'body' => null ], 200);

        $grade_category_callback = function ($qu) use ($request ) {
            $qu->whereNull('parent')
            ->with(['Children.userGrades' => function($query) use ($request){
                $query->where("user_id", $request->user_id);
            },'GradeItems.userGrades' => function($query) use ($request){
                $query->where("user_id", $request->user_id);
            },'userGrades' => function($query) use ($request){
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

    public function manaraReport(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);
        $GLOBALS['user_id'] = $request->user_id;
        $user = User::find($request->user_id);

        if($user->can('report_card/mfis/girls'))
            $allowed_levels=Permission::where('name','report_card/mfis/girls')->pluck('allowed_levels')->first();
        
        if($user->can('report_card/mfis/boys'))
            $allowed_levels=Permission::where('name','report_card/mfis/boys')->pluck('allowed_levels')->first();

        $allowed_levels=json_decode($allowed_levels);
        $student_levels = Enroll::where('user_id',$request->user_id)->pluck('level')->toArray();
        $check=(array_intersect($allowed_levels, $student_levels));

        if(count($check) == 0)
            return response()->json(['message' => 'You are not allowed to see report card', 'body' => null ], 200);

        $grade_category_callback = function ($qu) use ($request ) {
            $qu->whereNull('parent')
            ->with(['Children.userGrades' => function($query) use ($request){
                $query->where("user_id", $request->user_id);
            },'GradeItems.userGrades' => function($query) use ($request){
                $query->where("user_id", $request->user_id);
            },'userGrades' => function($query) use ($request){
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
                        ->with(['enroll' => $callback , 'enroll.levels' ,'enroll.year' , 'enroll.type' , 'enroll.classes'])->first();

        return response()->json(['message' => null, 'body' => $result ], 200);
    }


    public function manaraReportAll(Request $request)
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
            'courses' => 'array',
            'courses.*' => 'exists:courses,id',
        ]);
        $result_collection = collect([]);
        $user_ids = $this->chain->getEnrollsByManyChain($request)->distinct('user_id')->pluck('user_id');
        foreach($user_ids as $user_id){
            $GLOBALS['user_id'] = $user_id;
            $grade_category_callback = function ($qu) use ($user_id , $request) {
                $qu->whereNull('parent')
                ->with(['Children.userGrades' => function($query) use ($user_id , $request){
                    $query->where("user_id", $user_id);
                },'GradeItems.userGrades' => function($query) use ($user_id , $request){
                    $query->where("user_id", $user_id);
                },'userGrades' => function($query) use ($user_id , $request){
                    $query->where("user_id", $user_id);
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
            $result = User::select('id','username','lastname', 'firstname')->whereId($user_id)->whereHas('enroll' , $callback)
                            ->with(['enroll' => $callback , 'enroll.levels' ,'enroll.year' , 'enroll.type' , 'enroll.classes'])->first();
            if($result != null)
                $result_collection->push($result);
        }
        return response()->json(['message' => null, 'body' => $result_collection ], 200);
    }

    public function haramaninReportAll(Request $request)
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
            'courses' => 'array',
            'courses.*' => 'exists:courses,id',
        ]);
    
        $result_collection = collect([]);
        $user_ids = $this->chain->getEnrollsByManyChain($request)->distinct('user_id')->pluck('user_id');

        foreach($user_ids as $user_id){
            $GLOBALS['user_id'] = $user_id;
            $grade_category_callback = function ($qu) use ($request , $user_id) {
                $qu->where('type', 'item');
                $qu->with(['userGrades' => function($query) use ($request , $user_id){
                    $query->where("user_id", $user_id);
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

            $result = User::select('id','username','lastname', 'firstname')->whereId($user_id)->whereHas('enroll' , $callback)
                            ->with(['enroll' => $callback , 'enroll.levels' , 'enroll.type'])->first();

            if($result != null)
                $result_collection->push($result);
        }
        return response()->json(['message' => null, 'body' => $result_collection ], 200);
    }


    public function forsanReportAll(Request $request)
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
            'courses' => 'array',
            'courses.*' => 'exists:courses,id',
        ]);
    
        $result_collection = collect([]);
        $user_ids = $this->chain->getEnrollsByManyChain($request)->distinct('user_id')->pluck('user_id');

        foreach($user_ids as $user_id){
            $GLOBALS['user_id'] = $user_id;
            $grade_category_callback = function ($qu) use ($request, $user_id) {
                $qu->whereNull('parent')
                ->with(['Children.userGrades' => function($query) use ($request , $user_id){
                    $query->where("user_id", $user_id);
                },'GradeItems.userGrades' => function($query) use ($request, $user_id){
                    $query->where("user_id", $user_id);
                },'userGrades' => function($query) use ($request, $user_id){
                    $query->where("user_id", $user_id);
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
    
            $result = User::select('id','username','lastname', 'firstname')->whereId($user_id)->whereHas('enroll' , $callback)
                            ->with(['enroll' => $callback , 'enroll.levels' , 'enroll.type'])->first();
            if($result != null)
                $result_collection->push($result);
        }
        return response()->json(['message' => null, 'body' => $result_collection ], 200);
    }

    public function fglsReportAll(Request $request)
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
            'courses' => 'array',
            'courses.*' => 'exists:courses,id',
        ]);
    
        $result_collection = collect([]);
        $user_ids = $this->chain->getEnrollsByManyChain($request)->distinct('user_id')->pluck('user_id');

        foreach($user_ids as $user_id){
            $GLOBALS['user_id'] = $user_id;
            
            ////////////////////////////////
            $total = 0;
            $student_mark = 0;
            $grade_category_callback = function ($qu) use ($request ) {
                $qu->where('name', 'First Term');
                $qu->with(['userGrades' => function($query) use ($request , $user_id){
                    $query->where("user_id", $user_id);
                }]);     
            };
    
            $callback = function ($qu) use ($request , $grade_category_callback) {
                $qu->where('role_id', 3);
                $qu->whereHas('courses.gradeCategory' , $grade_category_callback)
                    ->with(['courses.gradeCategory' => $grade_category_callback]); 
    
            };
    
            $result = User::whereId($user_id)->whereHas('enroll' , $callback)
                            ->with(['enroll' => $callback])->first();
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
            ///////////////////////////////////////////////////
            if($result != null)
                $result_collection->push($result);
        }
        return response()->json(['message' => null, 'body' => $result_collection ], 200);
    }
}
