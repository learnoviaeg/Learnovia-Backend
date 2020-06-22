<?php

namespace App\Http\Controllers;

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
            'year' => 'required|exists:academic_years,id'
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
            $cat = AcademicYear::whereId($request->year)->first()->AC_Type;
            return HelperController::api_response_format(200, $cat->paginate(HelperController::GetPaginate($request)));
        }
    }

    public function get(Request $request){

        $request->validate([
            'search' => 'nullable'
        ]);
        if($request->filled('search'))
        {
            $types = AcademicType::with('AC_year')->where('name', 'LIKE' , "%$request->search%")->get()->paginate(HelperController::GetPaginate($request));
            return HelperController::api_response_format(202, $types);   
        }
        $types = AcademicType::with('AC_year')->paginate(HelperController::GetPaginate($request));
        return HelperController::api_response_format(202, $types);
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
        $type = AcademicType::find($req->id);
        if ($type) {
            $type->delete();
            $output= AcademicType::paginate(HelperController::GetPaginate($req));
            $print = self::get($req);
            return $print;
        }
        return HelperController::api_response_format(400, [], 'Type Deleted Fail');
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
            'segment_no' => 'required',
            'year' => 'array',
            'year.*' => 'exists:academic_years,id'
        ]);

        if ($valid->fails()) {
            return HelperController::api_response_format(400, $valid->errors(), 'Something went wrong');
        }
        $Ac = AcademicType::firstOrCreate([
            'name' => $req->name,
            'segment_no' => $req->segment_no
        ]);
       if($req->filled('year')){
           foreach ($req->year as $year) {
               # code...
               AcademicYearType::firstOrCreate([
                'academic_year_id' => $year,
                'academic_type_id' => $Ac->id
            ]);

           }
        }
        if ($Ac) {
            $output= AcademicType::paginate(HelperController::GetPaginate($req));
            return HelperController::api_response_format(200, $output, 'Type Added Successfully');
        }
        return HelperController::api_response_format(404, [], 'Type insertion Fail');
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

        if ($valid->fails()) {
            return HelperController::api_response_format(400, $valid->errors(), 'Something went wrong');
        }
        $AC = AcademicType::Find($req->id);
        if (!$AC) {
            return HelperController::api_response_format(404, [], 'NotFound');
        }

        $AC->update($req->all());
        if ($req->filled('year')) {
            if(count($AC->AC_year) > 0){
                $yearType = AcademicYearType::checkRelation($AC->AC_year[0]->id, $req->id);
                $yearType->delete();
            }
            AcademicYearType::create([
                'academic_year_id' => $req->year,
                'academic_type_id' => $req->id
            ]);
        }
        if ($AC) {
            $AC->AC_year;
            $output= AcademicType::paginate(HelperController::GetPaginate($req));
            return HelperController::api_response_format(200, $output, 'Type edited successfully');
        }
        return HelperController::api_response_format(400, [], 'Something went wrong');
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
            return HelperController::api_response_format(400, $valid->errors(), 'Something went wrong');
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
        $users = User::whereId(Auth::id())->with(['enroll.courseSegment' => function($query){
            //validate that course in my current course start < now && now < end
            $query->where('end_date', '>', Carbon::now())->where('start_date' , '<' , Carbon::now());
        },'enroll.courseSegment.segmentClasses.classLevel.yearLevels.yearType' => function($query) use ($request){
            if ($request->filled('year'))
                $query->where('academic_year_id', $request->year);            
        }])->first();

        foreach($users ->enroll as $enrolls)
            foreach($enrolls->courseSegment->segmentClasses as $segmetClas)
                foreach($segmetClas->classLevel as $clas)
                        foreach($clas->yearLevels as $level)
                            foreach($level->yearType as $typ)
                                if(!in_array($typ->academic_type_id, $result))
                                {
                                    $result[]=$typ->academic_type_id;
                                    $type[]=AcademicType::find($typ->academic_type_id);
                                }
        if(isset($type) && count($type) > 0)
            return HelperController::api_response_format(201,$type, 'There are your types');
        
        return HelperController::api_response_format(201,null, 'You haven\'t types');
    }
}
