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
  /*  public function List_Types_with_Years(){
        $cat = AcademicType::with("AC_year")->get();
        return $cat;
    }*/
    public function List_Years_with_types(){
        $cat =Year_type_resource::collection( AcademicYear::with("AC_type")->get());

        return $cat;
    }
    public function deleteType(Request $req){
        $type = AcademicType::find($req->id);
        if($type){
            $type->delete();
            return response()->json(['msg'=>'Type Deleted Successfully'],200);
        }
        return response()->json(['msg'=>'Type Deleted Fail'],404);
    }
    public function Add_type_to_Year(Request $req){


        $valid = Validator::make($req->all(),[
            'name' => 'required' ,
            'segment_no'=>'required',
            'year'=>'required|exists:academic_years,id'
        ]);

        if($valid->fails()){
            return response()->json(['msg'=>$valid->errors()],404);

        }
        $Ac= AcademicType::create($req->all());
        AcademicYearType::create([
            'academic_year_id'=>$req->year,
            'academic_type_id' =>$Ac->id
        ]);

        if($Ac){
            return $this->List_Years_with_types();
        }
        return response()->json(['msg'=>'Type insertion Fail'],404);

    }
    public function updateType(Request $req){

        $valid = Validator::make($req->all(),[
            'name' => 'required' ,
            'segment_no'=>'required'
            ,'id'=>'required'
        ]);

        if ($valid->fails()){
            return response()->json(['msg'=>$valid->errors()],404);

        }
        $AC = AcademicType::Find($req->id);
        if(!$AC){
            return response()->json(['msg'=>'Not Found'],404);
        }

        $AC->update($req->all());
        if($AC){
            return $this->List_Years_with_types();
        }
        return response()->json(['msg'=>'UNKNOWN '],404);


    }
    public function Assign_to_anther_year(Request $req){

        $valid = Validator::make($req->all(),[
            'id_type' => 'required|exists:academic_types,id' ,
            'id_year'=>'required|exists:academic_years,id'
        ]);
        if ($valid->fails()){
            return response()->json(['msg'=>$valid->errors()],404);

        }

        $ac=AcademicType::Find($req->id_type);

        if ($ac ){
            AcademicYearType::create([
                'academic_year_id'=>$req->id_year,
                'academic_type_id' =>$req->id_type

            ]);
            return $this->List_Years_with_types();

        }
        return response()->json(['msg'=>'Assignment Fail'],404);

    }

}
