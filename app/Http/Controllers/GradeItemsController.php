<?php

namespace App\Http\Controllers;
use App\Repositories\ChainRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use App\GradeCategory;
use App\GradeItems;
use App\UserGrader;
use App\Enroll;
use App\Events\GraderSetupEvent;

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

        $grade_items = GradeCategory::where('type', 'category');
            if($request->filled('name'))
                $grade_items->where('name','LIKE' , "%$request->name%");
            if($request->filled('grade_category_id'))
                $grade_items->where('parent' ,$request->grade_category_id);
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
            'name' => 'required|string',
            'grade_category_id' => 'required_without:course|exists:grade_categories,id',
            'min'=>'between:0,9999.99',
            'max'=>'between:0,9999.99',
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
        $item = GradeCategory::create([
            'name' => $request->name,
            'parent' => isset($request->grade_category_id) ? $request->grade_category_id : $category->id,
            'type' => 'item',
            'locked' =>isset($request->locked) ? $request->locked : 0,
            'min' =>isset($request->min) ? $request->min : 0,
            'max' =>isset($request->max) ? $request->max : null,
            'weight_adjust' =>isset($request->weight_adjust) ? $request->weight_adjust : 0,
            'weights' =>isset($request->weight) ? $request->weight : NULL,
            'hidden' =>isset($request->hidden) ? $request->hidden : 0,
            'item_type' => 'Manual',
            'course_id' => $course,
        ]);    
        $enrolled_students = Enroll::select('user_id')->distinct()->where('course',$course)->where('role_id',3)->get()->pluck('user_id');
        foreach($enrolled_students as $student){
            UserGrader::create([
                'user_id'   => $student,
                'item_type' => 'category',
                'item_id'   => $item->id,
                'grade'     => null
            ]);
        }
        event(new GraderSetupEvent($item->Parents));
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
        $grade_item = GradeCategory::findOrFail($id);
        return response()->json(['message' => __('messages.grade_items.list'), 'body' => $grade_item ], 200);
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
            'grade_category_id' => 'exists:grade_categories,id',
        ]);
        $grade_items = GradeCategory::findOrFail($id);
        
        if($request->filled('grade_category_id'))
            event(new GraderSetupEvent($grade_items->Parents)); 

        $grade_items->update([
            'name'   => isset($request->name) ? $request->name : $grade_items['name'],
            'parent' => isset($request->parent) ? $request->parent : $grade_items['parent'],
            'hidden' => isset($request->hidden) ? $request->hidden : $grade_items['hidden'],
            'locked' =>isset($request->locked) ? $request->locked  : $grade_items['locked'],
            'min' =>isset($request->min) ? $request->min : $grade_items['min'],
            'max' =>isset($request->max) ? $request->max : $grade_items['max'],
            'weight_adjust' =>isset($request->weight_adjust) ? $request->weight_adjust : $grade_items['weight_adjust'],
            'weights' =>isset($request->weight) ? $request->weight : $grade_items['weight'],
        ]);
        event(new GraderSetupEvent($grade_items->Parents));            
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
        $grade_item = GradeCategory::findOrFail($id);
        $parent =  GradeCategory::find($grade_item->Parents->id);
        $grade_item->delete();
        event(new GraderSetupEvent($parent));        
        $user_graders = UserGrader::where('item_type' , 'category')->where('item_id' , $grade_item->id)->delete();
        return response()->json(['message' => __('messages.grade_item.delete'), 'body' => null ], 200);
    }
}
