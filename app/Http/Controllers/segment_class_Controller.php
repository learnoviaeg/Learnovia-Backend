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
    public function List_Classes_with_all_segment()
    {
        $cat = Segment_class_resource::collection(ClassLevel::with("Segment_class")->get());
        return HelperController::api_response_format(200, $cat);
    }

    public function Add_Segment_with_class(Request $req)
    {
        $valid = Validator::make($req->all(), [
            'name' => 'required',
            'class_level_id' => 'required|exists:class_levels,id'
        ]);

        if ($valid->fails()) {
            return HelperController::api_response_format(400, $valid->errors());
        }
        //check here

        $segment = Segment::create([
            'name' => $req->name
        ]);
        SegmentClass::create([
            'class_level_id' => $req->class_level_id,
            'segment_id' => $segment->id
        ]);

        if ($segment) {
            return HelperController::api_response_format(200, $segment, 'Type insertion sucess');
        }
        return HelperController::NOTFOUND();

    }

    public function deleteSegment(Request $req)
    {
        $req->validate([
            'id' => 'required|exists:segments,id'
        ]);
        $segment = Segment::find($req->id);
        if ($segment) {
            $segment->delete();
            return HelperController::api_response_format(200, $segment, 'Segment Deleted Successfully');
        }
        return HelperController::NOTFOUND();
    }

    public function Assign_to_anther_Class(Request $req)
    {

        $valid = Validator::make($req->all(), [
            'id_segment' => 'required|exists:segments,id',
            'class_level_id' => 'required|exists:class_levels,id'
        ]);
        if ($valid->fails()) {
            return HelperController::api_response_format(400, $valid->errors(), 'Segment Deleted Successfully');
        }
        $ac = Segment::Find($req->id_segment);
        if ($ac) {
            SegmentClass::create([
                'segment_id' => $req->id_segment,
                'class_level_id' => $req->class_level_id

            ]);
            return HelperController::api_response_format(200, $ac, 'Assignment sucess');

        }
        return HelperController::NOTFOUND();

    }
}
