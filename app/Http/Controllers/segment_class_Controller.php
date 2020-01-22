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
use App\AcademicType;
use App\AcademicYear;

class segment_class_Controller extends Controller

{

    /**
     * @Description: Get all Classes with its Segments
     * @param: no take parameters
     * @return : response of all Classes with its Segments
     *
     */
    public function List_Classes_with_segments(Request $request)
    {
        $request->validate([
            'year' => 'required|exists:academic_years,id',
            'type' => 'required|exists:academic_types,id',
            'level' => 'required|exists:levels,id',
            'class' => 'required|exists:classes,id',
        ]);
        if ($request->id == null) {
            $yeartype = AcademicYearType::checkRelation($request->year, $request->type);
            $yearlevel = YearLevel::checkRelation($yeartype->id, $request->level);
            $classLevel = ClassLevel::checkRelation($request->class, $yearlevel->id);
            $segments = collect([]);
            foreach ($classLevel->segmentClass as $segmentClass) {
                $segments[] = $segmentClass->segments[0];
            }
            return HelperController::api_response_format(200, $segments->paginate(HelperController::GetPaginate($request)));
        } else {
            $request->validate([
                'id' => 'exists:academic_years,id',
            ]);
            $yeartype = AcademicYearType::checkRelation($request->year, $request->type);
            $yearlevel = YearLevel::checkRelation($yeartype->id, $request->level);
            $classLevel = ClassLevel::checkRelation($request->class, $yearlevel->id);
            $segments = [];
            foreach ($classLevel->segmentClass as $segmentClass) {
                $segments[] = $segmentClass->segments[0];
            }
            $segmentscoll = collect($segments);
            $allsegments = $segmentscoll->where('id', $request->id);
            return HelperController::api_response_format(200, $allsegments->paginate(HelperController::GetPaginate($request)));
        }
    }

    public function get(Request $request)
    {
        if ($request->id == null) {
            $request->validate([
                'search' => 'nullable'
            ]);
            if($request->filled('search'))
            {
                $segments = Segment::where('name', 'LIKE' , "%$request->search%")->get()
                ->paginate(HelperController::GetPaginate($request));
                return HelperController::api_response_format(202, $segments);   
            }
            $segments = Segment::paginate(HelperController::GetPaginate($request));
            return HelperController::api_response_format(200, $segments);
        } else {
            $class = Segment::find($request->id);
            return HelperController::api_response_format(200, $class);
        }
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

        $yeartype = AcademicYearType::checkRelation($req->year, $req->type);
        $yearlevel = YearLevel::checkRelation($yeartype->id, $req->level);
        $classLevel = ClassLevel::checkRelation($req->class, $yearlevel->id);
        $type = AcademicType::find($req->type);
        $count = SegmentClass::whereClass_level_id($classLevel->id)->count();
        if ($count >= $type->segment_no) {
            return HelperController::api_response_format(200, null, 'This class has its all segments before');
        }
        $segment = Segment::firstOrCreate([
            'name' => $req->name,
            'academic_type_id'=>$req->type
        ]);

        SegmentClass::firstOrCreate([
            'class_level_id' => $classLevel->id,
            'segment_id' => $segment->id,
        ]);

        if ($segment) {
            return HelperController::api_response_format(200, Segment::get()->paginate(HelperController::GetPaginate($req)), 'Type insertion sucess');
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
            return HelperController::api_response_format(200, Segment::get()->paginate(HelperController::GetPaginate($req)), 'Segment Deleted Successfully');
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
    public function Assign_to_anther_Class(Request $request)
    {
        $rules =[
            'year' => 'required|array',
            'year.*' => 'exists:academic_years,id',
            'type' => 'required|array',
            'type.*' => 'required|exists:academic_types,id',
            'level'=> 'required|array',
            'level.*' => 'required|exists:levels,id',
            'class'=> 'required|array',
            'class.*' => 'required|exists:classes,id',
            'segment' => 'exists:segments,id',
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails())
            return ['result' => false, 'value' => $validator->errors()];

        $count=0;
        if( (count($request->type) == count($request->level)) && (count($request->level) == count($request->class)))
        {
            while(isset($request->class[$count]))
            {
                $year = AcademicYear::Get_current()->id;
                if (isset($request->year[$count])) {
                    $year = $request->year[$count];
                }

                $academic_year_type = AcademicYearType::checkRelation($year, $request->type[$count]);
                $year_level = YearLevel::checkRelation($academic_year_type->id, $request->level[$count]);
                $class_level = ClassLevel::checkRelation($request->class[$count], $year_level->id);

                if (isset($request->segment[$count])) {
                    $segment = $request->segment[$count];
                }
                else
                {
                    $segment = Segment::Get_current($request->type[$count]);
                    if(!isset($segment))
                        return HelperController::api_response_format(201, 'there is no current segment');
                    else
                    $segment =$segment->id;
                }
                SegmentClass::checkRelation($class_level->id,$segment);
                $count++;
            }
        }
        else
        {
            return HelperController::api_response_format(201, 'Please Enter Equal number of array');
        }

        return HelperController::api_response_format(201, 'Segment Assigned Successfully');
    }
     /**
     *
     * @Description :update a segment
     * @param : id and name are required parameters.
     * @return : string message which indicates if segment set to be current or not.
     */
    public function update(Request $request)
    {
        $valid = Validator::make($request->all(), [
            'id' => 'required|exists:segments,id',
            'name' => 'required',
           
        ]);

        if ($valid->fails())
            return HelperController::api_response_format(400, $valid->errors());

        $segment = Segment::find($request->id);
        $segment->name = $request->name;
        $segment->save();
        return HelperController::api_response_format(200, $segment->paginate(HelperController::GetPaginate($request)));
    }
     /**
     *
     * @Description :set a segment to be current
     * @param : segment_id and type_id are required parameters.
     * @return : string message which indicates if segment set to be current or not.
     */
    public function setCurrent_segmant(Request $request)
    {
        $request->validate([
            'segment_id' => 'required|exists:segments,id',
            'type_id' => 'required|exists:academic_types,id'
        ]);

        $segment = Segment::where('id', $request->segment_id)->where('academic_type_id', $request->type_id)->first();
        if(isset($segment)) {
            $segment->update(['current' => 1]);

            Segment::where('id', '!=', $request->segment_id)->where('academic_type_id', $request->type_id)
                ->update(['current' => 0]);
            return HelperController::api_response_format(200, [], ' this Segment is  set to be current ');
        }
        else{
            return HelperController::api_response_format(200, [], ' this Segment invalid');

        }
    }
}
