<?php

namespace App\Http\Controllers;

use App\AcademicYearType;
use App\YearLevel;
use Illuminate\Http\Request;
use App\Classes;
use App\ClassLevel;
use App\Http\Resources\Classes as Classs;
use Validator;
class ClassController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $request->validate([
            'year'  => 'required|exists:academic_years,id',
            'type'  => 'required|exists:academic_types,id',
            'level' => 'required|exists:levels,id',
        ]);
        $yeartype = AcademicYearType::checkRelation($request->year , $request->type);
        $yearlevel = YearLevel::checkRelation($yeartype->id , $request->level);
        $class =[];
        foreach ($yearlevel->classLevels as $classLevel){
            $class[] = $classLevel->classes[0];
        }
        return HelperController::api_response_format(200, $class);
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
            'name'  => 'required',
            'year'  => 'required|exists:academic_years,id',
            'type'  => 'required|exists:academic_types,id',
            'level' => 'required|exists:levels,id',
        ]);

        $class = Classes::create([
            'name' => $request->name,
        ]);
        $yeartype = AcademicYearType::checkRelation($request->year , $request->type);
        $yearlevel = YearLevel::checkRelation($yeartype->id , $request->level);
        ClassLevel::create([
            'year_level_id' => $yearlevel->id,
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
        $valid = Validator::make($request->all(), [
            'name' => 'required',
            'id' => 'required|exists:levels,id',
            'year' => 'exists:academic_years,id',
            'type' => 'exists:academic_types,id|required_with:year',
            'level' => 'exists:levels,id|required_with:year',
        ]);

        if ($valid->fails())
            return HelperController::api_response_format(400 , $valid->errors() , 'Something went wrong');

        $class = Classes::find($request->id);

        $class->update($request->all());
        if ($request->filled('year')){
            $oldyearType = AcademicYearType::checkRelation($class->classlevel->yearLevels[0]->yearType[0]->academictype[0]->id , $class->classlevel->yearLevels[0]->yearType[0]->academicyear[0]->id);
            $newyearType = AcademicYearType::checkRelation($request->year , $request->type);
            $oldyearLevel = YearLevel::checkRelation($oldyearType->id , $class->classlevel->yearLevels[0]->levels[0]->id);
            $newyearLevel = YearLevel::checkRelation($newyearType->id , $request->level);
            $oldClassLevel = ClassLevel::checkRelation($oldyearLevel->id , $class->id);
            $oldClassLevel->delete();
            ClassLevel::checkRelation($newyearLevel->id , $class->id);
        }
        $class->classlevel->yearLevels[0]->levels;
        return HelperController::api_response_format(200, $class);
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