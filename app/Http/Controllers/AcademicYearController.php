<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Storage;

use Illuminate\Http\Request;
use App\AcademicYear;
use App\AcademicYearType;
use App\AcademicType;
use App\Enroll;
use App\Segment;
use App\Events\MassLogsEvent;
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
        return HelperController::api_response_format(201, $years, __('messages.year.add'));
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
            $years = AcademicYear::where('name', 'LIKE' , "%$request->search%"); 
        
        if($call == 1 )
            return $years->get();

        return HelperController::api_response_format(202, $years->paginate(HelperController::GetPaginate($request)));
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
            $year = AcademicYear::where('id', $request->id)->first();
        }
        $year = AcademicYear::get()->paginate(HelperController::GetPaginate($request));
        // else if ($request->filled('all')) {
        //     $year = AcademicYear::with('AC_Type')->get();
        // } else {
        //     $year = AcademicYear::with('AC_Type')->paginate(HelperController::GetPaginate($request));
        // }
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
        return HelperController::api_response_format(200, $years , __('messages.year.update'));
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
        // if(count( $year->YearType)>0){
        //     return HelperController::api_response_format(400, [], __('messages.error.cannot_delete'));
        // }
        
        // for log event
        $logsbefore=Enroll::where('year',$request->id)->get();
        
        $check=Enroll::where('year',$request->id)->update(["year"=>null]);
        if($check > 0)
            event(new MassLogsEvent($logsbefore,'updated'));

        if ($year->delete()) {
            return HelperController::api_response_format(200, AcademicYear::get()->paginate(HelperController::GetPaginate($request)), __('messages.year.delete'));            
        }
        return HelperController::api_response_format(404, [], __('messages.error.not_found'));
    }
    
    public function setCurrent_year(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:academic_years,id',
        ]);

        $year = AcademicYear::find($request->id);
        if($year->current == 1)
            $year->update(['current' => 0]);
        
        else
            $year->update(['current' => 1]);
        
        // for log event
        $logsbefore=AcademicYear::where('id', '!=', $request->id)->get();

        $all = AcademicYear::where('id', '!=', $request->id)->update(['current' => 0]);
        if($all > 0)
            event(new MassLogsEvent($logsbefore,'updated'));

        return HelperController::api_response_format(200, $year , __('messages.success.toggle'));
    }

    public function GetMyYears(Request $request)
    {
        // $result=array();
        // $CS=array();

        // if($request->user()->can('site/show-all-courses'))
        // {
        //     $year = AcademicYear::get();
        //     if(count($year) == 0)
        //         return HelperController::api_response_format(201,null, __('messages.error.not_found'));

        //     return HelperController::api_response_format(201,$year, __('messages.year.list'));
        // }

        // $course_segments = Enroll::where('user_id',Auth::id())->with(['courseSegment' => function($query){
        //     //validate that course in my current course start < now && now < end
        //     $query->where('end_date', '>', Carbon::now())->where('start_date' , '<' , Carbon::now());}])->get();
        //     foreach($course_segments as $course_segment){
        //         array_push($CS , $course_segment->course_segment);
        //     }
        // $years = Enroll::where('user_id',Auth::id())->whereIn('course_segment' ,$CS)->pluck('year');

        $currentSegment= Segment::where('start_date', '<=',Carbon::now())
                        ->where('end_date','>=',Carbon::now())->pluck('academic_year_id');
        $myYears=Enroll::where('user_id',Auth::id())->whereIn('year',$currentSegment)->pluck('year');

        $yearr = AcademicYear::whereIn('id', $myYears)->get();
        // if(isset($yearr) && count($yearr) > 0)
        return HelperController::api_response_format(201,$yearr, __('messages.year.list'));
        
        // return HelperController::api_response_format(201,null, __('messages.error.no_available_data'));
    }

    public function export(Request $request)
    {
        $years = self::getall($request,1);
        $filename = uniqid();
        $file = Excel::store(new YearsExport($years), 'Year'.$filename.'.xlsx','public');
        $file = url(Storage::url('Year'.$filename.'.xlsx'));
        return HelperController::api_response_format(201,$file, __('messages.success.link_to_file'));
    }
}
