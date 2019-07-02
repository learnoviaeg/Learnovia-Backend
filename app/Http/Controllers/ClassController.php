<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Classes;
use App\ClassLevel;
use App\Http\Resources\Classes as Classs;

class ClassController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $class = Classes::paginate(10); 
        return Classs::collection($class);
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
        $class = new Classes;
        $class->name = $request->input('name');
        $class->save();
    }

    public function AddClassWithYear(Request $request)
    {
        if(Classes::Validate($request->all()) == true){
            $class = Classes::create([
                'name'=> $request->name,
            ]);
            ClassLevel::create([
                'year_level_id' => $request->year,
                'class_id' => $class->id
            ]);
            return 'Level Created in Year ' . $request->year;
        }
        return Classes::Validate($request->all());
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $class = Classes::findOrFail($id);
        return new Classs($class);
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
        $class = Classes::find($id);
        $class->update($request->all());
        return $class;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $class = Classes::findOrFail($id);
        if($class->delete()){
        return new Classs($class);
    }
    }
}
