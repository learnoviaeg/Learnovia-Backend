<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\AcademicType;
use App\Level;
use App\Segment;
use App\Enroll;
use Auth;
use Carbon\Carbon;
use App\Exports\TypesExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use App\Repositories\ChainRepositoryInterface;

class TypesController extends Controller
{
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
    public function index(Request $request,$status=null)
    {
        $request->validate([
            'search' => 'nullable',
            'filter' => 'in:all,export' //all without enroll  //export for exporting
        ]);

        if($request->user()->can('site/show-all-courses'))
        {
            $types = AcademicType::whereNull('deleted_at');
            return HelperController::api_response_format(201, $types->paginate(HelperController::GetPaginate($request)), __('messages.type.list'));
        }
        
        $enrolls = $this->chain->getEnrollsByManyChain($request);
        $types=AcademicType::whereIn('id',$enrolls->pluck('type'));    

        if($request->filled('search'))
            $years = $years->where('name', 'LIKE' , "%$request->search%"); 

        if($request->filter == 'export')
        {
            $types = $types->get();
            $filename = uniqid();
            $file = Excel::store(new TypesExport($types), 'Type'.$filename.'.xls','public');
            $file = url(Storage::url('Type'.$filename.'.xls'));

            return HelperController::api_response_format(201,$file, __('messages.success.link_to_file'));
        }

        return HelperController::api_response_format(200, $types->paginate(HelperController::GetPaginate($request)),__('messages.type.list'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'segment_no' => 'required'
        ]);

        AcademicType::firstOrCreate([
            'name' => $request->name,
            'segment_no' => $request->segment_no
        ]);

        return HelperController::api_response_format(201, AcademicType::paginate(HelperController::GetPaginate($request)), __('messages.type.add'));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $type = AcademicType::where('id', $id)->first();
        return HelperController::api_response_format(201, $type);
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
        $req->validate([
            'segment_no' => 'integer',
        ]);

        $AC = AcademicType::Find($id);
        $AC->update($req->all());
        $AC->save();

        return HelperController::api_response_format(200, AcademicType::paginate(HelperController::GetPaginate($req),__('messages.type.update')));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id, Request $request)
    {
        $segment= Segment::where('academic_type_id',$id)->get();
        $level= Level::where('academic_type_id',$id)->get();
        if((count($segment) > 0 || count($level) > 0))
            return HelperController::api_response_format(404, [], __('messages.error.cannot_delete'));
        
        AcademicType::whereId($id)->first()->delete();

        return HelperController::api_response_format(201, AcademicType::paginate(HelperController::GetPaginate($request)), __('messages.type.delete'));
    }
}
