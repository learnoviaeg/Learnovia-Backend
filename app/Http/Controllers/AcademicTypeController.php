<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\AcademicType;
use App\Http\Resources\Academic_Type as Academic_TypeResource;
class AcademicTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $year = AcademicType::paginate(10); 
        return Academic_TypeResource::collection($year);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
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
        $year = new AcademicType;
        $year->name = $request->input('name');
        $year->segment_no = $request->input('segment_no');
        $year->save();
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $year = AcademicType::findOrFail($id);
        return new Academic_TypeResource($year);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
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
        $year = AcademicType::find($id);
        $year->update($request->all());
        return $year;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $year = AcademicType::findOrFail($id);
        if($year->delete()){
        return new Academic_TypeResource($year);
    }
    }
}
