<?php

namespace App\Http\Controllers;

use App\AcademicType;
use App\AcademicYear;
use App\AcademicYearType;
use Illuminate\Http\Request;
use App\Http\Resources\Year_type_resource;
use Validator;

class AC_year_type extends Controller
{
    public function List_Years_with_types()
    {
        $cat = Year_type_resource::collection(AcademicYear::with("AC_type")->get());
        return HelperController::api_response_format(200, $cat);
    }

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

    public function updateType(Request $req)
    {
        $valid = Validator::make($req->all(), [
            'name' => 'required',
            'segment_no' => 'required'
            , 'id' => 'required'
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
            return HelperController::api_response_format(404, $this->List_Years_with_types());
        }
        return HelperController::api_response_format(400, [], 'Something went worng');
    }

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
            return HelperController::api_response_format(400, $this->List_Years_with_types());
        }
        return HelperController::api_response_format(400, [], 'Assignment Fail');
    }

}
