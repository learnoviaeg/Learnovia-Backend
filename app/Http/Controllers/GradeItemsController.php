<?php

namespace App\Http\Controllers;
use App\Repositories\ChainRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use App\GradeCategory;
use App\GradeItems;

use Illuminate\Http\Request;

class GradeItemsController extends Controller
{
    public function __construct(ChainRepositoryInterface $chain)
    {
        $this->chain = $chain;
        $this->middleware('auth');
        // $this->middleware(['permission:grade/item/add' ],   ['only' => ['store']]);
        // $this->middleware(['permission:grade/item/update'],   ['only' => ['update']]);
        // $this->middleware(['permission:grade/item/get'],   ['only' => ['index']]);
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
        ]);
       
        foreach($request->items as $key=>$item){
            GradeItems::firstOrCreate([
                'name' => $item['name'],
                'grade_category_id' => isset($item['grade_category_id']) ? $item['grade_category_id'] : null,
                'type' => 'Manual',
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
