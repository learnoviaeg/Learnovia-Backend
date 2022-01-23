<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Repositories\ChainRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use App\GradeCategory;
use App\GradeItems;
use App\scale;

class GraderReportController extends Controller
{
    public function __construct(ChainRepositoryInterface $chain)
    {
        $this->chain = $chain;
        $this->middleware('auth');
        $this->middleware(['permission:grade/report/grader'],   ['only' => ['index','show']]);
        $this->middleware('permission:grade/report/setup', ['only' => ['grade_setup']]);        
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
        $req = new Request([
                'courses' => [$request->course_id],
        ]);
        $enrolled_students = $this->chain->getEnrollsByChain($req)->where('role_id' , 3)->get('user_id')->pluck('user_id');
        $main_category = GradeCategory::select('id','name','min','max','parent')->where('course_id' ,$request->course_id)->where('type', 'category')->whereNull('parent')
                        ->with(['userGrades' => function($q)use ($enrolled_students)
                        {
                                $q->whereIn('user_id',$enrolled_students);
                                $q->with(['user' => function($query) {
                                    $query->select('id', 'firstname', 'lastname','username');
                              }]);
                        }])->get();

        $main_category[0]['children'] = [];
        $cat = GradeCategory::select('id','name','min','max','parent')->where('parent',$main_category[0]->id)->where('type', 'category')->get();
        $items = GradeCategory::select('id','name','min','max','parent')->where('parent',$main_category[0]->id)->where('type', 'item')->get();
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
    public function show(Request $request ,$id)
    {
        $category = GradeCategory::where('parent',$id)->where('type', 'category');
        $req = new Request([
            'courses' =>[$request->course_id],
        ]);
        $enrolled_students = $this->chain->getEnrollsByChain($req)->where('role_id' , 3)->get('user_id')->pluck('user_id');
        $categories = $category->select('id','name','min','max','parent')->with(['userGrades' => function($q)use ($enrolled_students)
                        {
                            $q->whereIn('user_id',$enrolled_students);
                            $q->with(['user' => function($query) {
                                $query->select('id', 'firstname', 'lastname','username');
                            }]);
                        }])->get();
        $items = GradeCategory::where('parent' ,$id)->where('type', 'item')  ->with(['userGrades' => function($q)use ($enrolled_students)
                {
                    $q->whereIn('user_id',$enrolled_students);
                    $q->with(['user' => function($query) {
                        $query->select('id', 'firstname', 'lastname','username');
                    }]);
                }])->with('scale.details')->get();
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
        $categories = GradeCategory::where('course_id' ,$request->course_id)->whereNull('parent')->with('Children','GradeItems')->first();
        return response()->json(['message' => __('messages.grade_category.list'), 'body' => $categories ], 200);
    }

    public function user_grades(Request $request)
    {
        $request->validate([
            'courses'    => 'nullable|array',
            'courses.*'  => 'nullable|integer|exists:courses,id',
            'classes' => 'array',
            'classes.*' => 'exists:classes,id',
            ]);           
        $enrolls = $this->chain->getEnrollsByManyChain($request)->where('role_id',3)->select('user_id')->distinct('user_id')
                    ->with(array('user' => function($query) {
                        $query->addSelect(array('id', 'firstname', 'lastname'));
                    }))->get();

        return response()->json(['message' => __('messages.users.all_list'), 'body' => $enrolls ], 200);
    }
}
   