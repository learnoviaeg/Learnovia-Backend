<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\grade;

class GradeController extends Controller
{
    public function create(Request $request)
    {
        $request->validate([
            'grade_category' => 'required|exists:grade_categories,id',
            'grademin'=> 'required|integer',
            'grademax' => 'required|integer',
            'calculation' => 'required|string',
            'item_no' => 'nullable|integer',
            'scale_id' => 'required|exists:scales,id',
            'grade_pass' => 'required|integer',
            'multifactor' => 'nullable|numeric|between:0,99.99',
            'plusfactor' => 'nullable|numeric|between:0,99.99',
            'aggregationcoef' => 'nullable|numeric|between:0,99.99',
            'aggregationcoef2' => 'nullable|numeric|between:0,99.99',
        ]);

        $data=[
        'grade_category' => $request->grade_category ,
        'grademin'=> $request->grademin,
        'grademax' =>$request->grademax,
        'calculation' =>$request->calculation ,
        'item_no' => $request->item_no,
        'scale_id' =>$request->scale_id ,
        'grade_pass' =>$request->grade_pass,
        'aggregationcoef' =>$request->aggregationcoef,
        'aggregationcoef2' =>$request->aggregationcoef2
        ];
        if(isset($request->multifactor)) {
            $data['multifactor']=$request->multifactor;
        }
        if(isset($request->plusfactor)) {
            $data['plusfactor']=$request->plusfactor;
        }

        $grade=grade::create($data);

        return HelperController::api_response_format(201,$grade,'Grade Created Successfully');

    }



    public function update(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:grades,id',
        ]);

        $grade =grade::find($request->id);

        $request->validate([
            'grade_category' => 'required|exists:grade_categories,id',
            'grademin'=> 'required|integer',
            'grademax' => 'required|integer',
            'calculation' => 'required|string',
            'item_no' => 'nullable|integer',
            'scale_id' => 'required|exists:scales,id',
            'grade_pass' => 'required|integer',
            'multifactor' => 'nullable|numeric|between:0,99.99',
            'plusfactor' => 'nullable|numeric|between:0,99.99',
            'aggregationcoef' => 'nullable|numeric|between:0,99.99',
            'aggregationcoef2' => 'nullable|numeric|between:0,99.99',
        ]);

        $data=[
            'grade_category' => $request->grade_category ,
            'grademin'=> $request->grademin,
            'grademax' =>$request->grademax,
            'calculation' =>$request->calculation ,
            'item_no' => $request->item_no,
            'scale_id' =>$request->scale_id ,
            'grade_pass' =>$request->grade_pass,
            'aggregationcoef' =>$request->aggregationcoef,
            'aggregationcoef2' =>$request->aggregationcoef2
            ];
            if(isset($request->multifactor)) {
                $data['multifactor']=$request->multifactor;
            }
            if(isset($request->plusfactor)) {
                $data['plusfactor']=$request->plusfactor;
            }

            $update=$grade->update($data);

        return HelperController::api_response_format(201, $grade, 'Grade Updated Successfully');

    }



    public function delete(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:grades,id',
        ]);

        $grade = grade::find($request->id);
        $grade->delete();

        return HelperController::api_response_format(201, null, 'Grade Deleted Successfully');

    }


    public function list()
    {
        $grade = grade::all();
        return HelperController::api_response_format(200, $grade);
    }
}
