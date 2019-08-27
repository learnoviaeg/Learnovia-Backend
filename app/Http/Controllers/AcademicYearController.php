<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\AcademicYear;
use App\AcademicYearType;
use App\Http\Resources\Academic_Year as Academic_YearResource;

class AcademicYearController extends Controller
{
    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
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
        return HelperController::api_response_format(201, $year, 'Year Created Successfully');
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */

    public function get(Request $request)
    {
        $request->validate([
            'id' => 'exists:academic_years,id',
            'all' => 'boolean',
        ]);
        if ($request->filled('id')) {
            $year=AcademicYear::where('id', $request->id)->with('AC_Type')->first();
        }
        if (!$request->filled('id')) {
            $year=AcademicYear::with('AC_Type')->get();
        } else {
            $year->paginate(HelperController::GetPaginate($request));
        }
        return HelperController::api_response_format(200, $year);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:academic_years,id',
            'name' => 'required'
        ]);
        $year = AcademicYear::whereId($request->id)->first();
        $year->update($request->all());
        return HelperController::api_response_format(200, $year);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:academic_years,id',
        ]);

        $year = AcademicYear::whereId($request->id)->first();
        if ($year->delete()) {
            return HelperController::api_response_format(200, $year);
        }
        return HelperController::api_response_format(404, [], 'Not Found');
    }
    public function setCurrent_year(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:academic_years,id',
        ]);

        $year = AcademicYear::find($request->id);
        $year->update(['current' => 1]);
        $all = AcademicYear::where('id', '!=', $request->id)
            ->update(['current' => 0]);
        return HelperController::api_response_format(200, $year, ' this year is  set to be current ');
    }
}
