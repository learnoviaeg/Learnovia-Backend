<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Storage;

use Illuminate\Http\Request;
use App\AcademicYear;
use App\AcademicYearType;
use App\AcademicType;
use App\Enroll;
use App\Segment;
use App\User;
use Auth;
use Carbon\Carbon;
use App\Exports\YearsExport;
use Maatwebsite\Excel\Facades\Excel;

use App\Http\Resources\Academic_Year as Academic_YearResource;

class AcademicYearController extends Controller
{
 /**
     * @Description :Creates new academic year.
     * @param : Name of the year is required.
     * @return : All Years in database.
     *
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required'
        ]);
        $year = AcademicYear::create([
            'name' => $request->name
        ]);
        $years = AcademicYear::paginate(HelperController::GetPaginate($request));
        return HelperController::api_response_format(201, $years, 'Year Created Successfully');
    }

 /**
     * @Description :Get all years in database or get years with a given filter.
     * @param : 'search' as an optional parameter.
     * @return: If request contains search :returns years according to the search,
     *          else: returns all years in database.
     *
     */
    public function getall(Request $request,$call =0 )
    {
        $request->validate([
            'search' => 'nullable'
        ]);
        
        $years=AcademicYear::whereNull('deleted_at');
        if($request->filled('search'))
        {
            $years = AcademicYear::where('name', 'LIKE' , "%$request->search%"); 
        }
        if($call == 1 ){
            return $years->get();
        }
        $years =$years->paginate(HelperController::GetPaginate($request));
        return HelperController::api_response_format(202, $years);
    }

    public function get(Request $request)
    {
        $request->validate([
            'id' => 'exists:academic_years,id',
            'all' => 'boolean',
            'paginate' => 'integer'
        ]);
        //$year = AcademicYear::with('AC_Type');
        if ($request->filled('id')) {
            $year = AcademicYear::with('AC_Type')->where('id', $request->id)->first();
        }
        else if ($request->filled('all')) {
            $year = AcademicYear::with('AC_Type')->get();
        } else {
            $year = AcademicYear::with('AC_Type')->paginate(HelperController::GetPaginate($request));
        }
        return HelperController::api_response_format(200, $year);
    }

 /**
     * @Description :Updates name of a year.
     * @param : id and new name of the year.
     * @return: returns all years in database.
     *
     */
    public function update(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:academic_years,id',
            'name' => 'required'
        ]);
        $year = AcademicYear::whereId($request->id)->first();
        $year->update($request->all());
        $years=AcademicYear::get()->paginate(HelperController::GetPaginate($request));
        return HelperController::api_response_format(200, $years , 'Year edited successfully');
    }

    /**
     * @Description :Delete a year.
     * @param : id of the year.
     * @return: returns all years in database.
     *
     */
    public function destroy(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:academic_years,id',
        ]);

        $year = AcademicYear::whereId($request->id)->first();
        if(count( $year->YearType)>0){
            return HelperController::api_response_format(400, [], 'This year assigned to types, cannot be deleted');
        }
        Enroll::where('year',$request->id)->update(["year"=>null]);
        if ($year->delete()) {
            return HelperController::api_response_format(200, AcademicYear::get()->paginate(HelperController::GetPaginate($request)), 'Year Deleted Successfully');            
        }
        return HelperController::api_response_format(404, [], 'Not Found');
    }
    public function setCurrent_year(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:academic_years,id',
        ]);

        $year = AcademicYear::find($request->id);
        if($year->current == 1){
            $year->update(['current' => 0]);
            $types = $year->AC_Type;
            foreach($types as $type){
                $active_segment = Segment::where('academic_type_id',$type->id)
                                    ->where('current',1)
                                    ->first();
                if(isset($active_segment))
                    $active_segment->update(['current' => 0]);
            }
            unset($year->AC_Type);
        }
        else
            $year->update(['current' => 1]);
        
        $all = AcademicYear::where('id', '!=', $request->id)
            ->update(['current' => 0]);

        return HelperController::api_response_format(200, $year , 'Year toggled successfully');
    }

    // public function GetMyYears(Request $request)
    // {
    //     $result=array();
    //     $lev=array();
    //     $users = User::whereId(Auth::id())->with(['enroll.courseSegment' => function($query){
    //         //validate that course in my current course start < now && now < end
    //         $query->where('end_date', '>', Carbon::now())->where('start_date' , '<' , Carbon::now());
    //     },'enroll.courseSegment.segmentClasses.classLevel.yearLevels.yearType' => function($query) use ($request){
    //         if ($request->filled('year'))
    //             $query->where('academic_year_id', $request->year);            
    //     }])->first();
    //     foreach($users ->enroll as $enrolls)
    //         foreach($enrolls->courseSegment->segmentClasses as $segmetClas)
    //             foreach($segmetClas->classLevel as $clas)
    //                     foreach($clas->yearLevels as $level)
    //                         foreach($level->yearType as $typ)
    //                             if(!in_array($typ->academic_year_id, $result))
    //                             {
    //                                 $result[]=$typ->academic_year_id;
    //                                 $yearr[]=AcademicYear::find($typ->academic_year_id);
    //                             }
    //     if(isset($yearr) && count($yearr) > 0)
    //         return HelperController::api_response_format(201,$yearr, 'Here are your years');
        
    //     return HelperController::api_response_format(201,null, 'You are not enrolled in any year');
    // }
    public function GetMyYears(Request $request)
    {
        $result=array();
        $CS=array();

        if($request->user()->can('site/show-all-courses'))
        {
            $year = AcademicYear::get();
            if(count($year) == 0)
                return HelperController::api_response_format(201,null, 'No available years in the system');

            return HelperController::api_response_format(201,$year, 'Here are your years');
        }

        $course_segments = Enroll::where('user_id',Auth::id())->with(['courseSegment' => function($query){
            //validate that course in my current course start < now && now < end
            $query->where('end_date', '>', Carbon::now())->where('start_date' , '<' , Carbon::now());}])->get();
            foreach($course_segments as $course_segment){
                array_push($CS , $course_segment->course_segment);
            }
        $years = Enroll::where('user_id',Auth::id())->whereIn('course_segment' ,$CS)->pluck('year');
        $yearr = AcademicYear::whereIn('id', $years)->get();
        if(isset($yearr) && count($yearr) > 0)
            return HelperController::api_response_format(201,$yearr, 'Here are your years');
        
        return HelperController::api_response_format(201,null, 'You are not enrolled in any year');
    }

    public function export(Request $request)
    {
         $years = self::getall($request,1);
        $filename = uniqid();
         $file = Excel::store(new YearsExport($years), 'Year'.$filename.'.xls','public');
         $file = url(Storage::url('Year'.$filename.'.xls'));
         return HelperController::api_response_format(201,$file, 'Link to file ....');
    }
}
