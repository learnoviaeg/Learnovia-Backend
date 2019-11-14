<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\GradeItems;
use App\ItemType;
use DB;

class GradeItemController extends Controller
{
    /**
     * create grade item 
     * 
     * @param  [int] grade_category, grademin, grademax, item_no, scale_id, aggregationcoef, grade_pass, multifactor,
     *              plusfactor, aggregationcoef2, item_type
     * @param  [boolean] hidden
     * @param  [string] calculation, item_Entity
     * @return [objects] and [message] Grade Created Successfully
    */
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
            'item_type'=> 'required|exists:item_types,id',
            'item_Entity' => 'required',
            'hidden' => 'nullable|boolean'
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
        'aggregationcoef2' =>$request->aggregationcoef2,
        'item_type' => $request->item_type,
        'item_Entity' => $request->item_Entity
        ];
        if(isset($request->multifactor)) {
            $data['multifactor']=$request->multifactor;
        }
        if(isset($request->plusfactor)) {
            $data['plusfactor']=$request->plusfactor;
        }
        if(isset($request->hidden)) {
            $data['hidden']=$request->hidden;
        }

        $grade=GradeItems::create($data);

        return HelperController::api_response_format(201,$grade,'Grade Created Successfully');

    }

    /**
     * update grade item 
     * 
     * @param  [int] id, grade_category, grademin, grademax, item_no, scale_id, aggregationcoef, grade_pass, multifactor,
     *              plusfactor, aggregationcoef2, item_type
     * @param  [boolean] hidden
     * @param  [string] calculation, item_Entity
     * @param  [boolean] hidden
     * @return [objects] and [message] Grade updated Successfully
    */
    public function update(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:grade_items,id',
        ]);

        $grade =GradeItems::find($request->id);
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
            'item_type'=> 'required|exists:item_types,id',
            'item_Entity' => 'required',
            'hidden' => 'nullable|integer'
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
            'aggregationcoef2' =>$request->aggregationcoef2,
            'item_type' => $request->item_type,
            'item_Entity' => $request->item_Entity
            ];
            if(isset($request->multifactor)) {
                $data['multifactor']=$request->multifactor;
            }
            if(isset($request->plusfactor)) {
                $data['plusfactor']=$request->plusfactor;
            }
            if(isset($request->hidden)) {
                $data['hidden']=$request->hidden;
            }

            $update=$grade->update($data);


        return HelperController::api_response_format(200, $grade, 'Grade Updated Successfully');

    }

    /**
     * delete grade item 
     * 
     * @param  [int] id
     * @return [objects] and [message] Grade deleted Successfully
    */
    public function delete(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:grade_items,id',
        ]);

        $grade = GradeItems::find($request->id);
        $grade->delete();

        return HelperController::api_response_format(201, null, 'Grade Deleted Successfully');

    }

    /**
     * list/get grade item 
     * 
     * @return [objects] all grade items with Grade category and item type and scale
    */
    public function list()
    {
        $grade = GradeItems::with(['GradeCategory' , 'ItemType' , 'scale'])->get();
        return HelperController::api_response_format(200, $grade);
    }

    /**
     * move  grade item to new category
     * 
     * @param  [int] id, newcategory
     * @return [objects] and [message] Grade item Category is moved successfully
    */
    public function Move_Category(Request $request){
        $request->validate([
            'id' => 'required|exists:grade_items,id',
            'newcategory'=>'required|exists:grade_categories,id',
        ]);
        $GardeCategory=GradeItems::find($request->id);
        $GardeCategory->update([
            'grade_category' => $request->newcategory,
        ]);
        return HelperController::api_response_format(200, $GardeCategory,'Grade item Category is moved successfully');

    }
}
