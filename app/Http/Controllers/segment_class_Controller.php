<?php

namespace App\Http\Controllers;

use App\ClassLevel;
use Illuminate\Http\Request;
use Validator;
use App\SegmentClass;
use App\Segment;
use App\Http\Resources\Segment_class_resource;

class segment_class_Controller extends Controller
{
    public function List_Classes_with_all_segment(){
     $cat =Segment_class_resource::collection( ClassLevel::with("Segment_class")->get());
//        $cat =ClassLevel::with("Segment_class")->get();

        return $cat;
    }
    public function Add_Segment_with_class(Request $req){


        $valid = Validator::make($req->all(),[
            'name' => 'required' ,
            'class_level_id'=>'required'/*exists:class_levels,id*/
        ]);

        if($valid->fails()){
            return response()->json(['msg'=>$valid->errors()],404);

        }
        $segment= Segment::create($req->all());
        SegmentClass::create([
            'class_level_id'=>$req->class_level_id,
            'segment_id' =>$segment->id
        ]);

        if($segment){
            return response()->json(['msg'=>'Type insertion sucess'],200);
        }
        return response()->json(['msg'=>'Type insertion Fail'],404);

    }
    public function deleteSegment(Request $req){
        $segment = Segment::find($req->id);
        if($segment){
            $segment->delete();
            return response()->json(['msg'=>'Segment Deleted Successfully'],200);
        }
        return response()->json(['msg'=>'Segment Deleted Fail'],404);
    }
    public function Assign_to_anther_Class(Request $req){

        $valid = Validator::make($req->all(),[
            'id_segment' => 'required|exists:segments,id' ,
            'class_level_id'=>'required|exists:class_levels,id'
        ]);
        if ($valid->fails()){
            return response()->json(['msg'=>$valid->errors()],404);

        }

        $ac=Segment::Find($req->id_segment);

        if ($ac ){
            SegmentClass::create([
                'segment_id'=>$req->id_segment,
                'class_level_id' =>$req->class_level_id

            ]);
            return response()->json(['msg'=>'Assignment sucess'],200);

        }
        return response()->json(['msg'=>'Assignment Fail'],404);

    }
}
