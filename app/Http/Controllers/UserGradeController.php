<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\UserGrade;

class UserGradeController extends Controller
{
    /**
     * create User grade
     * 
     * @param  [int] grade_item_id, user_id, raw_grade, raw_grade_max, raw_grade_min, raw_scale_id, final_grade, letter_id
     * @param  [boolean] hidden, locked
     * @param  [string] feedback
     * @return [object] and [string] User Grade Created Successfully
    */
   public function create(Request $request)
        {

            $request->validate([
                'user'=>'required|array',
                'user.*.id'=> 'required|exists:users,id',
                'user.*.grade_item_id' => 'required|array',
                'user.*.grade_item_id.*' => 'required|exists:grade_items,id',
                'user.*.raw_grade' => 'required|array',
                'user.*.raw_grade.*' => 'required|numeric|between:0,99.99',
                'user.*.raw_grade_max' => 'required|array',
                'user.*.raw_grade_max.*' => 'required|numeric|between:0,99.99',
                'user.*.raw_grade_min' => 'nullable|array',
                'user.*.raw_grade_min.*' => 'nullable|numeric|between:0,99.99',
                'user.*.raw_scale_id' => 'required|array',
                'user.*.raw_scale_id.*' => 'required|exists:scales,id',
                'user.*.final_grade' => 'required|array',
                'user.*.hidden' => 'nullable|array',
                'user.*.hidden.*' => 'nullable|boolean',
                'user.*.locked' => 'nullable|array',
                'user.*.locked.*' => 'nullable|boolean',
                'user.*.feedback' => 'required|array',
                'user.*.letter_id' => 'required|array',
                'user.*.letter_id.*' => 'required|exists:letters,id',
            ]);

            foreach ($request->user as $key => $user) {
                foreach($user['raw_grade'] as $keys => $rawgrade){
                    $data=[
                        'grade_item_id' => $request->user[$key]['grade_item_id'][$keys],
                        'user_id'=> $request->user[$key]['id'],
                        'raw_grade' => $request->user[$key]['raw_grade'][$keys],
                        'raw_grade_max' =>$request->user[$key]['raw_grade_max'][$keys],
                        'raw_scale_id' => $request->user[$key]['raw_scale_id'][$keys],
                        'final_grade' =>$request->user[$key]['final_grade'][$keys],
                        'feedback' => $request->user[$key]['feedback'][$keys],
                        'letter_id' => $request->user[$key]['letter_id'][$keys],
                    ];
                    if(isset($request->user[$key]['hidden'][$keys])) {
                        $data['hidden']=$request->user[$key]['hidden'][$keys];
                    }
                    if(isset($request->user[$key]['locked'][$keys])) {
                        $data['locked']=$request->user[$key]['locked'][$keys];
                    }
                    if(isset($request->user[$key]['raw_grade_min'][$keys])) {
                        $data['raw_grade_min']=$request->user[$key]['raw_grade_min'][$keys];
                    }
        
                    $grade=UserGrade::create($data);
                }
                
            }
            return HelperController::api_response_format(201,'Users Grades are Created Successfully');
    
        }

    /**
     * update User grade
     * 
     * @param  [int] id, grade_item_id, user_id, raw_grade, raw_grade_max, raw_grade_min, raw_scale_id, final_grade, 
     *              letter_id 
     * @param  [boolean] hidden, locked
     * @param  [string] feedback
     * @return [object] and [string] User Grade updated Successfully
    */
    public function update(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:user_grades,id',
        ]);

        $grade =UserGrade::find($request->id);

        $request->validate([
            'grade_item_id' => 'required|exists:grade_items,id',
            'user_id'=> 'required|exists:users,id',
            'raw_grade' => 'required|numeric|between:0,99.99',
            'raw_grade_max' => 'required|numeric|between:0,99.99',
            'raw_grade_min' => 'nullable|numeric|between:0,99.99',
            'raw_scale_id' => 'required|exists:scales,id',
            'final_grade' => 'required|numeric|between:0,99.99',
            'hidden' => 'nullable|boolean',
            'locked' => 'nullable|boolean',
            'feedback' => 'required|string',
            'letter_id' => 'required|exists:letters,id',
        ]);

        $data=[
            'grade_item_id' => $request->grade_item_id,
            'user_id'=> $request->user_id,
            'raw_grade' => $request->raw_grade,
            'raw_grade_max' =>$request->raw_grade_max,
            'raw_scale_id' => $request->raw_scale_id,
            'final_grade' =>$request->final_grade,
            'feedback' => $request->feedback,
            'letter_id' => $request->letter_id
        ];
        if(isset($request->hidden)) {
            $data['hidden']=$request->hidden;
        }
        if(isset($request->locked)) {
            $data['locked']=$request->locked;
        }
        if(isset($request->raw_grade_min)) {
            $data['raw_grade_min']=$request->raw_grade_min;
        }

        $update=$grade->update($data);

        return HelperController::api_response_format(200, $grade, 'User Grade Updated Successfully');
    }

    /**
     * delete User grade
     * 
     * @param  [int] id
     * @return [string] User Grade deleted Successfully
    */
    public function delete(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:user_grades,id',
        ]);

        $grade = UserGrade::find($request->id);
        $grade->delete();

        return HelperController::api_response_format(201, null, 'User Grade Deleted Successfully');
    }

    /**
     * list User grades
     * 
     * @return [objects] grades
    */
    public function list()
    {
        $grade = UserGrade::all();
        return HelperController::api_response_format(200, $grade);
    }

}
