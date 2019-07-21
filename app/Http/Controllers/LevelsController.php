<?php

namespace App\Http\Controllers;

use App\AcademicYearType;
use App\YearLevel;
use Illuminate\Http\Request;

use App\Level;
use Validator;

class LevelsController extends Controller
{
    public function AddLevelWithYear(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'year' => 'required|exists:academic_years,id',
            'type' => 'required|exists:academic_types,id',
        ]);

        $level = Level::create([
            'name' => $request->name,
        ]);
        $yeartype = AcademicYearType::checkRelation($request->year, $request->type);
        YearLevel::create([
            'academic_year_type_id' => $yeartype->id,
            'level_id' => $level->id,
        ]);
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
            'year' => 'required|exists:academic_year_types,id'
        ]);
        return HelperController::api_response_format(200, Level::whereIn('id', Level::GetAllLevelsInYear($request->year))->get());
    }
}
