<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\AcademicType;

class TypeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $request->validate([
            'year' => 'required|exists:academic_years,id',
            'dropdown' => 'boolean'
        ]);

        if($request->id != null)
        {
            $request->validate([
                'id' => 'exists:academic_types,id'
            ]);
            $types = AcademicType::where('id',$request->id)->first();
            return HelperController::api_response_format(200, $types);
        }
        else {
            // $cat = AcademicYear::whereId($request->year)->first()->AC_Type->pluck('id');
            // $types = AcademicType::with('yearType.academicyear')->whereIn('id',$cat);     
            $types = AcademicType::all();     
            if(isset($request->dropdown) && $request->dropdown == true)       
                return HelperController::api_response_format(200, $types->get());
            // else
            return HelperController::api_response_format(200, $types->paginate(HelperController::GetPaginate($request)));
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
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
