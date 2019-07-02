<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\AcademicYear;
use App\AcademicYearType;
use App\Http\Resources\Academic_Year as Academic_YearResource;

class AcademicYearController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $year = AcademicYear::paginate(10); 
        return Academic_YearResource::collection($year);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $year = new AcademicYear;
        $year->name = $request->input('name');
        $year->save();
    }

    // public function AddAcdamiecYearType(Request $request)
    // {
    //     dd(AcademicYear::GetAllYearsInAcademic($request->year));
    //     if(AcademicYear::Validate($request->all()) == true){
    //         $acyear = AcademicYear::create([
    //             'name'=> $request->name,
    //         ]);
    //         AcademicYearType::create([
    //             'academic_year_id' => $acyear->id,
    //         ]);
    //         return 'Year Name' . $request->year;
    //     }
    //     return AcademicYear::Validate($request->all());
    // }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $year = AcademicYear::findOrFail($id);
        return new Academic_YearResource($year);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
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
        $year = AcademicYear::find($id);
        $year->update($request->all());
        return $year;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $year = AcademicYear::findOrFail($id);
        if($year->delete()){
        return new Academic_YearResource($year);
    }
}
}
