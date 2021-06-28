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
        $this->middleware(['permission:grader/report/get'],   ['only' => ['index','show']]);
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
            'class_id' => 'required|exists:classes,id',
        ]);
        $req = new Request([
            'class'   => $request->class_id,
            'courses' => array($request->class_id)
        ]);

        $course_segment_id = $this->chain->getCourseSegmentByChain($request)->first()->course_segment;
        $main_category = GradeCategory::where('course_segment_id' ,$course_segment_id)->whereNull('parent')->with('userGrades.user')->get();
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
}
