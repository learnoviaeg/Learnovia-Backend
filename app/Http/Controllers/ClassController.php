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
        return HelperController::api_response_format(200, $class);
        //return Classs::collection($class);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
        ]);
        $class = new Classes;
        $class->name = $request->name;
        $class->save();
        return HelperController::api_response_format(200, new Classs($class), 'Class Created Successfully');
    }

    public function AddClassWithYear(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'year' => 'required|exists:year_levels,id'
        ]);

        $class = Classes::create([
            'name' => $request->name,
        ]);
        ClassLevel::create([
            'year_level_id' => $request->year,
            'class_id' => $class->id
        ]);
        return HelperController::api_response_format(200, new  Classs($class), 'Class Created Successfully');
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $class = Classes::find($id);
        if ($class)
            return HelperController::api_response_format(200, new Classs($class));
        return HelperController::NOTFOUND();
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'id' => 'required|exists:classes,id'
        ]);
        $class = Classes::find($request->id);
        $class->update($request->all());
        return HelperController::api_response_format(200, new Classs($class));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request)
    {
        $request->validate(['id' => 'required|exists:classes,id']);
        $class = Classes::find($request->id);
        $class->delete();
        return HelperController::api_response_format(200, new Classs($class));
    }
}