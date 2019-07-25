<?php

namespace App\Http\Controllers;

use App\AcademicYearType;
use App\ClassLevel;
use App\YearLevel;
use Illuminate\Http\Request;
use Validator;
use App\SegmentClass;
use App\Segment;
use App\Http\Resources\Segment_class_resource;

class segment_class_Controller extends Controller

{

    /**
     * @Description: Get all Classes with its Segments
     * @param: no take parameters
     * @return : response of all Classes with its Segments
     *
     */
    public function List_Classes_with_all_segment()
    {
        return HelperController::api_response_format(200, Segment::with('Segment_class')->get());
    }

    /**
     *
     * @Description : add segment to specific Class
     * @param : Request to Access name of Segment  and class_level_id of class
     * @return : if addition succeeded ->  return MSG : 'Type insertion sucess'
     *           if not -> return MSG: 'NOTFOUND Error '
     *
     * ``
     */
    public function Add_Segment_with_class(Request $req)
    {
        $valid = Validator::make($req->all(), [
            'name' => 'required',
            'year' => 'required|exists:academic_years,id',
            'type' => 'required|exists:academic_types,id',
            'level' => 'required|exists:levels,id',
            'class' => 'required|exists:classes,id',
        ]);

        if ($valid->fails()) {
            return HelperController::api_response_format(400, $valid->errors());
        }

        $segment = Segment::create([
            'name' => $req->name
        ]);
        $yeartype = AcademicYearType::checkRelation($req->year, $req->type);
        $yearlevel = YearLevel::checkRelation($yeartype->id, $req->level);
        $classLevel = ClassLevel::checkRelation($req->class, $yearlevel->id);
        SegmentClass::create([
            'class_level_id' => $classLevel->id,
            'segment_id' => $segment->id,
        ]);

        if ($segment) {
            return HelperController::api_response_format(200, $segment, 'Type insertion sucess');
        }
        return HelperController::NOTFOUND();

    }

    /**
     * @Description:Remove Segment
     * @param: request to access id of the Segment
     * @return : MSG 'Segment Deleted Successfully' if deleted
     *          if not : return 'NotFound Error'
     *
     **/

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

    /**
     * @Description :assign specific Segment to specific Class
     * @param : request to access id_segment of Segment and class_level_id
     * @return : if Assignment succeeded ->  return MSG -> 'Assignment sucess'
     *           if not -> return "NOTFOUND Error"
     *
     */
    public function Assign_to_anther_Class(Request $req)
    {

        $valid = Validator::make($req->all(), [
            'id_segment' => 'required|exists:segments,id',
            'class_level_id' => 'required|exists:class_levels,id'
        ]);
        if ($valid->fails()) {
            return HelperController::api_response_format(400, $valid->errors());
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

    public function update(Request $request)
    {
        $valid = Validator::make($request->all(), [
            'id' => 'required|exists:segments,id',
            'name' => 'required',
            'year' => 'exists:academic_years,id',
            'type' => 'exists:academic_types,id|required_with:year',
            'level' => 'exists:levels,id|required_with:year',
            'class' => 'exists:classes,id|required_with:year',
        ]);

        if ($valid->fails())
            return HelperController::api_response_format(400, $valid->errors());

        $segment = Segment::find($request->id);
        $segment->name = $request->name;
        $segment->save();
        if ($request->filled('year')) {
            $oldyearType = AcademicYearType::checkRelation($segment->Segment_class[0]->classes[0]->classlevel->yearLevels[0]->yearType[0]->academicyear[0]->id , $segment->Segment_class[0]->classes[0]->classlevel->yearLevels[0]->yearType[0]->academictype[0]->id);
            $newyearType = AcademicYearType::checkRelation($request->year, $request->type);
            $oldyearLevel = YearLevel::checkRelation($oldyearType->id, $segment->Segment_class[0]->classes[0]->classlevel->yearLevels[0]->levels[0]->id);
            $newyearLevel = YearLevel::checkRelation($newyearType->id, $request->level);
            $oldClassLevel = ClassLevel::checkRelation($segment->Segment_class[0]->classes[0]->id ,$oldyearLevel->id);
            $newClassLevel = ClassLevel::checkRelation($segment->Segment_class[0]->classes[0]->id,$newyearLevel->id);
            $oldsegmentClass = SegmentClass::checkRelation($oldClassLevel->id, $segment->id);
            $oldsegmentClass->delete();
            SegmentClass::checkRelation($newClassLevel->id, $segment->id);
        }
        return HelperController::api_response_format(200, $segment);
    }
}
