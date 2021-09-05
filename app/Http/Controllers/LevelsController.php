<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Storage;

use App\AcademicYearType;
use App\AcademicType;
use App\YearLevel;
use Illuminate\Http\Request;
use App\AcademicYear;
use App\Level;
use App\Enroll;
use App\Course;
use App\Segment;
use App\Classes;
use App\User;
use Carbon\Carbon;
use App\CourseSegment;
use Auth;
use App\Events\MassLogsEvent;
use Illuminate\Support\Collection;
use Validator;
use App\Exports\LevelsExport;
use Maatwebsite\Excel\Facades\Excel;
use Response;
use App\ClassLevel;

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
            'year' => 'array',
            'year.*' => 'exists:academic_years,id',
            'type' => 'array|required',
            'type.*' => 'exists:academic_types,id',
        ]);

        if ($request->filled('year') || $request->filled('type')) {
            foreach ($request->type as $type) {
                # code...
                $level = Level::firstOrCreate([
                    'name' => $request->name,
                    'academic_type_id' => $type
                ]);
            }
        }
        return HelperController::api_response_format(201, Level::paginate(HelperController::GetPaginate($request)), __('messages.level.add'));
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
        
        $courses= Course::where('level_id',$request->id)->get();
        $classes = Classes::where('level_id',$request->id)->get();
        if (count($courses) > 0 || count($classes) > 0)
            return HelperController::api_response_format(404, [] , __('messages.error.cannot_delete'));

        // $yearLevel=YearLevel::where('level_id',$request->id)->first();
        // if(isset($yearLevel))
        //     $yearLevel->delete();
        Level::whereId($request->id)->first()->delete();

        // $levels = Level::paginate(HelperController::GetPaginate($request));
        return HelperController::api_response_format(203, Level::paginate(HelperController::GetPaginate($request)), __('messages.level.delete'));
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
        $request->validate([
            // 'name' => 'required',
            'id' => 'required|exists:levels,id',
            'year' => 'exists:academic_years,id',
            'type' => 'exists:academic_types,id',
        ]);

        $level = Level::find($request->id);
        if(isset($request->name))
            $level->name = $request->name;
        $level->save();

        if ($request->filled('type'))
            Level::where('id',$request->id)->update(['academic_type_id' => $request->type]);
                
        return HelperController::api_response_format(200, Level::paginate(HelperController::GetPaginate($request)), __('messages.level.update'));
    }

    /**
     * Get levels in year
     * 
     * @param  [int] id, type, year
     * @return [object] level
    */
    public function GetAllLevelsInYear(Request $request, $call = 0)
    {
        $request->validate([
            'years' => 'array',
            'years.*' => 'nullable|exists:academic_years,id',
            'types' => 'array',
            'types.*' => 'nullable|exists:academic_types,id',
            'id' => 'exists:levels,id',
            'search' => 'nullable'
        ]);
        
        if ($request->filled('id')) {
            $all_levels = Level::find($request->id);
            return HelperController::api_response_format(200, $all_levels);
        }
        
        $levels = new Level;
        $levels = Level::whereHas('type', function ($q) use ($request) {
                                                if($request->filled('types'))
                                                    $q->whereIn("academic_type_id", $request->types);
                                            });
        
        if($request->filled('search'))
            $levels=$levels->where('name', 'LIKE' , "%$request->search%");

        $all_levels = collect([]);
        $levels= $levels->get();  

        foreach ($levels as $level)
        {
            $academic_type_id= $level->type->pluck('id')->unique();
            $level['academicType']= AcademicType::whereIn('id',$academic_type_id)->pluck('name');
            unset($level->type);
            $all_levels->push($level); 
        }

        if($call == 1){
        //    $levelsIds = $all_levels->pluck('id');
           return $all_levels;
        }

        return HelperController::api_response_format(200, $all_levels->paginate(HelperController::GetPaginate($request)), __('messages.level.list'));
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
    public function get(Request $request , $call = 0)
    {
        $request->validate([
            'year' => 'required|exists:academic_years,id',
            'type' => 'required|exists:academic_types,id',
            'id' => 'exists:levels,id',
        ]);

        $levels = collect([]);
        // $all_levels = collect([]);

        // $types=Segment::where('start_date', '<=',Carbon::now())
        //     ->where('end_date','>=',Carbon::now())->pluck('academic_type_id');

            // foreach ($yearType->yearLevel as $yearLevel) {
            //     if(count($yearLevel->levels) > 0)
            //         $levels[] = $yearLevel->levels[0]->id;
            // }

        $levels = Level::whereNull('deleted_at');        

        if($request->has('type')){
            $levels->where('academic_type_id',$request->type);
        }

        $levels = $levels->get();
        
        foreach ($levels as $level){
            $academic_type_id= $level->type->pluck('id')->unique();
            $level['academicType']= AcademicType::whereIn('id',$academic_type_id)->pluck('name');
        // // $academic_year_id= $level->years->pluck('academic_year_id')->unique();
        // // $level['academicYear']= AcademicYear::whereIn('id',$academic_year_id)->pluck('name');
            unset($level->type);
        // $all_levels->push($level); 
        
        }
        return HelperController::api_response_format(200, $levels->paginate(HelperController::GetPaginate($request)), __('messages.level.list'));  
    }

    public function GetMyLevels(Request $request)
    {
        $request->validate([
            'type' => 'array',
            'type.*' => 'exists:academic_types,id',
        ]);
        $result=array();
        $lev=array();

        // if($request->user()->can('site/show-all-courses'))
        // {
        //     $year_type = AcademicYear::where('current',1)->first();
        //     if(isset($year_type))
        //         $year_type = $year_type->YearType->pluck('id');
          
        //     // if ($request->filled('type'))
        //     //     $year_type = AcademicYearType::whereIn('academic_type_id',$request->type)->pluck('id');

        //     if(!isset($year_type))
        //         return HelperController::api_response_format(200,null, __('messages.error.no_active_year'));

        //     $year_levels = YearLevel::whereIn('academic_year_type_id', $year_type)->pluck('level_id');
        //     $levels;
        //     if(isset($year_levels))
        //         $levels = Level::whereIn('id',$year_levels)->get();

        //     if(count($levels) == 0)
        //         return HelperController::api_response_format(201,null, __('messages.error.no_available_data'));

        //     return HelperController::api_response_format(200,$levels, __('messages.level.list'));
        // }

        $types=Segment::where('start_date', '<=',Carbon::now())
                ->where('end_date','>=',Carbon::now())->pluck('academic_type_id');
        $myLevels=Enroll::where('user_id',Auth::id())->whereIn('type',$types)->pluck('level');
        $levels->whereIn('id',$myLevels);

        // $users = User::whereId(Auth::id())->with(['enroll.courseSegment' => function($query){
        //     //validate that course in my current course start < now && now < end
        //     $query->where('end_date', '>', Carbon::now())->where('start_date' , '<' , Carbon::now());
        // },'enroll.courseSegment.segmentClasses.classLevel.yearLevels.yearType' => function($query) use ($request){
        //     if ($request->filled('type'))
        //         $query->whereIn('academic_type_id', $request->type);            
        // }])->first();

        // foreach($users ->enroll as $enrolls){
        //     if(isset($enrolls->courseSegment) && isset($enrolls->courseSegment->segmentClasses)){
        //         foreach($enrolls->courseSegment->segmentClasses as $segmetClas)
        //         foreach($segmetClas->classLevel as $clas)
        //                 foreach($clas->yearLevels as $level)
        //                     if(count($level->yearType) > 0)
        //                         if(!in_array($level->level_id, $result))
        //                         {
        //                             $result[]=$level->level_id;
        //                             $lev[]=Level::find($level->level_id);
        //                         }
        //     }
        // }
                             
        if(count($lev) > 0)
            return HelperController::api_response_format(201,$lev, __('messages.level.list'));
        
        return HelperController::api_response_format(201, __('messages.error.no_available_data'));
    }

    public function export(Request $request)
    {
        $levelsIDs = self::GetAllLevelsInYear($request,1);
        $filename = uniqid();
        $file = Excel::store(new LevelsExport($levelsIDs), 'levels'.$filename.'.xls','public');
        $file = url(Storage::url('levels'.$filename.'.xls'));
        return HelperController::api_response_format(201,$file, __('messages.success.link_to_file'));
    }
}
