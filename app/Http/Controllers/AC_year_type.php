<?php

namespace App\Http\Controllers;

use App\AcademicType;
use App\AcademicYear;
use App\AcademicYearType;
use App\Classes;
use App\Course;
use App\CourseSegment;
use App\Segment;
use App\YearLevel;
use Illuminate\Http\Request;
use App\Http\Resources\Year_type_resource;
use Validator;
use App\SegmentClass;
use App\ClassLevel;
use App\Level;
use DB;

class AC_year_type extends Controller
{
    /**
    * @Description: Get all Years with its types
    *@param: no take parameters
    *@return : response of all Years with its Typs
    *
    */
    public function List_Years_with_types()
    {
        $cat = Year_type_resource::collection(AcademicYear::with("AC_type")->get());
        return HelperController::api_response_format(200, $cat);
    }
    /**
     *@Description:Get all Types with specific year
     *@param: request to access id of the Year
     *@return :  all Types with specific year
     *
     **/
    public function View_types_with_specific_year(Request $request)
    {
        return AcademicYear::Get_types_with_specific_year($request->id);
    }
    public function View_Levels_with_specific_Type(Request $request)
    {
        return AcademicType::Get_Levels_with_specific_Type($request->id);
    }
    public function View_Classes_with_specific_Level(Request $request)
    {
        return Level::Get_Classes_with_specific_Level($request->id);
    }

    public function View_Segments_with_specific_Class(Request $request)
    {
        return Course::Get_Segments_with_specific_Class($request->id);
    }

    public function View_Courses_with_specific_segment(Request $request)
    {

    return Segment::Get_Courses_with_specific_segment($request->id);
    }
    /**
     *@Description:Remove type
     *@param: request to access id of the type
     *@return : MSG 'Type Deleted Successfully' if deleted
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
            return HelperController::api_response_format(200, $type, 'Type Deleted Successfully');
        }
        return HelperController::api_response_format(400, [], 'Type Deleted Fail');
    }

        /**
         *
         @Description : add type "Like National and its NUM of terms " to specific year
         * @param : Request to Access id of Year , name of Type and its segment no
         * @return : if addition succeeded ->  return all Years with its Type
         *           if not -> return MSG: 'Type insertion Fail'
         *
``
         */
    public function Add_type_to_Year(Request $req)
    {
        $valid = Validator::make($req->all(), [
            'name' => 'required',
            'segment_no' => 'required',
            'year' => 'required|exists:academic_years,id'
        ]);

        if ($valid->fails()) {
            return HelperController::api_response_format(400, $valid->errors(), 'Something went wrong');
        }
        $Ac = AcademicType::create($req->all());
        AcademicYearType::create([
            'academic_year_id' => $req->year,
            'academic_type_id' => $Ac->id
        ]);
        if ($Ac) {
            return HelperController::api_response_format(404, $this->List_Years_with_types());
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
            'segment_no' => 'required'
            , 'id' => 'required||exists:academic_types,id'
        ]);

        if ($valid->fails()) {
            return HelperController::api_response_format(400, $valid->errors(), 'Something went wrong');
        }
        $AC = AcademicType::Find($req->id);
        if (!$AC) {
            return HelperController::api_response_format(404, [], 'NotFound');
        }

        $AC->update($req->all());
        if ($AC) {
            return HelperController::api_response_format(200, $this->List_Years_with_types());
        }
        return HelperController::api_response_format(400, [], 'Something went worng');
    }

    /**
     *@Description :assign specific Type to specific Year
     * @param : request to access id_type of Type and id_year of year
     * @return : if Assignment succeeded ->  return all Years with its Type
     *           if not -> return MSG 'Assignment Fail'
     *
     */
    public function Assign_to_anther_year(Request $req)
    {
        $valid = Validator::make($req->all(), [
            'id_type' => 'required|exists:academic_types,id',
            'id_year' => 'required|exists:academic_years,id'
        ]);
        if ($valid->fails()) {
            return HelperController::api_response_format(400, $valid->errors(), 'Something went wrong');
        }
        $ac = AcademicType::Find($req->id_type);
        if ($ac) {
            AcademicYearType::create([
                'academic_year_id' => $req->id_year,
                'academic_type_id' => $req->id_type

            ]);
            return HelperController::api_response_format(200, $this->List_Years_with_types());
        }
        return HelperController::api_response_format(400, [], 'Assignment Fail');
    }

}
