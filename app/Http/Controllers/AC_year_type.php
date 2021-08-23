<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Storage;
use Nwidart\Modules\Collection;
use App\Events\MassLogsEvent;
use App\AcademicType;
use App\AcademicYear;
use App\AcademicYearType;
use App\Enroll;
use App\Segment;
use App\User;
use Auth;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Resources\Year_type_resource;
use Validator;
use App\Exports\TypesExport;
use Maatwebsite\Excel\Facades\Excel;
use App\YearLevel;

class AC_year_type extends Controller
{
    /**
     * @Description: Get all Years with its types
     * @param: no take parameters
     * @return : response of all Years with its Typs
     *
     */
    public function List_Years_with_types(Request $request)
    {
        $request->validate([
            'year' => 'required|exists:academic_years,id',
            'dropdown' => 'boolean'
        ]);

        if($request->id != null)
        {
            $request->validate([
                'id' => 'exists:academic_types,id'
            ]);
            $types = AcademicType::where('id',$request->id)->first();
            return HelperController::api_response_format(200, $types);
        }
        else {
            $cat = AcademicYear::whereId($request->year)->first()->AC_Type->pluck('id');
            $types = AcademicType::with('yearType.academicyear')->whereIn('id',$cat);     
            if(isset($request->dropdown) && $request->dropdown == true)       
                return HelperController::api_response_format(200, $types->get());
            else
                return HelperController::api_response_format(200, $types->paginate(HelperController::GetPaginate($request)));
        }
    }

    public function get(Request $request,$call=0){

        $request->validate([
            'search' => 'nullable',
            'years' => 'array',
            'years.*' => 'exists:academic_years,id',
        ]);
    
        $all_types = AcademicType::whereNull('deleted_at');
        if(isset($request->search))
            $all_types->where('name', 'LIKE' , "%$request->search%");

        return HelperController::api_response_format(200, (new Collection($all_types->get()))->paginate(HelperController::GetPaginate($request)));
    }

    /**
     * @Description:Remove type
     * @param: request to access id of the type
     * @return : MSG 'Type Deleted Successfully' if deleted
     *          if not : return 'Type Deleted Fail'
     *
     **/
    public function deleteType(Request $req)
    {
        $req->validate([
            'id' => 'required|exists:academic_types,id'
        ]);

        $segment= Segment::where('academic_type_id',$req->id)->get();
        $level= YearLevel::whereIn('academic_year_type_id',AcademicYearType::where('academic_type_id',$req->id)->pluck('id'))->get();
        if(!(count($segment) == 0 && count($level) == 0))
            return HelperController::api_response_format(404, [], __('messages.error.cannot_delete'));
        
        AcademicType::whereId($req->id)->first()->delete();
        AcademicYearType::where('academic_type_id',$req->id)->delete();
        User::where('type',$req->id)->update(['type' => null]);
        Enroll::where('type',$req->id)->update(['type' => null]);
        $req['returnmsg'] = 'delete';
        $print = self::get($req);
        return $print;
    }

    /**
     *
     * @Description : add type "Like National and its NUM of terms " to specific year
     * @param : Request to Access id of Year , name of Type and its segment no
     * @return : if addition succeeded ->  return all Years with its Type
     *           if not -> return MSG: 'Type insertion Fail'
     *
     * ``
     */
    public function Add_type_to_Year(Request $req)
    {
        $valid = Validator::make($req->all(), [
            'name' => 'required',
            'segment_no' => 'required'
        ]);

        if ($valid->fails()) 
            return HelperController::api_response_format(400, $valid->errors(), __('messages.error.try_again'));
        
        AcademicType::firstOrCreate([
            'name' => $req->name,
            'segment_no' => $req->segment_no
        ]);

        $output= AcademicType::paginate(HelperController::GetPaginate($req));
        $req['returnmsg'] = 'add';
        $print = self::get($req);
        return $print;
    }

    /**
     *
     * @Description : update specific Type
     * @param : Request to access  id ,  new name or new segment_no of this Type
     * @return :  if modify succeeded ->  return all Years with its Type
     *            if not -> return MSG: 'Something went worng'
     *
     */
    public function updateType(Request $req)
    {
        $valid = Validator::make($req->all(), [
            'name' => 'required',
            'segment_no' => 'required',
            'id' => 'required|exists:academic_types,id',
            'year' => 'exists:academic_years,id',
        ]);

        if ($valid->fails()) 
            return HelperController::api_response_format(400, $valid->errors(), __('messages.error.try_again'));
        
        $AC = AcademicType::Find($req->id);
        if (!$AC) 
            return HelperController::api_response_format(404, [], __('messages.error.not_found'));

        $AC->update($req->all());
        if ($req->filled('year'))
            $yearType = AcademicYearType::where('academic_type_id', $AC->id)->update(['academic_year_id' => $req->year]);
        
        $AC->save();
        if ($AC) {
            $AC->AC_year;
            $output= AcademicType::paginate(HelperController::GetPaginate($req));
            $req['returnmsg'] = 'update';
            $print = self::get($req);
            return $print;
            // return HelperController::api_response_format(200, $output, 'Type edited successfully');
        }
        return HelperController::api_response_format(400, [], __('messages.error.try_again'));
    }

    /**
     * @Description :assign specific Type to specific Year
     * @param : request to access id_type of Type and id_year of year
     * @return : if Assignment succeeded ->  return all Years with its Type
     *           if not -> return MSG 'Assignment Fail'
     *
     */
    public function Assign_to_anther_year(Request $req)
    {
        $valid = Validator::make($req->all(), [
            'id_type' => 'required|exists:academic_types,id',
            'id_year'=>'required|array',
            'id_year.*' => 'required|exists:academic_years,id'
        ]);
        if ($valid->fails()) {
            return HelperController::api_response_format(400, $valid->errors(), __('messages.error.try_again'));
        }
        $counter=0;
        while(isset($req->id_year[$counter]))
        {
            $academic_year_type=AcademicYearType::checkRelation($req->id_year[$counter],$req->id_type);
            $counter++;
        }
         return HelperController::api_response_format(200, 'Academic Year-Type Relation Created Succssesfully');
    }

    public function GetMytypes(Request $request)
    {
        $result=array();
        $lev=array();

        if($request->user()->can('site/show-all-courses'))
        {
            $year = AcademicYear::where('current',1)->first();
            if ($request->filled('year'))
                $year = AcademicYear::whereId($request->year)->first();

            if(!isset($year))
                return HelperController::api_response_format(200,null, __('messages.error.no_active_year'));

            if(count($year->AC_Type) == 0)
                return HelperController::api_response_format(201,null, __('messages.error.no_available_data'));

            return HelperController::api_response_format(200,$year->AC_Type, __('messages.type.list'));
        }

        $users = User::whereId(Auth::id())->with(['enroll.courseSegment' => function($query){
            //validate that course in my current course start < now && now < end
            $query->where('end_date', '>', Carbon::now())->where('start_date' , '<' , Carbon::now());
        },'enroll.courseSegment.segmentClasses.classLevel.yearLevels.yearType' => function($query) use ($request){
            if ($request->filled('year'))
                $query->where('academic_year_id', $request->year);            
        }])->first();

        foreach($users ->enroll as $enrolls){
            if(isset($enrolls->courseSegment) && isset($enrolls->courseSegment->segmentClasses)){
                foreach($enrolls->courseSegment->segmentClasses as $segmetClas)
                foreach($segmetClas->classLevel as $clas)
                        foreach($clas->yearLevels as $level)
                            foreach($level->yearType as $typ)
                                if(!in_array($typ->academic_type_id, $result))
                                {
                                    $result[]=$typ->academic_type_id;
                                    $type[]=AcademicType::find($typ->academic_type_id);
                                }
            }
        }
            
        if(isset($type) && count($type) > 0)
            return HelperController::api_response_format(201,$type, __('messages.type.list'));
        
        return HelperController::api_response_format(201,null, __('messages.error.no_available_data'));
    }
    
    public function export(Request $request)
    {
        // return Excel::download(new TypesExport, 'types.xls');
        $typeIDs = self::get($request,1);
        
        $filename = uniqid();
        $file = Excel::store(new TypesExport($typeIDs), 'Type'.$filename.'.xls','public');
        $file = url(Storage::url('Type'.$filename.'.xls'));
        return HelperController::api_response_format(201,$file, __('messages.success.link_to_file'));
    }
}
