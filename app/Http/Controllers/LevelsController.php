<?php

namespace App\Http\Controllers;

use App\AcademicYearType;
use App\YearLevel;
use Illuminate\Http\Request;
use App\AcademicYear;
use App\Level;
use Illuminate\Support\Collection;
use Validator;

class LevelsController extends Controller
{
    public function AddLevelWithYear(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'year' => 'array|required_with:type',
            'year.*' => 'exists:academic_years,id',
            'type' => 'array|required_with:year',
            'type.*' => 'exists:academic_types,id',
        ]);
        $level = Level::firstOrCreate([
            'name' => $request->name,
        ]);
        if ($request->filled('year') && $request->filled('type')) {
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
            'id' => 'required|exists:levels,id',
        ]);

        $level = Level::find($request->id);
        if ($level)
            $level->delete();
        return HelperController::api_response_format(203, Level::get(), 'Level Deleted Successfully');
    }


    public function UpdateLevel(Request $request)
    {
        $valid = Validator::make($request->all(), [
            'name' => 'required',
            'id' => 'required|exists:levels,id'
        ]);

        if ($valid->fails())
            return HelperController::api_response_format(400, $valid->errors(), 'Something went wrong');
        $level = Level::find($request->id);
        $level->name = $request->name;
        $level->save();
        return HelperController::api_response_format(200, $level, 'Level Updated Successfully');
    }

    public function GetAllLevelsInYear(Request $request)
    {
        $request->validate([
            'year' => 'required|exists:academic_years,id',
            'type' => 'required|exists:academic_types,id',
            'id' => 'exists:levels,id',
        ]);
        $yearType = AcademicYearType::checkRelation($request->year, $request->type);
        $levels = collect([]);
        if ($request->filled('id')) {
            $levels = Level::find($request->id);
        } else {
            foreach ($yearType->yearLevel as $yearLevel) {
                $levels[] = $yearLevel->levels[0];
            }
        }
        return HelperController::api_response_format(200, $levels->paginate(HelperController::GetPaginate($request)));
    }

    public function Assign_level_to(Request $request)
    {
        $rules = [
            'year' => 'array',
            'year.*' => 'exists:academic_years,id',
            'type' => 'array',
            'type.*' => 'required|exists:academic_types,id',
            'level' => 'required|exists:levels,id',
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails())
            return ['result' => false, 'value' => $validator->errors()];

        $count = 0;
        while (isset($request->type[$count])) {
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
        $levels = Level::paginate(HelperController::GetPaginate($request));
        return HelperController::api_response_format(200, $levels);
    }
}
