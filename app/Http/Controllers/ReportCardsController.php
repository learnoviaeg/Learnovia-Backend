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
        $this->middleware(['permission:report_card/mfis/mfisg|report_card/mfis/mfisb|report_card/mfis/mfisb-final|report_card/mfis/mfisg-final'],   ['only' => ['manaraReport']]);
        $this->middleware(['permission:report_card/mfis/manara-boys/printAll|report_card/mfis/manara-girls/printAll'],   ['only' => ['manaraReportAll']]);
        $this->middleware(['permission:report_card/haramain/all|report_card/haramain/all-final'],   ['only' => ['haramaninReportAll']]);
        $this->middleware(['permission:report_card/forsan/all'],   ['only' => ['forsanReportAll']]);
        $this->middleware(['permission:report_card/fgls/all'],   ['only' => ['fglsReportAll', 'fglsPrep3ReportAll']]);
        $this->middleware(['permission:report_card/mfis/mfisg-monthly|report_card/mfis/mfisb-monthly'],   ['only' => ['manaraMonthlyReport']]);
        $this->middleware(['permission:report_card/mfis/manara-boys/monthly/printAll|report_card/mfis/manara-girls/monthly/printAll|
                            report_card/mfis/manara-boys/monthly/printAll-final|report_card/mfis/manara-girls/monthly/printAll-final'],   ['only' => ['manaraMonthylReportAll']]);
        $this->middleware(['permission:report_card/fgls/final'],   ['only' => ['fglFinalReport']]);
        $this->middleware(['permission:report_card/fgls/all-final'],   ['only' => ['fglsFinalReportAll']]);       
        $this->middleware(['permission:report_card/forsan/monthly'],   ['only' => ['forsanMonthlyReport']]);
        $this->middleware(['permission:report_card/forsan/monthly/printAll'],   ['only' => ['forsanMonthylReportAll']]);
    }

    public function haramainReport(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'term'    => 'required|in:first,final',
        ]);

        if($request->term == 'first'){
            $allowed_levels=Permission::where('name','report_card/haramain')->pluck('allowed_levels')->first();
            $course_callback = function ($qu) use ($request ) {
                $qu->Where(function ($query) {
                    $query->where('name', 'LIKE' , "%First term%")
                        ->orWhere('name','LIKE' , "%ترم الاول%");
                });     
            };
        }

        if($request->term == 'final'){
            $allowed_levels=Permission::where('name','report_card/haramain/all-final')->pluck('allowed_levels')->first();
            $course_callback = function ($qu) use ($request ) {
                $qu->Where(function ($query) {
                    $query->where('name', 'LIKE' , "%Second term%")
                        ->orWhere('name','LIKE' , "%ترم الثان%");
                });     
            };
        }
            
        $allowed_levels=json_decode($allowed_levels);
        $student_levels = Enroll::where('user_id',$request->user_id)->pluck('level')->toArray();
        $check=(array_intersect($allowed_levels, $student_levels));

        if(count($check) == 0)
            return response()->json(['message' => 'You are not allowed to see report card', 'body' => null ], 200);
        
        $GLOBALS['user_id'] = $request->user_id;
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

        $callback = function ($qu) use ($request , $course_callback , $grade_category_callback) {
            $qu->where('role_id', 3);
            $qu->whereHas('courses' , $course_callback)
                ->with(['courses' => $course_callback]); 
            $qu->whereHas('courses.gradeCategory' , $grade_category_callback)
                ->with(['courses.gradeCategory' => $grade_category_callback]); 
        };

        $result = User::whereId($request->user_id)->whereHas('enroll' , $callback)
                ->with(['enroll' => $callback , 'enroll.levels:id,name' ,'enroll.year:id,name' , 'enroll.type:id,name' , 'enroll.classes:id,name'])->first();

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
            'term'    => 'required|in:first,final',
        ]);
        $GLOBALS['user_id'] = $request->user_id;
        $user = User::find($request->user_id);

        if($request->term == 'first'){
            if($user->can('report_card/mfis/mfisg'))
                $allowed_levels=Permission::where('name','report_card/mfis/mfisg')->pluck('allowed_levels')->first();
    
            if($user->can('report_card/mfis/mfisb'))
                $allowed_levels=Permission::where('name','report_card/mfis/mfisb')->pluck('allowed_levels')->first();

            $course_callback = function ($qu) use ($request ) {
                $qu->Where(function ($query) {
                    $query->where('name', 'LIKE' , "%Grades%");
                });     
            };
        }
            
        if($request->term == 'final'){
            if($user->can('report_card/mfis/mfisg-final'))
                $allowed_levels=Permission::where('name','report_card/mfis/mfisg')->pluck('allowed_levels')->first();

            if($user->can('report_card/mfis/mfisb-final'))
                $allowed_levels=Permission::where('name','report_card/mfis/mfisb')->pluck('allowed_levels')->first();

            $course_callback = function ($qu) use ($request ) {
                $qu->Where(function ($query) {
                    $query->where('name', 'LIKE' , "%inal-%");
                });     
            };
        }
           
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

        // $course_callback = function ($qu) use ($request ) {
        //     $qu->Where(function ($query) {
        //         $query->where('name', 'LIKE' , "%Grades%")
        //               ->orWhere('name','LIKE' , "%درجات%"); 
        //     });     
        // };

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
            'term'    => 'required|in:first,final',
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


            if($request->term == 'first')
            $course_callback = function ($qu) use ($request ) {
                $qu->Where(function ($query) {
                    $query->where('name', 'LIKE' , "%Grades%");
                });     
            };

            if($request->term == 'final')
                $course_callback = function ($qu) use ($request ) {
                    $qu->Where(function ($query) {
                        $query->where('name', 'LIKE' , "%inal-%");
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
            'term'    => 'required|in:first,final',
        ]);
    
        $result_collection = collect([]);
        $user_ids = $this->chain->getEnrollsByManyChain($request)->distinct('user_id')->pluck('user_id');

        foreach($user_ids as $user_id){
            $GLOBALS['user_id'] = $user_id;

            $grade_category_callback = function ($qu) use ($request , $user_id ) {
                $qu->whereNull('parent')
                ->with(['Children.userGrades' => function($query) use ($request , $user_id){
                    $query->where("user_id", $user_id);
                },'GradeItems.userGrades' => function($query) use ($request , $user_id){
                    $query->where("user_id", $user_id);
                },'userGrades' => function($query) use ($request ,$user_id){
                    $query->where("user_id", $user_id);
                }]); 
            };


            if($request->term == 'first')
                $course_callback = function ($qu) use ($request ) {
                    $qu->Where(function ($query) {
                        $query->where('name', 'LIKE' , "%First term%")
                            ->orWhere('name','LIKE' , "%ترم الاول%");
                    });     
                };

            if($request->term == 'final')
                $course_callback = function ($qu) use ($request ) {
                    $qu->Where(function ($query) {
                        $query->where('name', 'LIKE' , "%Second term%")
                            ->orWhere('name','LIKE' , "%ترم الثان%");
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
        $user_ids = $this->chain->getEnrollsByManyChain($request)->where('role_id',3)->distinct('user_id')->pluck('user_id');

        $total_check=(array_intersect([6, 7 ,8 , 9, 10 , 11 , 12], $request->levels));
        foreach($user_ids as $user_id){
            $GLOBALS['user_id'] = $user_id;
            
            ////////////////////////////////
            $total = 0;
            $student_mark = 0;
            $grade_category_callback = function ($qu) use ($request, $user_id ) {
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
            ///////////////////////////////////////////////////
            if($result != null)
                $result_collection->push($result);
        }
        return response()->json(['message' => null, 'body' => $result_collection ], 200);
    }


    public function fglPrep3Report(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $allowed_levels=Permission::where('name','report_card/fgls')->pluck('allowed_levels')->first();
        $allowed_levels=json_decode($allowed_levels);

        $student_levels = Enroll::where('user_id',$request->user_id)->pluck('level')->toArray();
        $check=(array_intersect($allowed_levels, $student_levels));

        if(count($check) == 0)
            return response()->json(['message' => 'You are not allowed to see report card', 'body' => null ], 200);
        
        $GLOBALS['user_id'] = $request->user_id;
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
                        ->with(['enroll' => $callback , 'enroll.levels' , 'enroll.type' , 'enroll.classes'])->first();

        return response()->json(['message' => null, 'body' => $result ], 200);
    }

    public function fglsPrep3ReportAll(Request $request)
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
                            ->with(['enroll' => $callback , 'enroll.levels' , 'enroll.type', 'enroll.classes'])->first();
            if($result != null)
                $result_collection->push($result);
        }
        return response()->json(['message' => null, 'body' => $result_collection ], 200);
    }

    public function manaraMonthlyReport(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'month'   => 'required|in:Feb,March,April',
        ]);

        $GLOBALS['user_id'] = $request->user_id;
        $user = User::find($request->user_id);

        if($user->can('report_card/mfis/mfisg-monthly'))
            $allowed_levels=Permission::where('name','report_card/mfis/mfisg-monthly')->pluck('allowed_levels')->first();
        
        // if($user->can('report_card/mfis/mfisb'))
        //     $allowed_levels=Permission::where('name','report_card/mfis/mfisb')->pluck('allowed_levels')->first();

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
            $qu->where('name','LIKE', "%$request->month%");
        };

        $callback = function ($qu) use ($request , $course_callback , $grade_category_callback) {
            $qu->where('role_id', 3);
            $qu->whereHas('courses' , $course_callback)
                ->with(['courses' => $course_callback]); 
            $qu->whereHas('courses.gradeCategory' , $grade_category_callback)
                ->with(['courses.gradeCategory' => $grade_category_callback]); 
        };

        $result = User::whereId($request->user_id)->whereHas('enroll' , $callback)
                        ->with(['enroll' => $callback , 'enroll.levels:id,name' ,'enroll.year:id,name' , 'enroll.type:id,name' , 'enroll.classes:id,name'])->first();

        return response()->json(['message' => null, 'body' => $result ], 200);
    }


    public function manaraMonthylReportAll(Request $request)
    {
        $request->validate([
            'month'   => 'required|in:Feb,March,April',
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
                $qu->where('name','LIKE', "%$request->month%");
            };

            $callback = function ($qu) use ($request , $course_callback , $grade_category_callback) {
                $qu->where('role_id', 3);
                $qu->whereHas('courses' , $course_callback)
                    ->with(['courses' => $course_callback]); 
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



    public function fglFinalReport(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id', 
        ]);

        $allowed_levels=Permission::where('name','report_card/fgls/final')->pluck('allowed_levels')->first();
        $allowed_levels=json_decode($allowed_levels);
        $student_levels = Enroll::where('user_id',$request->user_id)->pluck('level')->toArray();
        $check=(array_intersect($allowed_levels, $student_levels));

        $total_check=(array_intersect([7, 8 , 9, 10 , 11], $student_levels));

        if(count($check) == 0)
            return response()->json(['message' => 'You are not allowed to see report card', 'body' => null ], 200);

        $First_grade_category_callback = function ($qu) use ($request ) {
            $qu->where('name', 'First Term');
            $qu->with(['userGrades' => function($query) use ($request){
                $query->where("user_id", $request->user_id);
            }]);     
        };

        $Second_grade_category_callback = function ($qu) use ($request ) {
            $qu->where('name', 'Second Term')->orWhere('name','LIKE', "%actor%");
            $qu->with(['userGrades' => function($query) use ($request){
                $query->where("user_id", $request->user_id);
            }]);     
        };


        $course_callback = function ($qu) use ($request ) {
            $qu->orderBy('index', 'Asc');
        };

        $first_term = function ($qu) use ($request , $First_grade_category_callback , $course_callback) {
            $qu->whereHas('courses' , $course_callback)
            ->with(['courses' => $course_callback]); 
            $qu->where('role_id', 3);
            $qu->whereHas('courses.gradeCategory' , $First_grade_category_callback)
                ->with(['courses.gradeCategory' => $First_grade_category_callback]); 

        };


        $second_term = function ($qu) use ($request , $Second_grade_category_callback , $course_callback) {
            $qu->where('role_id', 3);
            $qu->whereHas('courses' , $course_callback)
                ->with(['courses' => $course_callback]); 
            $qu->whereHas('courses.gradeCategory' , $Second_grade_category_callback)
                ->with(['courses.gradeCategory' => $Second_grade_category_callback]); 

        };

        $first_term = User::select('id','firstname' , 'lastname')->whereId($request->user_id)->whereHas('enroll' , $first_term)
                        ->with(['enroll' => $first_term])->first();


        
        $second_term = User::select('id','firstname' , 'lastname')->whereId($request->user_id)->whereHas('enroll' , $second_term)
        ->with(['enroll' => $second_term , 'enroll.levels:id,name' ,'enroll.year:id,name' , 'enroll.type:id,name' , 'enroll.classes:id,name'])->first();


        $first_term->enrolls =  collect($first_term->enroll)->sortBy('courses.index')->values();
        $second_term->enrolls =  collect($second_term->enroll)->sortBy('courses.index')->values();

        unset($first_term->enroll);
        unset($second_term->enroll);

        $total = 0;
        $student_mark = 0;
        $result = collect([]);

        $olFound = true;
        foreach($first_term->enrolls as $key => $enroll){   
            if(!$total_check)
                $second_term->enrolls[$key]->courses->gradeCategory[0]->userGrades[0]->grade =
                ($enroll->courses->gradeCategory[0]->userGrades[0]->grade + $second_term->enrolls[$key]->courses->gradeCategory[0]->userGrades[0]->grade)/2;

             if(isset($second_term->enrolls[$key]->courses->gradeCategory[1])){
                $factor = $second_term->enrolls[$key]->courses->gradeCategory[1]->max;

                $second_term->enrolls[$key]->courses->gradeCategory[0]->userGrades[0]->grade =
                    ($enroll->courses->gradeCategory[0]->userGrades[0]->grade + $second_term->enrolls[$key]->courses->gradeCategory[0]->userGrades[0]->grade) * $factor;

                $second_term->enrolls[$key]->courses->gradeCategory[0]->max=
                    ($enroll->courses->gradeCategory[0]->max + $second_term->enrolls[$key]->courses->gradeCategory[0]->max) * $factor;

                    if($olFound == true){
                        if($enroll->courses->gradeCategory != null)
                            $total += $second_term->enrolls[$key]->courses->gradeCategory[0]->max;
            
                        if($enroll->courses->gradeCategory[0]->userGrades != null)
                            $student_mark += $second_term->enrolls[$key]->courses->gradeCategory[0]->userGrades[0]->grade;
                    }
                    unset($second_term->enrolls[$key]->courses->gradeCategory[1]);
                    if(str_contains($enroll->courses->name, 'O.L'))
                        $olFound = false;
            }   

            $percentage =($second_term->enrolls[$key]->courses->gradeCategory[0]->userGrades[0]->grade /$second_term->enrolls[$key]->courses->gradeCategory[0]->max) * 100;
           $evaluation = LetterDetails::select('evaluation')->where('lower_boundary', '<=', $percentage)
                       ->where('higher_boundary', '>', $percentage)->first();
   
           if($percentage == 100)
               $evaluation = LetterDetails::select('evaluation')->where('lower_boundary', '<=', $percentage)
               ->where('higher_boundary', '>=', $percentage)->first();

            $second_term->enrolls[$key]->courses->gradeCategory[0]->userGrades[0]->letter = $evaluation->evaluation;
            
        }
        $second_term->add_total = false;
        if(count($total_check) > 0){
            $second_term->student_total_mark = round($student_mark,2);
            $second_term->total = $total;

            if($total == 0)
            $second_term->total_mark_evaluation ='Failed';
            else
            $second_term->total_mark_evaluation = LetterDetails::select('evaluation')->where('lower_boundary', '<=',  ($student_mark/$total) *100 )
                                            ->where('higher_boundary', '>',  ($student_mark/$total) *100)->first();
            
            // ($student_mark/$total) *100;
            $second_term->add_total = true;
        }
       
       return response()->json(['message' => null, 'body' => $second_term ], 200);

    }

    public function fglsFinalReportAll(Request $request)
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
        $user_ids = $this->chain->getEnrollsByManyChain($request)->where('role_id',3)->distinct('user_id')->pluck('user_id');

        $total_check=(array_intersect([7 ,8 , 9, 10 , 11 , 12], $request->levels));

        foreach($user_ids as $user_id){
            $GLOBALS['user_id'] = $user_id;
            ////////////////////newnew

            $First_grade_category_callback = function ($qu) use ($request , $user_id) {
                $qu->where('name', 'First Term');
                $qu->with(['userGrades' => function($query) use ($request, $user_id){
                    $query->where("user_id", $user_id);
                }]);     
            };
    
            $Second_grade_category_callback = function ($qu) use ($request, $user_id ) {
                $qu->where('name', 'Second Term')->orWhere('name','LIKE', "%actor%");
                $qu->with(['userGrades' => function($query) use ($request , $user_id){
                    $query->where("user_id", $user_id);
                }]);     
            };
    
    
            $course_callback = function ($qu) use ($request , $user_id) {
                $qu->orderBy('index', 'Asc');
            };
    
            $first_term = function ($qu) use ($request , $First_grade_category_callback , $course_callback) {
                $qu->whereHas('courses' , $course_callback)
                ->with(['courses' => $course_callback]); 
                $qu->where('role_id', 3);
                $qu->whereHas('courses.gradeCategory' , $First_grade_category_callback)
                    ->with(['courses.gradeCategory' => $First_grade_category_callback]); 
    
            };
    
    
            $second_term = function ($qu) use ($request , $Second_grade_category_callback , $course_callback) {
                // $qu->orderBy('course', 'Asc');
                $qu->where('role_id', 3);
                $qu->whereHas('courses' , $course_callback)
                    ->with(['courses' => $course_callback]); 
                $qu->whereHas('courses.gradeCategory' , $Second_grade_category_callback)
                    ->with(['courses.gradeCategory' => $Second_grade_category_callback]); 
    
            };
    
            $first_term = User::select('id','firstname' , 'lastname')->whereId($user_id)->whereHas('enroll' , $first_term)
                            ->with(['enroll' => $first_term])->first();
    
    
            
            $second_term = User::select('id','firstname' , 'lastname')->whereId($user_id)->whereHas('enroll' , $second_term)
            ->with(['enroll' => $second_term , 'enroll.levels:id,name' ,'enroll.year:id,name' , 'enroll.type:id,name' , 'enroll.classes:id,name'])->first();

            $first_term->enrolls =  collect($first_term->enroll)->sortBy('courses.index')->values();
            $second_term->enrolls =  collect($second_term->enroll)->sortBy('courses.index')->values();
    
            unset($first_term->enroll);
            unset($second_term->enroll);
    
            $total = 0;
            $student_mark = 0;
            $result = collect([]);

            $olFound = true;

            foreach($first_term->enrolls as $key => $enroll){  
                if(!$total_check){
                    if(isset($second_term->enrolls[$key])){
                    $second_term->enrolls[$key]->courses->gradeCategory[0]->userGrades[0]->grade =
                        ($enroll->courses->gradeCategory[0]->userGrades[0]->grade + $second_term->enrolls[$key]->courses->gradeCategory[0]->userGrades[0]->grade)/2;

                    $percentage =($second_term->enrolls[$key]->courses->gradeCategory[0]->userGrades[0]->grade /$second_term->enrolls[$key]->courses->gradeCategory[0]->max) * 100;
                    $evaluation = LetterDetails::select('evaluation')->where('lower_boundary', '<=', $percentage)
                                ->where('higher_boundary', '>', $percentage)->first();
            
                    if($percentage == 100)
                        $evaluation = LetterDetails::select('evaluation')->where('lower_boundary', '<=', $percentage)
                        ->where('higher_boundary', '>=', $percentage)->first();
        
                    $second_term->enrolls[$key]->courses->gradeCategory[0]->userGrades[0]->letter = $evaluation->evaluation;
                    }
                }
                    
                    if(isset($second_term->enrolls[$key]->courses->gradeCategory[1])){
                        $factor = $second_term->enrolls[$key]->courses->gradeCategory[1]->max;
        
                        $second_term->enrolls[$key]->courses->gradeCategory[0]->userGrades[0]->grade =
                            ($enroll->courses->gradeCategory[0]->userGrades[0]->grade + $second_term->enrolls[$key]->courses->gradeCategory[0]->userGrades[0]->grade) * $factor;
        
                        $second_term->enrolls[$key]->courses->gradeCategory[0]->max=
                            ($enroll->courses->gradeCategory[0]->max + $second_term->enrolls[$key]->courses->gradeCategory[0]->max) * $factor;
        
                            $percentage =($second_term->enrolls[$key]->courses->gradeCategory[0]->userGrades[0]->grade /$second_term->enrolls[$key]->courses->gradeCategory[0]->max) * 100;
                        $evaluation = LetterDetails::select('evaluation')->where('lower_boundary', '<=', $percentage)
                                    ->where('higher_boundary', '>', $percentage)->first();
                
                        if($percentage == 100)
                            $evaluation = LetterDetails::select('evaluation')->where('lower_boundary', '<=', $percentage)
                            ->where('higher_boundary', '>=', $percentage)->first();
            
                        $second_term->enrolls[$key]->courses->gradeCategory[0]->userGrades[0]->letter = $evaluation->evaluation;

                            if($olFound == true){
                                if($enroll->courses->gradeCategory != null)
                                    $total += $second_term->enrolls[$key]->courses->gradeCategory[0]->max;
                    
                                if($enroll->courses->gradeCategory[0]->userGrades != null)
                                    $student_mark += $second_term->enrolls[$key]->courses->gradeCategory[0]->userGrades[0]->grade;
                            }
                            unset($second_term->enrolls[$key]->courses->gradeCategory[1]);
                            if(str_contains($enroll->courses->name, 'O.L'))
                                $olFound = false;
                    }
                 
             }
 
            $second_term->add_total = false;
            if(count($total_check) > 0){
                $second_term->student_total_mark = round($student_mark, 2);
                $second_term->total = round($total,2);
                if($total == 0)
                    $second_term->total_mark_evaluation ='Failed';
                    else
                $second_term->total_mark_evaluation = LetterDetails::select('evaluation')->where('lower_boundary', '<=',  ($student_mark/$total) *100 )
                ->where('higher_boundary', '>',  ($student_mark/$total) *100)->first();
                $second_term->add_total = true;
            }

            if($second_term != null)
                $result_collection->push($second_term);
        }
        return response()->json(['message' => null, 'body' => $result_collection ], 200);
    }


    public function forsanMonthlyReport(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'month'   => 'required|in:Feb,March,April',
        ]);

        $GLOBALS['user_id'] = $request->user_id;
        $user = User::find($request->user_id);

        if($request->month == 'Feb')
            $arabic_search = 'فبراير';
        if($request->month == 'March')
            $arabic_search = 'مارس';
        if($request->month == 'April')
            $arabic_search = 'بريل';

        if($user->can('report_card/forsan/monthly'))
            $allowed_levels=Permission::where('name','report_card/forsan/monthly')->pluck('allowed_levels')->first();
        

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

        $course_callback = function ($qu) use ($request, $arabic_search) { 
            $qu->where('name','LIKE', "%$request->month%")
                ->orWhere('name','LIKE', "%$arabic_search%");
        };

        $callback = function ($qu) use ($request , $course_callback , $grade_category_callback) {
            $qu->where('role_id', 3);
            $qu->whereHas('courses' , $course_callback)
                ->with(['courses' => $course_callback]); 
            $qu->whereHas('courses.gradeCategory' , $grade_category_callback)
                ->with(['courses.gradeCategory' => $grade_category_callback]); 
        };

        $result = User::whereId($request->user_id)->whereHas('enroll' , $callback)
                        ->with(['enroll' => $callback , 'enroll.levels:id,name' ,'enroll.year:id,name' , 'enroll.type:id,name' , 'enroll.classes:id,name'])->first();

        return response()->json(['message' => null, 'body' => $result ], 200);
    }

    public function forsanMonthylReportAll(Request $request)
    {
        $request->validate([
            'month'   => 'required|in:Feb,March,April',
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

        if($request->month == 'Feb')
            $arabic_search = 'فبراير';
        if($request->month == 'March')
            $arabic_search = 'مارس';
        if($request->month == 'April')
            $arabic_search = 'بريل';

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

            $course_callback = function ($qu) use ($request, $arabic_search ) {
                $qu->where('name','LIKE', "%$request->month%")
                    ->orWhere('name','LIKE', "%$arabic_search%");
            };

            $callback = function ($qu) use ($request , $course_callback , $grade_category_callback) {
                $qu->where('role_id', 3);
                $qu->whereHas('courses' , $course_callback)
                    ->with(['courses' => $course_callback]); 
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

}
