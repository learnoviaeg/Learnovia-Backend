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
            'items' => 'required|array',
            'items.*.name' => 'required|string',
            'items.*.grade_category_id' => 'required|exists:grade_categories,id',
            'items.*.min'=>'between:0,99.99',
            'items.*.max'=>'between:0,99.99',
            'items.*.weight_adjust' => 'boolean',
            'items.*.locked' => 'boolean',
            'items.*.hidden' => 'boolean',
        ]);

        foreach($request->items as $key=>$item){
            $item = GradeItems::firstOrCreate([
                'name' => $item['name'],
                'grade_category_id' => isset($item['grade_category_id']) ? $item['grade_category_id'] : null,
                'type' => 'Manual',
                'locked' =>isset($item['locked']) ? $item['locked'] : 0,
                'min' =>isset($item['min']) ? $item['min'] : 0,
                'max' =>isset($item['max']) ? $item['max'] : null,
                'weight_adjust' =>isset($item['weight_adjust']) ? $item['weight_adjust'] : 0,
                'hidden' =>isset($item['hidden']) ? $item['hidden'] : 0,
            ]);
            $enrolled_students = Enroll::where('role_id' , 3)->where('course_segment',GradeCategory::whereId($item['grade_category_id'])->first()->course_segment_id)->pluck('user_id');
            foreach($enrolled_students as $student){
                UserGrader::create([
                    'user_id'   => $student,
                    'item_type' => 'Item',
                    'item_id'   => $item->id,
                    'grade'     => null
                ]);
            }
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
        //
    }
}
