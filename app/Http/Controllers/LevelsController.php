<?php

namespace App\Http\Controllers;

use App\AcademicYearType;
use App\YearLevel;
use Illuminate\Http\Request;
use App\AcademicYear;
use App\Level;
use Validator;

class LevelsController extends Controller
{
    public function AddLevelWithYear(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'year'=>'array|required_with:type',
            'year.*'=>'exists:academic_years,id',
            'type'=>'array|required_with:year',
            'type.*'=> 'exists:academic_types,id',
        ]);
        $level = Level::firstOrCreate([
            'name' => $request->name,
        ]);
        if($request->filled('year')&&$request->filled('type')){
            foreach ($request->year as $year) {
                # code...
                foreach ($request->type as $type) {
                    # code...
                    $yeartype = AcademicYearType::checkRelation($year, $type);
                    YearLevel::firstOrCreate([
                        'academic_year_type_id' => $yeartype->id,
                        'level_id' => $level->id,
                    ]);
                }
            }

        }
        return HelperController::api_response_format(201, $level, 'Level Created Successfully');
    }

    public function Delete(Request $request)
    {
        $request->validate([
            'level' => 'required|exists:levels,id',
        ]);

        $level = Level::find($request->level);
        if ($level)
            $level->delete();
        return HelperController::api_response_format(203, $level, 'Level Deleted Successfully');
    }


    public function UpdateLevel(Request $request)
    {
        $valid = Validator::make($request->all(), [
            'name' => 'required',
            'id' => 'required|exists:levels,id',
            'year' => 'exists:academic_years,id',
            'oldyear' => 'exists:academic_years,id|required_with:year|exists:academic_year_types,academic_year_id',
            'type' => 'exists:academic_types,id|required_with:year',
            'oldtype' => 'exists:academic_years,id|required_with:year|exists:academic_year_types,academic_type_id'
        ]);

        if ($valid->fails())
            return HelperController::api_response_format(400 , $valid->errors() , 'Something went wrong');

        $level = Level::find($request->id);
        $level->name = $request->name;
        $level->save();
        if ($request->filled('year')){
            $oldyearType = AcademicYearType::checkRelation($request->oldyear , $request->oldtype);
            $yearLevel = YearLevel::checkRelation($oldyearType->id , $request->id);
            $yearLevel->delete();
            $newyearType = AcademicYearType::checkRelation($request->year , $request->type);
            YearLevel::checkRelation($newyearType->id , $level->id);
        }
        $level->years[0]->academictype[0]->AC_year;
        return HelperController::api_response_format(200 , $level,'Level Updated Successfully');
    }

    public function GetAllLevelsInYear(Request $request)
    {
        $request->validate([
            'year' => 'required|exists:academic_years,id',
            'type' => 'required|exists:academic_types,id',
            'id'=>'exists:levels,id'
        ]);
        $yearType = AcademicYearType::checkRelation($request->year , $request->type);
        $levels = [];
        if($request->filled('id')){
            $levels=Level::find($request->id);
        }else{
        foreach ($yearType->yearLevel as $yearLevel){
            $levels[] = $yearLevel->levels[0];
        }
    }
        return HelperController::api_response_format(200,$levels);
    }

    public function Assign_level_to(Request $request)
    {
        $rules =[
            'year' => 'array',
            'year.*' => 'exists:academic_years,id',
            'type' => 'array',
            'type.*' => 'required|exists:academic_types,id',
            'level' => 'required|exists:levels,id',
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails())
            return ['result' => false, 'value' => $validator->errors()];

        $count=0;
            while(isset($request->type[$count]))
                {
                    $year = AcademicYear::Get_current()->id;
                    if (isset($request->year[$count])) {
                        $year = $request->year[$count];
                    }

                    $academic_year_type = AcademicYearType::checkRelation($year, $request->type[$count]);
                    YearLevel::checkRelation($academic_year_type->id, $request->level);
                    $count++;
                }

        return HelperController::api_response_format(201, 'Level Assigned Successfully');

    }

    public function get(Request $request)
    {
        $levels=Level::paginate(HelperController::GetPaginate($request));
        return HelperController::api_response_format(200,$levels);

    }
}
