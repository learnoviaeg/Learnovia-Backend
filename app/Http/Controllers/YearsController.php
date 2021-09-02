<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\AcademicYear;
use App\Enroll;
use Carbon\Carbon;
use Auth;
use App\Exports\YearsExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use App\Repositories\ChainRepositoryInterface;

class YearsController extends Controller
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
    public function index(Request $request)
    {
        $request->validate([
            'search' => 'nullable',
            'filter' => 'in:all,export' //all without enroll  //export for exporting
        ]);

        $years=AcademicYear::whereNull('deleted_at');
        if($request->filled('search'))
            $years = $years->where('name', 'LIKE' , "%$request->search%"); 
        if($request->user()->can('site/show-all-courses'))
            return HelperController::api_response_format(201, $years->paginate(HelperController::GetPaginate($request)), __('messages.year.list'));

        $enrolls = $this->chain->getEnrollsByManyChain($request);
        $years->whereIn('id',$enrolls->pluck('year'));
        
        return HelperController::api_response_format(201, $years->paginate(HelperController::GetPaginate($request)), __('messages.year.list'));
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
            'name' => 'required'
        ]);

        $year = AcademicYear::firstOrCreate([
            'name' => $request->name
        ]);

        return HelperController::api_response_format(201, AcademicYear::paginate(HelperController::GetPaginate($request)), __('messages.year.add'));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $year = AcademicYear::where('id', $id)->first();
        return HelperController::api_response_format(201, $year);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id,$current=null)
    {
        $year = AcademicYear::find($id);
        if($current=='current')
        {
            if($year->current == 1)
                $year->update(['current' => 0]);
            
            else
                $year->update(['current' => 1]);

            AcademicYear::where('id', '!=', $request->id)->update(['current' => 0]);
        }

        if(isset($request->name))
            $year->name=$request->name;

        $year->save();

        return HelperController::api_response_format(201, AcademicYear::get()->paginate(HelperController::GetPaginate($request)), __('messages.year.update'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id,Request $request)
    {
        $check = AcademicType::where('academic_year_id',$id)->get();
        if (count($check) > 0) 
            return HelperController::api_response_format(200, [] , __('messages.error.cannot_delete'));
        $year=AcademicYear::find($id);
        $year->delete();
        return HelperController::api_response_format(200, AcademicYear::get()->paginate(HelperController::GetPaginate($request)), __('messages.year.delete'));            
    }
}
