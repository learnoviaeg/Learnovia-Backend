<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use App\Repositories\ChainRepositoryInterface;
use App\Segment;
use App\Course;
use App\AcademicType;

class SegmentsController extends Controller
{
        /**
     * ChainController constructor.
     *
     * @param ChainRepositoryInterface $post
     */
    public function __construct(ChainRepositoryInterface $chain)
    {
        $this->chain = $chain;
        $this->middleware('auth');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
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

        $segments=Segment::with('academicType','academicYear')->whereNull('deleted_at');

        if($request->filled('search'))
            $segments->where('name', 'LIKE' , "%$request->search%");

        if($request->user()->can('site/show-all-courses'))
        {
            if(isset($request->types))
                $segments->whereIn('academic_type_id',$request->types)->with('academicType','academicYear')->whereNull('deleted_at');

            return HelperController::api_response_format(201, $segments->paginate(HelperController::GetPaginate($request)), __('messages.segment.list'));
        }

        $enrolls = $this->chain->getEnrollsByManyChain($request);
        $segments->whereIn('id',$enrolls->pluck('segment'));    

        if($request->filter == 'export')
        {
            $segmentsIDs = $segments->get();
            $filename = uniqid();
            $file = Excel::store(new SegmentsExport($segmentsIDs), 'Segment'.$filename.'.xls','public');
            $file = url(Storage::url('Segment'.$filename.'.xls'));
            return HelperController::api_response_format(201,$file, __('messages.success.link_to_file'));
        }

        return HelperController::api_response_format(200, null, __('messages.segment.list'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $req)
    {
        $req->validate([
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
        // return HelperController::api_response_format(200, Segment::with('academicType','academicYear')->get()->paginate(HelperController::GetPaginate($req)), __('messages.segment.add'));
        return HelperController::api_response_format(200, null, __('messages.segment.add'));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $segment = Segment::where('id', $id)->first();
        return HelperController::api_response_format(201, $segment);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required',
            'year' => 'exists:academic_years,id',
            'type' => 'exists:academic_types,id|required_with:year',
        ]);

        $segment = Segment::find($id);
        $segment->name = $request->name;
        $segment->start_date = $request->start_date;
        $segment->end_date = $request->end_date;
        $segment->save();

        if ($request->filled('year') && $request->filled('type'))
        {
            Segment::whereId($id)->update([
                'academic_year_id' => $request->year,
                'academic_type_id' => $request->type,
            ]);
        }
        return HelperController::api_response_format(200, null, __('messages.segment.update'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $course = Course::whereIn('segment_id',$id)->get();
        if (count($course) > 0) 
            return HelperController::api_response_format(404, [] , __('messages.error.cannot_delete'));
        
        Segment::whereId($id)->first()->delete();

        return HelperController::api_response_format(200, null, __('messages.segment.delete'));
    }
}
