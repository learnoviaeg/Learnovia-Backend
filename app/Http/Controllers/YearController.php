<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\AcademicYear;

class YearController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $request->validate([
            'search' => 'nullable',
            'id' => 'exists:academic_years,id',
            'all' => 'boolean',
        ]);
        
        $years=AcademicYear::whereNull('deleted_at');
        if($request->filled('search'))
            $years = AcademicYear::where('name', 'LIKE' , "%$request->search%"); 

        if ($request->filled('id'))
            $years = AcademicYear::where('id', $request->id)->first();
        
        $years =$years->paginate(HelperController::GetPaginate($request));
        return HelperController::api_response_format(202, $years);
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
        $year = AcademicYear::create([
            'name' => $request->name
        ]);
        $years = AcademicYear::paginate(HelperController::GetPaginate($request));
        return HelperController::api_response_format(201, $years, __('messages.year.add'));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
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
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
