<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Repositories\ChainRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use App\GradeCategory;

class GradeCategoriesController extends Controller
{

    public function __construct(ChainRepositoryInterface $chain)
    {
        $this->chain = $chain;
        $this->middleware('auth');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
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
            'year' => 'exists:academic_years,id',
            'type' => 'exists:academic_types,id',
            'level' => 'exists:levels,id',
            'segment' => 'exists:segments,id',
            'courses'    => 'nullable|array',
            'courses.*'  => 'nullable|integer|exists:courses,id',
            'class' => 'nullable|integer|exists:classes,id',
            'category' => 'required|array',
            'category.*.name' => 'required|string',
            'category.*.parent' => 'exists:grade_categories,id',
            'category.*.hidden' => 'boolean',
        ]);
        
        $course_segment_id = $this->chain->getCourseSegmentByChain($request)->first()->course_segment;
        foreach($request->category as $key=>$category){
            GradeCategory::firstOrCreate([
                'course_segment_id'=> $course_segment_id,
                'name' => $category['name'],
                'parent' => isset($category['parent']) ? $category['parent'] : null,
                'hidden' =>isset($category['hidden']) ? $category['hidden'] : 0,
            ]);
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
        //
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
        //
    }
}
