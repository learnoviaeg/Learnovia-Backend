<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Repositories\ChainRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use App\GradeCategory;
use App\GradeItems;
use App\UserGrader;
use App\Enroll;
use App\Course;

class GradeCategoriesController extends Controller
{

    public function __construct(ChainRepositoryInterface $chain)
    {
        $this->chain = $chain;
        $this->middleware('auth');
        $this->middleware(['permission:grade/category/add' ],   ['only' => ['store']]);
        $this->middleware(['permission:grade/category/update'],   ['only' => ['update']]);
        $this->middleware(['permission:grade/category/get'],   ['only' => ['index']]);
        $this->middleware(['permission:grade/category/delete'],   ['only' => ['destroy']]);
    }


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $request->validate([
            'year' => 'exists:academic_years,id',
            'type' => 'exists:academic_types,id',
            'level' => 'exists:levels,id',
            'segment' => 'exists:segments,id',
            'courses'    => 'nullable|array',
            'courses.*'  => 'nullable|integer|exists:courses,id',
            'class' => 'nullable|integer|exists:classes,id',
            'name' => 'string',
            'parent' => 'exists:grade_categories,id',
        ]);

        $grade_categories = GradeCategory::whereNull('instance_type');
            if($request->filled('name'))
                $grade_categories->where('name','LIKE' , "%$request->name%");
            if($request->filled('parent'))
                $grade_categories->where('parent' ,$request->parent);
            if($request->filled('class') && $request->filled('courses')){
                $course_segment_id = $this->chain->getCourseSegmentByChain($request)->first()->course_segment;
                $grade_categories->where('course_segment_id' ,$course_segment_id);
            }
            
        return response()->json(['message' => __('messages.grade_category.list'), 'body' => $grade_categories->with('Child.GradeItems','GradeItems')->get() ], 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            // 'year' => 'exists:academic_years,id',
            // 'type' => 'exists:academic_types,id',
            // 'level' => 'exists:levels,id',
            'segment' => 'exists:segments,id',
            'courses'    => 'nullable|array|required_without:levels',
            'courses.*'  => 'nullable|integer|exists:courses,id',
            'levels'    => 'nullable|array|required_without:courses',
            'levels.*'  => 'nullable|integer|exists:levels,id',
            'name' => 'required|string',
            'parent' => 'exists:grade_categories,id',
            'hidden' => 'boolean',
            'calculation_type' => 'nullable',
            'locked' => 'boolean',
            'min'=>'between:0,99.99',
            'max'=>'between:0,99.99',
            'weight_adjust' => 'boolean',
            'exclude_empty_grades' => 'boolean',
        ]);
        $enrolls = $this->chain->getEnrollsByManyChain($request);
        $courses = $enrolls->get()->pluck('course')->unique(); 

        foreach($courses as $course){
            $cat = GradeCategory::firstOrCreate([
                'course_id'=> $course,
                'name' => $request->name,
                'parent' => isset($request->parent) ? $request->parent : null,
                'hidden' =>isset($request->hidden) ? $request->hidden : 0,
                'calculation_type' =>isset($request->calculation_type) ? $request->calculation_type : null,
                'locked' =>isset($request->locked) ? $request->locked : 0,
                'min' =>isset($request->min) ? $request->min : 0,
                'max' =>isset($request->max) ? $request->max : null,
                'weight_adjust' =>isset($request->weight_adjust) ? $request->weight_adjust : 0,
                'exclude_empty_grades' =>isset($request->exclude_empty_grades) ? $request->exclude_empty_grades : 0,
            ]);
            $enrolled_students = Enroll::where('course',$course)->where('role_id',3)->get()->pluck('user_id')->unique();
            foreach($enrolled_students as $student){
                UserGrader::create([
                    'user_id'   => $student,
                    'item_type' => 'Category',
                    'item_id'   => $cat->id,
                    'grade'     => null
                ]);
            }
        }
        return response()->json(['message' => __('messages.grade_category.add'), 'body' => null ], 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $grade_categories = GradeCategory::where('id', $id)->with('Child.GradeItems','GradeItems')->first();
        return response()->json(['message' => __('messages.grade_category.list'), 'body' => $grade_categories], 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'string',
            'parent' => 'exists:grade_categories,id',
            'hidden' => 'boolean',

        ]);
        $grade_category = GradeCategory::findOrFail($id);
        $grade_category->update([
            'name'   => isset($request->name) ? $request->name : $grade_category->name,
            'parent' => isset($request->parent) ? $request->parent : $grade_category->parent,
            'hidden' => isset($request->hidden) ? $request->hidden : $grade_category->hidden,
            'calculation_type' =>isset($request->calculation_type) ? $request->calculation_type : $category['calculation_type'],
            'locked' =>isset($request->locked) ? $request->locked  : $grade_category['locked'],
            'min' =>isset($request->min) ? $request->min : $grade_category['min'],
            'max' =>isset($request->max) ? $request->max : $grade_category['max'],
            'weight_adjust' =>isset($request->weight_adjust) ? $request->weight_adjust : $grade_category['weight_adjust'],
            'exclude_empty_grades' =>isset($request->exclude_empty_grades) ? $request->exclude_empty_grades : $grade_category['exclude_empty_grades'],
        ]);
        return response()->json(['message' => __('messages.grade_category.update'), 'body' => null ], 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $grade_category = GradeCategory::find($id);
        if(!isset($grade_category))
            return response()->json(['message' => __('messages.error.not_found'), 'body' => [] ], 404);

        foreach($grade_category->Child as $child){
            $child->GradeItems()->delete();
        }
        $grade_category->Child()->delete();
        $grade_category->delete();
        return response()->json(['message' => __('messages.grade_category.delete'), 'body' => null], 200);
    }
}
