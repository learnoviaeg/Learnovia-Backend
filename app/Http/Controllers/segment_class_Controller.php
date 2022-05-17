<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Storage;

use App\AcademicYearType;
use App\ClassLevel;
use App\YearLevel;
use Illuminate\Http\Request;
use App\Events\MassLogsEvent;
use App\AcademicType;
use App\AcademicYear;
use Validator;
use App\SegmentClass;
use App\CourseSegment;
use App\Segment;
use App\Http\Resources\Segment_class_resource;
use App\User;
use App\Level;
use App\Classes;
use Auth;
use Carbon\Carbon;
use App\Exports\SegmentsExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Enroll;

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
                if(isset($segmentClass->segments[0]))
                    $segments[] = $segmentClass->segments[0]->id;
            }
            $segments = Segment::with(['academicType.yearType.academicyear','Segment_class.yearLevels.yearType'])->whereIn('id',$segments);
            $all_segments=collect([]);
            $segments =$segments->get();
            foreach($segments as $segment){
                $academic_year_id = $segment->Segment_class->pluck('yearLevels.*.yearType.*.academic_year_id')->collapse();
                $segment['academicYear']= AcademicYear::whereIn('id',$academic_year_id)->pluck('name');
                $academic_type_id = $segment->Segment_class->pluck('yearLevels.*.yearType.*.academic_type_id')->collapse();
                $segment['academicType']= AcademicType::whereIn('id',$academic_type_id)->pluck('name');
                if(isset($segment->segment_class[0]->class_id)){
                    $class_id = $segment->segment_class[0]->class_id;
                    $segment['class']=Classes::where('id',$class_id)->pluck('name')->first();
                }
                $level_id = $segment->Segment_class->pluck('yearLevels.*.level_id')->collapse();
                $segment['level'] = Level::whereIn('id',$level_id)->pluck('name');
                unset($segment->Segment_class);
                $all_segments->push($segment);
            }

            return HelperController::api_response_format(200, $all_segments->paginate(HelperController::GetPaginate($request)));
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

    public function get(Request $request ,$call = 0)
    {
        if ($request->id == null) {
            $request->validate([
                'search' => 'nullable',
                'years' => 'array',
                'years.*' => 'exists:academic_years,id',
                'types' => 'array',
                'types.*' => 'exists:academic_types,id',
                'levels' => 'array',
                'levels.*' => 'exists:levels,id',
                'classes' => 'array',
                'classes.*' => 'exists:classes,id',
            ]);
            if($request->filled('id')) {
                $segment = Segment::find($request->id);
                return HelperController::api_response_format(200, $segment->paginate(HelperController::GetPaginate($request)));
            }

            $segments = Segment::whereNull('deleted_at')
            ->where('name', 'LIKE' , "%$request->search%")
            ->with(['academicType','academicYear'])
            ->whereHas('academicType' , function($q)use ($request)
            { 
                if ($request->has('types'))
                    $q->whereIn('academic_type_id',$request->types);
            })
            ->whereHas('academicYear', function($q)use ($request)
            { 
                if ($request->has('years')) 
                    $q->whereIn('academic_year_id',$request->years);
            });
            
            // ->whereHas('Segment_class.classes.classlevel.yearLevels.yearType' , function($q)use ($request)
            // { 
            //     if ($request->has('years'))
            //         $q->whereIn('academic_year_id',$request->years);
            //     if ($request->has('types'))
            //         $q->whereIn('academic_type_id',$request->types);
            // })->get();
            // $segments = Segment::with(['academicType','academicYear']);
            $all_segments=collect([]);
           
            if($call == 1){
                $segmentIds = $segments->pluck('id');
                return $segmentIds;
            }
            // foreach($segmentt as $segment){
            //     $academic_year_id = $segment->Segment_class->pluck('yearLevels.*.yearType.*.academic_year_id')->collapse();
            //     $segment['academicYear']= AcademicYear::whereIn('id',$academic_year_id)->pluck('name');
            //     $academic_type_id = $segment->Segment_class->pluck('yearLevels.*.yearType.*.academic_type_id')->collapse();
            //     $segment['academicType']= AcademicType::whereIn('id',$academic_type_id)->pluck('name');
            //     if(isset($segment->segment_class/*->class_id*/)){
            //         $class_id = $segment->segment_class->pluck('class_id');
            //         $segment['class']=Classes::whereIn('id',$class_id)->pluck('name');
            //     }
            //     $level_id = $segment->Segment_class->pluck('yearLevels.*.level_id')->collapse();
            //     $segment['level'] = Level::whereIn('id',$level_id)->pluck('name');
            //     unset($segment->segment_class);
            //     $all_segments->push($segment);
            // }
            
            if($request->returnmsg == 'delete')
                return HelperController::api_response_format(200,  $segments->paginate(HelperController::GetPaginate($request)),__('messages.segment.delete'));
            if($request->returnmsg == 'add')
                return HelperController::api_response_format(200,  $segments->paginate(HelperController::GetPaginate($request)),__('messages.segment.add'));
            if($request->returnmsg == 'update')
                return HelperController::api_response_format(200,  $segments->paginate(HelperController::GetPaginate($request)),__('messages.segment.update'));
            else
                return HelperController::api_response_format(200,  $segments->paginate(HelperController::GetPaginate($request)));
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
            'name'      => 'required',
            'year'      => 'required|exists:academic_years,id',
            'type'      => 'required|exists:academic_types,id',
            'start_date'      => 'required|date|before_or_equal:end_date',
            'end_date'      => 'required|date',
            // 'levels'    => 'required|array',
            // 'levels.*.id'  => 'required|exists:levels,id',
            // 'levels.*.classes'   => 'required|array',
            // 'levels.*.classes.*'   => 'required|exists:classes,id',
        ]);

        if ($valid->fails()) 
            return HelperController::api_response_format(400, $valid->errors());
        
        $type = AcademicType::find($req->type);
        $current_segment_created = Segment::where('academic_type_id',$req->type)->count();
        if($current_segment_created >= $type->segment_no)
            return HelperController::api_response_format(200, null,__('messages.segment.type_invalid'));
        
        $segment = Segment::firstOrCreate([
            'name' => $req->name,
            'academic_type_id'=>$req->type,
            'academic_year_id'=>$req->year,
            'start_date' => $req->start_date,
            'end_date' => $req->end_date
        ]);
        // $yeartype = AcademicYearType::checkRelation($req->year, $req->type);
        // foreach($req->levels as $level){
        //     $yearlevel = YearLevel::checkRelation($yeartype->id, $level['id']);
        //     foreach($level['classes'] as $class){
        //         $classLevel = ClassLevel::checkRelation($class, $yearlevel->id);
        //         $count = SegmentClass::whereClass_level_id($classLevel->id)->count();
        //         if ($count >= $type->segment_no) {
        //             continue;
        //         }
        //         SegmentClass::create([
        //             'class_level_id' => $classLevel->id,
        //             'segment_id' => $segment->id,
        //         ]);
        //     }
        // }
        return HelperController::api_response_format(200, Segment::get()->paginate(HelperController::GetPaginate($req)), __('messages.segment.add'));
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

        $course_segments = CourseSegment::whereIn('segment_class_id',SegmentClass::where('segment_id',$req->id)->pluck('id'))->get();
        if (count($course_segments) > 0) 
             return HelperController::api_response_format(404, [] , __('messages.error.cannot_delete'));
        
        Segment::whereId($req->id)->first()->delete();
        SegmentClass::where('segment_id',$req->id)->first()->delete();

        //for log event
        // $logsbefore=Enroll::where('segment',$req->id)->get();
        // $returnValue=Enroll::where('segment',$req->id)->update(['segment' => null]);
        // if($returnValue > 0)
        //     event(new MassLogsEvent($logsbefore,'updated'));

        $req['id'] = null;
        $req['returnmsg'] = 'delete';
        $print = self::get($req);
        return $print;
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
                        return HelperController::api_response_format(201, __('messages.error.no_active_segment'));
                    else
                    $segment =$segment->id;
                }
                SegmentClass::checkRelation($class_level->id,$segment);
                $count++;
            }
        }
        else
        {
            return HelperController::api_response_format(201, __('messages.error.data_invalid'));
        }

        return HelperController::api_response_format(201, __('messages.segment.add'));
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
            'year' => 'exists:academic_years,id',
            'type' => 'exists:academic_types,id|required_with:year',
            'level' => 'exists:levels,id|required_with:year',
        ]);

        if ($valid->fails())
            return HelperController::api_response_format(400, $valid->errors());

        $segment = Segment::find($request->id);
        $segment->name = $request->name;
        $segment->save();

        if ($request->filled('year'))
        {
            $year_type= AcademicYearType::where('academic_year_id',$request->year)->where('academic_type_id',$request->type)->first();
            $year_level=YearLevel::where('level_id',$request->level)->where('academic_year_type_id',$year_type->id)->first();
            $class_level=ClassLevel::where('class_id',$request->class)->where('year_level_id', $year_level->id)->first();
            SegmentClass::where('segment_id',$request->id)->update(['class_level_id' => $class_level->id]);
        }

        $request['id'] = null;
        $request['returnmsg'] = 'update';
        $print = self::get($request);
        return $print;
        // return HelperController::api_response_format(200, $segment->paginate(HelperController::GetPaginate($request)),'Segment edited successfully');
    }
     /**
     *
     * @Description :set a segment to be current
     * @param : id and type_id are required parameters.
     * @return : string message which indicates if segment set to be current or not.
     */
    public function setCurrent_segmant(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:segments,id',
            'type_id' => 'required|exists:academic_types,id'
        ]);

        $segment = Segment::where('id', $request->id)->where('academic_type_id', $request->type_id)->first();
        if(isset($segment)) {
            if($segment->current == 1)
                $segment->update(['current' => 0]);
            else
                $segment->update(['current' => 1]);

            Segment::where('id', '!=', $request->id)->where('academic_type_id', $request->type_id)
                ->update(['current' => 0]);
            return HelperController::api_response_format(200, [], __('messages.segment.activate'));
        }
        else{
            return HelperController::api_response_format(200, [], __('messages.error.not_found'));

        }
    }

    public function GetMySegments(Request $request)
    {
        $result=array();
        $lev=array();
        $user = User::whereId(Auth::id())->with(['enroll.courseSegment' => function($query){
            //validate that course in my current course start < now && now < end
            $query->where('end_date', '>', Carbon::now())->where('start_date' , '<' , Carbon::now());
        },'enroll.courseSegment.segmentClasses.classLevel' => function($query) use ($request){
            if ($request->filled('class'))
                $query->where('class_id', $request->class);
        },'enroll.courseSegment.segmentClasses.classLevel.yearLevels' => function($query) use ($request){
            if ($request->filled('level'))
                $query->where('level_id', $request->level);
        },'enroll.courseSegment.segmentClasses.classLevel.yearLevels.yearType' => function($query) use ($request){
            if ($request->filled('type'))
                $query->where('academic_type_id', $request->type);   
            if ($request->filled('year'))
                $query->where('academic_year_id', $request->year);          
        }])->first();

        if($request->user()->can('site/show-all-courses'))
        {
            $year = AcademicYear::where('current',1)->first();
            if($request->filled('year'))
                $year = AcademicYear::where('id',$request->year)->first();

            if(isset($year))
                $year_type = $year->YearType->pluck('id');

            if ($request->filled('type')){
                $year_type = AcademicYearType::where('academic_type_id',$request->type)->pluck('id');
                $segments = Segment::where('academic_type_id',$request->type)->pluck('id');
            }
            // return $year_type;   
            if(!isset($year_type))
                return HelperController::api_response_format(200,null, __('messages.error.no_active_year'));
                
            $year_levels = YearLevel::whereIn('academic_year_type_id', $year_type)->pluck('id');
            // return
            if(isset($year_levels))
                $classes = ClassLevel::whereIn('year_level_id',$year_levels)->pluck('id');
            
            if(!isset($segments))
                $segments=SegmentClass::whereIn('class_level_id',$classes)->pluck('segment_id');

            if(isset($segments))
                $segments = segment::whereIn('id',$segments)->get();
            if(count($segments) == 0)
                return HelperController::api_response_format(201,null, __('messages.error.no_available_data'));

            return HelperController::api_response_format(200,$segments, __('messages.segment.list'));
        }

        foreach($user->enroll as $enrolls){
            if(isset($enrolls->courseSegment) && isset($enrolls->courseSegment->segmentClasses)){
                foreach($enrolls->courseSegment->segmentClasses as $segmetClas)
                    foreach($segmetClas->classLevel as $clas)
                        foreach($clas->yearLevels as $level)
                            foreach($level->yearType as $typ)
                                if(isset($typ)){
                                    if(!in_array($segmetClas->segment_id, $result))
                                    {
                                        $result[]=$segmetClas->segment_id;
                                        $segmentt[]=Segment::find($segmetClas->segment_id);
                                    }
                                }
            }
        } 
        if(isset($segmentt) && count($segmentt) > 0)
            return HelperController::api_response_format(201,$segmentt, __('messages.segment.list'));
        
        return HelperController::api_response_format(201,null, __('messages.error.no_available_data'));
    }
    public function export(Request $request)
    {
        $segmentsIDs = self::get($request,1);
        $filename = uniqid();
        $file = Excel::store(new SegmentsExport($segmentsIDs), 'Segment'.$filename.'.xlsx','public');
        $file = url(Storage::url('Segment'.$filename.'.xlsx'));
        return HelperController::api_response_format(201,$file, __('messages.success.link_to_file'));
        
    }
}
