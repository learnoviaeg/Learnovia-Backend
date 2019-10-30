<?php

namespace App\Http\Controllers;

use App\AcademicYearType;
use App\YearLevel;
use Illuminate\Http\Request;
use App\Classes;
use App\ClassLevel;
use App\Http\Resources\Classes as Classs;
use Validator;
use App\AcademicYear;
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
            'id' => 'exists:classes,id'
        ]);
        if($request->id == null)
        {
            $yeartype = AcademicYearType::checkRelation($request->year , $request->type);
            $yearlevel = YearLevel::checkRelation($yeartype->id , $request->level);
            $class =[];
            foreach ($yearlevel->classLevels as $classLevel){
                $class[] = $classLevel->classes[0];
            }
            return HelperController::api_response_format(200, $class);
        }
        else
        {
            $class = Classes::find($request->id);
            if ($class)
                return HelperController::api_response_format(200, new Classes($class));
            return HelperController::NOTFOUND();
        }

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
            'year'=>'array|required_with:type|required_with:level',
            'year.*'=>'exists:academic_years,id',
            'type'=>'array|required_with:year',
            'type.*'=> 'exists:academic_types,id',
            'level'=>'array|required_with:year',
            'level.*'=> 'exists:levels,id',
        ]);

        $class = Classes::firstOrCreate([
            'name' => $request->name,
        ]);
        if($request->filled('year')&&$request->filled('type')&&$request->filled('level')){
            foreach ($request->year as $year) {
                # code...
                foreach ($request->type as $type) {
                    # code...
                    $yeartype = AcademicYearType::checkRelation($year , $type);
                    foreach ($request->level as $level) {
                        # code...
                        $yearlevel = YearLevel::checkRelation($yeartype->id , $level);
                        ClassLevel::firstOrCreate([
                            'year_level_id' => $yearlevel->id,
                            'class_id' => $class->id
                        ]);
                    }
                }
            }

        }
        return HelperController::api_response_format(200, new  Classs($class), 'Class Created Successfully');
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request)
    {
        if($request->id == null)
        {
            $classes = Classes::paginate(HelperController::GetPaginate($request));
            return HelperController::api_response_format(200,$classes);
        }
        else
        {
            $request->validate([
                'id' => 'exists:classes,id',
            ]);
            $class = Classes::find($request->id);
            return HelperController::api_response_format(200,$class );

        }
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
        return HelperController::api_response_format(200, Classes::get(), 'Class Deleted Successfully');
    }


    public function Assign_class_to(Request $request)
    {
        $rules =[
            'year' => 'array',
            'year.*' => 'exists:academic_years,id',
            'type' => 'array',
            'type.*' => 'required|exists:academic_types,id',
            'level' => 'array',
            'level.*' => 'required|exists:levels,id',
            'class' => 'required|exists:classes,id'
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails())
            return ['result' => false, 'value' => $validator->errors()];

        $count=0;
        if( count($request->type) == count ($request->level))
        {
            while(isset($request->type[$count]))
                {
                    $year = AcademicYear::Get_current()->id;
                    if (isset($request->year[$count])) {
                        $year = $request->year[$count];
                    }
                    $academic_year_type = AcademicYearType::checkRelation($year, $request->type[$count]);
                    $Year_level=YearLevel::checkRelation($academic_year_type->id, $request->level[$count]);
                    $Class_level=ClassLevel::checkRelation($request->class, $Year_level->id);
                    $count++;
                }
        }
        else {
             return HelperController::api_response_format(201, 'Arrays must have same length');
        }
        return HelperController::api_response_format(201, 'Class Assigned Successfully');
    }
}
