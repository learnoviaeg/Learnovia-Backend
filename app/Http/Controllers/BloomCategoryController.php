<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\BloomCategory;
use Modules\QuestionBank\Entities\Questions;

class BloomCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $bloomCategories=BloomCategory::where('current',1);
        if(isset($request->default))
            $bloomCategories->where('default',$request->default);

        return response()->json(['message' => __('messages.bloom_category.get'), 'body' => $bloomCategories->get() ], 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // dd($request->complex);
        foreach($request->complex as $complexity){
            $blooms=BloomCategory::updateOrCreate(['name' => $complexity],['current' => 1]);
            $ids[] = $blooms->id; 
        }

        BloomCategory::whereNotIn('id',$ids)->update(['current'=>0]);

        $job = (new \App\Jobs\MapComplexityJob($request->map,$ids));
        dispatch($job);

        return response()->json(['message' => __('messages.bloom_category.add'), 'body' => null ], 200);
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
