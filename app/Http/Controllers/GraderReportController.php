<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Repositories\ChainRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use App\GradeCategory;
use App\GradeItems;

class GraderReportController extends Controller
{
    public function __construct(ChainRepositoryInterface $chain)
    {
        $this->chain = $chain;
        $this->middleware('auth');
        $this->middleware(['permission:grade/report/grader'],   ['only' => ['index','show']]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $request->validate([
            'course_id' => 'required|exists:courses,id',
        ]);

        $main_category = GradeCategory::where('course_id' ,$request->course_id)->whereNull('parent')->with('userGrades.user')->get();
        $main_category[0]['children'] = [];
        $cat = GradeCategory::where('parent',$main_category[0]->id)->get();
        $items = GradeItems::where('grade_category_id',$main_category[0]->id)->get();
            $main_category[0]['has_children'] = false;
            if(count($cat) > 0 || count($items) > 0)
                $main_category[0]['has_children'] = true;
        return response()->json(['message' => __('messages.grade_category.list'), 'body' => $main_category ], 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $categories = GradeCategory::where('parent',$id)->with('userGrades.user')->get();
        $items = GradeItems::where('grade_category_id' ,$id)->with('userGrades.user')->get();
        foreach($categories as $key=>$category){
            $category['children'] = [];
            $category['Category_or_Item'] = 'Category';
            $cat = GradeCategory::where('parent',$category->id)->get();
            $category['has_children'] = false;
            if(count($cat) > 0 || count($items) > 0)
                $category['has_children'] = true;
        }
        foreach($items as $key=>$item){
            $item['Category_or_Item'] = 'Item';
            $item['has_children'] = false;
        }
        $all['categories'] = $categories;
        $all['items'] = $items;
        return response()->json(['message' => __('messages.grade_category.list'), 'body' => $all ], 200);

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
        //
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

    public function grade_setup(Request $request)
    {
        $request->validate([
            'course_id' => 'required|exists:courses,id',
        ]);
        $categories = GradeCategory::where('course_id' ,$request->course_id)->whereNull('parent')->with('Children')->get();
        return response()->json(['message' => __('messages.grade_category.list'), 'body' => $categories ], 200);
    }

    public function weight_adjust(Request $request)
    {
        $request->validate([
            'instance' => 'required|array',
            'instance.*.id' => 'required',
            'instance.*.type' => 'required|in:GradeCategory,GradeItems',
            // 'instance.*.weight' => 'required',
            // 'instance.*.mark' => 'required',
            'instance.*.weight_adjust' => 'boolean',

        ]);

        foreach($request->instance as $instance)
        {
            $total_grade = 0;
            $total_weight = 100;
            if($instance['type'] == 'GradeCategory'){
                $category = GradeCategory::find($instance['id']);
                $category->weight_adjust = isset($instance['weight_adjust']) ? $instance['weight_adjust'] : $category->weight_adjust;
                $category->weight = isset($instance['weight']) ? $instance['weight'] : $category->weight;
                $category->save();
            }
            if($instance['type'] == 'GradeItems'){
                $category = GradeItems::find($instance['id']);
                $category->weight_adjust = isset($instance['weight_adjust']) ? $instance['weight_adjust'] : $category->weight_adjust;
                $category->weight = isset($instance['weight']) ? $instance['weight'] : $category->weight;
                $category->save();
            }
            if($category->parent == null)
                return response()->json(['message' => __('messages.grade_category.CannotUpdate'), 'body' => null ], 400);

            $parent = GradeCategory::where('id',$category->parent)->with('Child','GradeItems')->first();
            foreach($parent->child as $cats)
            {
                if($cats->weight != 0){
                    $total_grade += $cats->max;
                }
                if($cats->weight_adjust	 == 1){
                    $total_weight -= $cats->weight;
                }
            }
            foreach($parent->GradeItems as $item)
            {
                if($item->weight != 0){
                    $total_grade += $item->max;
                }
                if($item->weight_adjust	 == 1){
                    $total_weight -= $item->weight;
                }
            }

            foreach($parent->child as $cats)
            {
                if($cats->weight_adjust	 != 1){
                    $cats->weight = ($cats->max / $total_grade) *$total_weight;
                    $cats->save();
                }
            }

            foreach($parent->GradeItems as $item)
            {
                if($item->weight_adjust	 != 1){
                    $item->weight = ($item->max / $total_grade) *$total_weight;
                    $item->save();
                }
            }

    
        }
        return 'done';



    }
}
