<?php

namespace App\Http\Controllers;

use App\CourseSegment;
use App\Enroll;
use App\User;
use Illuminate\Http\Request;
use App\UserGrade;
use stdClass;

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
            'grade_item_id' => 'required|exists:grade_items,id',
            'user_id' => 'required|exists:users,id',
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

        $data = [
            'grade_item_id' => $request->grade_item_id,
            'user_id' => $request->user_id,
            'raw_grade' => $request->raw_grade,
            'raw_grade_max' => $request->raw_grade_max,
            'raw_scale_id' => $request->raw_scale_id,
            'final_grade' => $request->final_grade,
            'feedback' => $request->feedback,
            'letter_id' => $request->letter_id
        ];
        if (isset($request->hidden)) {
            $data['hidden'] = $request->hidden;
        }
        if (isset($request->locked)) {
            $data['locked'] = $request->locked;
        }
        if (isset($request->raw_grade_min)) {
            $data['raw_grade_min'] = $request->raw_grade_min;
        }

        $grade = UserGrade::create($data);

        return HelperController::api_response_format(201, $grade, 'User Grade Created Successfully');
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

        $grade = UserGrade::find($request->id);

        $request->validate([
            'grade_item_id' => 'required|exists:grade_items,id',
            'user_id' => 'required|exists:users,id',
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

        $data = [
            'grade_item_id' => $request->grade_item_id,
            'user_id' => $request->user_id,
            'raw_grade' => $request->raw_grade,
            'raw_grade_max' => $request->raw_grade_max,
            'raw_scale_id' => $request->raw_scale_id,
            'final_grade' => $request->final_grade,
            'feedback' => $request->feedback,
            'letter_id' => $request->letter_id
        ];
        if (isset($request->hidden)) {
            $data['hidden'] = $request->hidden;
        }
        if (isset($request->locked)) {
            $data['locked'] = $request->locked;
        }
        if (isset($request->raw_grade_min)) {
            $data['raw_grade_min'] = $request->raw_grade_min;
        }

        $update = $grade->update($data);

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

    public function graderReport(Request $request)
    {
        $request->validate([
            'course' => 'required|exists:courses,id',
            'class'  => 'required|exists:classes,id'
        ]);
        $courseSegment = CourseSegment::GetWithClassAndCourse($request->class, $request->course);
        if($courseSegment == null)
            return HelperController::api_response_format(200, null , 'This Course not assigned to this class');
        $users = User::whereIn('id', Enroll::where('course_segment', $courseSegment->id)->where('role_id', 3)->pluck('id'))->get();
        $gradeCategories = $courseSegment->where('id', $courseSegment->id)->with('GradeCategory.GradeItems')->get()->pluck('GradeCategory')[0];
        $first = true;
        $grades = [];
        foreach ($users as $user) {
            $user->grades = collect();
            $i = 0 ;
            foreach ($gradeCategories as $category) {
                $grades[$i]['items'] = collect();
                $grades[$i]['name'] = $category->name;
                $user->grades[$category->name] = collect();
                $user->grades[$category->name]['total'] = 0;
                $user->grades[$category->name]['data'] = collect();
                foreach ($category->GradeItems as $item) {
                    $temp = UserGrade::where('user_id', $user->id)->where('grade_item_id', $item->id)->first();
                    if ($temp != null && $first) {
                        $user->grades[$category->name]['total'] = $temp->calculateGrade();
                        $first = false;
                        $temp->grade_items = null;
                    }
                    $usergrade = new stdClass();
                    $usergrade->name = $item->name;
                    $usergrade->final_grade = ' - ';
                    if ($temp != null) {
                        $usergrade->final_grade = $temp->final_grade;
                    }
                    $user->grades[$category->name]['data']->push($usergrade);
                    $grades[$i]['items']->push($item->name);
                }
                $user->grades->toArray();
                $grades[$i]['items']->push($category->name . ' Total');
                $first = true;
                $i++;
            }
        }
        return HelperController::api_response_format(200, ['schema' => $grades, 'users' => $users]);
    }
}
