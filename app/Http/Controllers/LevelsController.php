<?php

namespace App\Http\Controllers;

use App\AcademicYearType;
use App\YearLevel;
use Illuminate\Http\Request;
use App\AcademicYear;
use App\Level;
use App\Enroll;
use App\User;
use Carbon\Carbon;
use App\CourseSegment;
use Auth;
use Illuminate\Support\Collection;
use Validator;

class LevelsController extends Controller
{
    /**
     * Add level with year
     * 
     * @param  [string] name 
     * @param  [array] year, type
     * @return [object] levels, Level Created Successfully
    */
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
        $levels = Level::paginate(HelperController::GetPaginate($request));
        return HelperController::api_response_format(201, $levels, 'Level Created Successfully');
    }

    /**
     * delete level with year
     * 
     * @param  [int] id 
     * @return [object] levels, Level deleted Successfully
    */
    public function Delete(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:levels,id',
        ]);

        $level = Level::find($request->id);
        if ($level)
            $level->delete();
            $levels = Level::paginate(HelperController::GetPaginate($request));
        return HelperController::api_response_format(203, $levels, 'Level Deleted Successfully');
    }

    /**
     * update level with year
     * 
     * @param  [int] id 
     * @param  [string] name 
     * @return [object] levels, Level updated Successfully
    */
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
        $levels=Level::paginate(HelperController::GetPaginate($request));
        return HelperController::api_response_format(200, $levels, 'Level Updated Successfully');
    }

    /**
     * Get levels in year
     * 
     * @param  [int] id, type, year
     * @return [object] level
    */
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

    /**
     * Assign level to year
     * 
     * @param  [int] level 
     * @param  [array] year, type
     * @return [string] Level Assigned Successfully
    */
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

    /**
     * get levels
     * 
     * @param  [string] search
     * @return [string] Levels
    */
    public function get(Request $request)
    {
        $request->validate([
            'search' => 'nullable'
        ]);
        if($request->filled('search'))
        {
            $levels = Level::where('name', 'LIKE' , "%$request->search%")->get()
            ->paginate(HelperController::GetPaginate($request));
            return HelperController::api_response_format(202, $levels);   
        }
        $levels = Level::paginate(HelperController::GetPaginate($request));
        return HelperController::api_response_format(200, $levels);
    }

    public function GetMyLevels(Request $request)
    {
        $request->validate([
            'type' => 'array',
            'type.*' => 'exists:academic_types,id',
        ]);
        $result=array();
        $lev=array();
        $users = User::whereId(Auth::id())->with(['enroll.courseSegment' => function($query){
            //validate that course in my current course start < now && now < end
            $query->where('end_date', '>', Carbon::now())->where('start_date' , '<' , Carbon::now());
        },'enroll.courseSegment.segmentClasses.classLevel.yearLevels.yearType' => function($query) use ($request){
            if ($request->filled('type'))
                $query->whereIn('academic_type_id', $request->type);            
        }])->first();

        foreach($users ->enroll as $enrolls)
            foreach($enrolls->courseSegment->segmentClasses as $segmetClas)
                foreach($segmetClas->classLevel as $clas)
                        foreach($clas->yearLevels as $level)
                            if(count($level->yearType) > 0)
                                if(!in_array($level->level_id, $result))
                                {
                                    $result[]=$level->level_id;
                                    $lev[]=Level::find($level->level_id);
                                }
                                
        if(count($lev) > 0)
            return HelperController::api_response_format(201,$lev, 'There are your Levels');
        
        return HelperController::api_response_format(201, 'You haven\'t Levels');
    }
}
