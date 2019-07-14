<?php

namespace App\Http\Controllers;

use App\AcademicYearType;
use App\YearLevel;
use Illuminate\Http\Request;

use App\Level;

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
        $yeartype = AcademicYearType::checkRelation($request->year , $request->type);
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

    }

    public function GetAllLevelsInYear(Request $request)
    {
        $request->validate([
            'year' => 'required|exists:academic_year_types,id'
        ]);
        return HelperController::api_response_format(200, Level::whereIn('id', Level::GetAllLevelsInYear($request->year))->get());
    }
}
