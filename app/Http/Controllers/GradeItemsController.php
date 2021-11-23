<?php

namespace App\Http\Controllers;
use App\Repositories\ChainRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use App\GradeCategory;
use App\GradeItems;
use App\UserGrader;
use App\Enroll;

use Illuminate\Http\Request;

class GradeItemsController extends Controller
{
    public function __construct(ChainRepositoryInterface $chain)
    {
        $this->chain = $chain;
        $this->middleware('auth');
        $this->middleware(['permission:grade/item/add' ],   ['only' => ['store']]);
        $this->middleware(['permission:grade/item/update'],   ['only' => ['update']]);
        $this->middleware(['permission:grade/item/get'],   ['only' => ['index']]);
        $this->middleware(['permission:grade/item/delete'],   ['only' => ['destroy']]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $request->validate([
            'name' => 'string',
            'grade_category_id' => 'exists:grade_categories,id',
        ]);

        $grade_items = GradeItems::Query();
            if($request->filled('name'))
                $grade_items->where('name','LIKE' , "%$request->name%");
            if($request->filled('grade_category_id'))
                $grade_items->where('grade_category_id' ,$request->grade_category_id);
        return response()->json(['message' => __('messages.grade_items.list'), 'body' => $grade_items->get() ], 200);
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
            'course'    => 'required_without:grade_category_id|exists:courses,id',
            'item_name' => 'required|string',
            'grade_category_id' => 'required_without:course|exists:grade_categories,id',
            'min'=>'between:0,99.99',
            'max'=>'between:0,99.99',
            'weight_adjust' => 'boolean',
            'locked' => 'boolean',
            'hidden' => 'boolean',
        ]);

        if($request->filled('grade_category_id'))
        $course = GradeCategory::find($request->grade_category_id)->course_id;
    
        if($request->filled('course')){
            $course = $request->course;
            $category = GradeCategory::whereNull('parent')->where('course_id',$request->course)->first();
        }

        $item = GradeItems::firstOrCreate([
            'name' => $request->name,
            'grade_category_id' => isset($request->grade_category_id) ? $request->grade_category_id : $category->id,
            'type' => 'Manual',
            'locked' =>isset($request->locked) ? $request->locked : 0,
            'min' =>isset($request->min) ? $request->min : 0,
            'max' =>isset($request->max) ? $request->max : null,
            'weight_adjust' =>isset($request->weight_adjust) ? $request->weight_adjust : 0,
            'hidden' =>isset($request->hidden) ? $request->hidden : 0,
        ]);    
        $enrolled_students = Enroll::select('user_id')->distinct()->where('course',$course)->where('role_id',3)->get()->pluck('user_id');
        foreach($enrolled_students as $student){
            UserGrader::create([
                'user_id'   => $student,
                'item_type' => 'Item',
                'item_id'   => $item->id,
                'grade'     => null
            ]);
        }
        return response()->json(['message' => __('messages.grade_item.add'), 'body' => null ], 200);
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
            'grade_category_id' => 'exists:grade_category_id,id',
        ]);
        $grade_items = GradeItems::findOrFail($id);
        $grade_items->update([
            'name'   => isset($request->name) ? $request->name : $grade_items->name,
            'grade_category_id' => isset($request->grade_category_id) ? $request->grade_category_id : $grade_items->grade_category_id,
            'hidden' => isset($request->hidden) ? $request->hidden : $grade_items->hidden,
            'locked' =>isset($request->locked) ? $request->locked  : $grade_items['locked'],
            'min' =>isset($request->min) ? $request->min : $grade_items['min'],
            'max' =>isset($request->max) ? $request->max : $grade_items['max'],
            'weight_adjust' =>isset($request->weight_adjust) ? $request->weight_adjust : $grade_items['weight_adjust'],
        ]);
        return response()->json(['message' => __('messages.grade_item.update'), 'body' => null ], 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $grade_item = GradeItems::findOrFail($id);
        $grade_item->delete();
        $user_graders = UserGrader::where('item_type' , 'Item')->where('item_id' , $grade_item->id)->delete();
        return response()->json(['message' => __('messages.grade_item.delete'), 'body' => null ], 200);
    }
}
