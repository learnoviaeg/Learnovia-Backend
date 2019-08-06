<?php

namespace Modules\Assigments\Http\Controllers;

use App\attachment;
use App\Enroll;
use App\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use App\Http\Controllers\HelperController;
use Carbon\Carbon;
use Modules\Assigments\Entities\assignment;
use Modules\Assigments\Entities\UserAssigment;

class AssigmentsController extends Controller
{

    public function install_Assignment()
    {
        if (\Spatie\Permission\Models\Permission::whereName('assignment/add')->first() != null) {
            return \App\Http\Controllers\HelperController::api_response_format(400, null, 'This Component is installed before');
        }

        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'assignment/add']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'assignment/update']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'assignment/submit']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'assignment/grade']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'assignment/override']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'assignment/delete']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'assignment/get']);

        $role = \Spatie\Permission\Models\Role::find(1);
        $role->givePermissionTo('assignment/add');
        $role->givePermissionTo('assignment/update');
        $role->givePermissionTo('assignment/submit');
        $role->givePermissionTo('assignment/grade');
        $role->givePermissionTo('assignment/override');
        $role->givePermissionTo('assignment/delete');
        $role->givePermissionTo('assignment/get');

        return \App\Http\Controllers\HelperController::api_response_format(200, null, 'Component Installed Successfully');
    }

    //Create assignment
    public function createAssigment(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'is_graded' => 'required|boolean',
            'mark' => 'required|integer',
            'allow_attachment' => 'required|integer|min:0|max:3',
            'opening_date' => 'required|before:closing_date|after:' . Carbon::now(),
            'closing_date' => 'required',
            'visiable' => 'required|boolean',
            'type' => 'required|exists:academic_types,id',
            'level' => 'required|exists:levels,id',
            'class' => 'required|array',
            'class.*'=>'required|exists:classes,id',
            'segment' => 'exists:segments,id',
            'year' => 'exists:academic_years,id',
            'course' => 'required|exists:courses,id',
        ]);
        if (!isset($request->file) && !isset($request->content)) {
            return HelperController::api_response_format(400, $body = [], $message = 'please enter file or content');
        }
        $assigment = new assignment;
        if (isset($request->file)) {

            $request->validate([
                'file' => 'file|distinct|mimes:txt,pdf,docs,jpg',
            ]);
            if (isset($request->file_description)) {
                $description = $request->file_description;
            } else {
                $description = Null;
            }
            $assigment->attachment_id = attachment::upload_attachment($request->file, 'assigment', $description)->id;
        }
        if (isset($request->content)) {
            $assigment->content = $request->content;
        }
        $assigment->name = $request->name;
        $assigment->is_graded = $request->is_graded;
        $assigment->mark = $request->mark;
        $assigment->allow_attachment = $request->allow_attachment;
        $assigment->start_date = $request->opening_date;
        $assigment->due_date = $request->closing_date;
        $assigment->visiable = $request->visiable;
        $assigment->save();
        $year=null;
        if(isset($request->year))
        {
            $year=$request->year;
        }
        $segment=null;
        if(isset($request->segment))
        {
            $segment=$request->segment;
        }
        $course_segment=array();
        foreach ($request->class as $singleclass) {
            $coursreq=new Request([
                'type' => $request->type,
                'level' => $request->level,
                'class'=> $singleclass,
                'year' => $year,
                'segment'=>$segment,
                'course'=>$request->course
            ]);
            $course_segment[]=HelperController::Get_Course_segment_Course($coursreq)['value']->id;


        }

        $data = array("course_segment" => $course_segment, "assignments_id" => $assigment->id);
        $this->assignAsstoUsers($data);
        return HelperController::api_response_format(200, $body = $assigment, $message = 'assigment added');
    }
    /*


            update Assigment


*/
    public function ubdateAssigment(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:assignments,id',
            'name' => 'required|string',
            'is_graded' => 'required|boolean',
            'mark' => 'required|integer',
            'allow_attachment' => 'required|integer|min:0|max:3',
            'opening_date' => 'required|before:closing_date|after:' . Carbon::now(),
            'closing_date' => 'required',
            'visiable' => 'required|boolean',
            'file_description' => 'string',

        ]);
        if (!isset($request->file) && !isset($request->content)) {
            return HelperController::api_response_format(400, $body = [], $message = 'please enter file or content');
        }
        $assigment = assignment::find($request->id);
        if (isset($request->file)) {

            $request->validate([
                'file' => 'file|distinct|mimes:txt,pdf,docs,jpg',
            ]);
            if (isset($request->file_description)) {
                $description = $request->file_description;
            } else {
                $description = Null;
            }
            $assigment->attachment_id = attachment::upload_attachment($request->file, 'assigment', $description)->id;
        } else {
            $assigment->attachment_id = null;
        }
        if (isset($request->content)) {
            $assigment->content = $request->content;
        }
        else {
            $assigment->content = null;
        }
        $assigment->name = $request->name;
        $assigment->is_graded = $request->is_graded;
        $assigment->mark = $request->mark;
        $assigment->allow_attachment = $request->allow_attachment;
        $assigment->start_date = $request->start_date;
        $assigment->due_date = $request->due_date;
        $assigment->visiable = $request->visiable;
        $assigment->save();

        return HelperController::api_response_format(200, $body = $assigment, $message = 'assigment edited');
    }
    /*


            assign Assigment to users


*/

    public function assignAsstoUsers($request)
    {
        foreach($request['course_segment'] as $coursseg){
        $usersIDs = Enroll::where('course_segment', $coursseg)->pluck('user_id')->toarray();
        foreach ($usersIDs as $userId) {
            # code...
            $userassigment = new UserAssigment;
            $userassigment->user_id = $userId;
            $userassigment->assignment_id = $request['assignments_id'];
            $userassigment->status_id = 2;
            $userassigment->override = 0;
            $userassigment->save();
        }
    }
    }
    /*


            submit Assigment from user


*/


    public function submitAssigment(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:user_assigments,user_id',
            'assignment_id' => 'required|exists:user_assigments,assignment_id',
        ]);
        $assigment = assignment::where('id', $request->assignment_id)->first();

        /*

        0===================>content
        1===================>attached_file
        2===================>both
        3===================>can submit content or file

    */
        if ((($assigment->allow_attachment == 3)) && ((!isset($request->content)) && (!isset($request->file)))) {
            return HelperController::api_response_format(400, $body = [], $message = 'you must enter the content or the file');
        }
        if ((($assigment->allow_attachment == 0)) && ((!isset($request->content)) || (isset($request->file)))) {
            return HelperController::api_response_format(400, $body = [], $message = 'you must enter only the content');
        }

        if ((($assigment->allow_attachment == 1)) && ((isset($request->content)) || (!isset($request->file)))) {
            return HelperController::api_response_format(400, $body = [], $message = 'you must enter only the file');
        }
        if ((($assigment->allow_attachment == 2)) && ((!isset($request->content)) || (!isset($request->file)))) {
            return HelperController::api_response_format(400, $body = [], $message = 'you must enter both the content and the file');
        }
        $userassigment = UserAssigment::where('user_id', $request->user_id)->where('assignment_id', $request->assignment_id)->first();

        if (((($assigment->start_date >  Carbon::now()) || (Carbon::now() > $assigment->due_date)) && ($userassigment->override == 0)) || ($userassigment->status_id == 1) || ($assigment->visiable == 0)) {
            return HelperController::api_response_format(400, $body = [], $message = 'sorry you are not allowed to submit anymore');
        }
        if (isset($request->file)) {

            $request->validate([
                'file' => 'file|distinct|mimes:txt,pdf,docs,jpg',
            ]);
            if (isset($request->file_description)) {
                $description = $request->file_description;
            } else {
                $description = Null;
            }
            $userassigment->attachment_id = attachment::upload_attachment($request->file, 'assigment', $description)->id;
        } else {
            $userassigment->attachment_id = null;
        }
        if (isset($request->content)) {
            $userassigment->content = $request->content;
        } else {
            $userassigment->content = null;
        }
        $userassigment->submit_date = Carbon::now();
        $userassigment->save();
        return HelperController::api_response_format(200, $body = $userassigment, $message = 'your answer is submitted');
    }

    /*


            grade assigment


*/
    public function gradeAssigment(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:user_assigments,user_id',
            'assignment_id' => 'required|exists:user_assigments,assignment_id',
            'grade' => 'required|integer',
            'feedback' => 'string'
        ]);
        $userassigment = UserAssigment::where('user_id', $request->user_id)->where('assignment_id', $request->assignment_id)->first();
        $assigment = assignment::where('id', $request->assignment_id)->first();
        if ($assigment->mark < $request->grade) {
            return HelperController::api_response_format(400, $body = [], $message = 'please put grade less than ' . $assigment->mark);
        }
        if (isset($request->feedback)) {
            $userassigment->feedback = $request->feedback;
        }
        $userassigment->grade = $request->grade;
        $userassigment->status_id = 1;
        $userassigment->save();
        return HelperController::api_response_format(200, $body = [], $message = 'assigment graded sucess');
    }
    /*


            override assigment users


*/
    public function override(Request $request)
    {
        $request->validate([
            'user_id' => 'exists:user_assigments,user_id',
            'assignment_id' => 'required|exists:user_assigments,assignment_id',

        ]);
        if (!isset($request->user_id) && !isset($request->class)) {
            return HelperController::api_response_format(400, $body = [], $message = 'please enter user id or class id');
        }
        $usersall=array();
        if (isset($request->user_id)) {
            $userassigment = UserAssigment::where('user_id', $request->user_id)->where('assignment_id', $request->assignment_id)->first();
            if ($userassigment == null) {
                return HelperController::api_response_format(400, $body =  $userassigment, $message = 'this user is not assigned to this course');
            }
            $userassigment->override = 1;
            $userassigment->save();
            $usersall[]=$request->user_id;
        }
        if (isset($request->class)) {
            $request->validate([
                'type' => 'required|exists:academic_types,id',
                'level' => 'required|exists:levels,id',
                'class' => 'required|array',
                'class.*'=>'required|exists:classes,id',
                'segment' => 'exists:segments,id',
                'year' => 'exists:academic_years,id',
                'course' => 'required|exists:courses,id',
            ]);

            $year=null;
            if(isset($request->year))
            {
                $year=$request->year;
            }
            $segment=null;
            if(isset($request->segment))
            {
                $segment=$request->segment;
            }
            $course_segment=array();
            foreach ($request->class as $singleclass) {
                $coursreq=new Request([
                    'type' => $request->type,
                    'level' => $request->level,
                    'class'=> $singleclass,
                    'year' => $year,
                    'segment'=>$segment,
                    'course'=>$request->course
                ]);
                $course_segment[]=HelperController::Get_Course_segment_Course($coursreq)['value']->id;


            }

            /*





            */
        foreach($course_segment as $coursseg){

            $usersIDs = Enroll::where('course_segment', $coursseg)->pluck('user_id')->toarray();
            $usersall[]=$usersIDs;
            foreach ($usersIDs as $userId) {
                # code...
                $userassigment = UserAssigment::where('user_id', $userId)->where('assignment_id', $request->assignment_id)->first();
                if ($userassigment == null) {
                    return HelperController::api_response_format(400, $body =  $userassigment, $message = 'this user is not assigned to this course');
                }
                $userassigment->override = 1;
                $userassigment->save();
            }}
        }
        return HelperController::api_response_format(200, $body =  $usersall, $message = 'those users now can submit');

    }
    /*


            delete assigment


*/
    public function deleteAssigment(Request $request)
    {
        $request->validate([
            'assignment_id' => 'required|exists:user_assigments,assignment_id',
        ]);
        $assigment = assignment::where('id', $request->assignment_id)->first();
        $assigment->delete();
        return HelperController::api_response_format(200, $body = [], $message = 'Assigment deleted succesfully');
    }
    public function GetAssignment(Request $request)
    {
        $request->validate([
            'assignment_id' => 'required|exists:assignments,id',
        ]);
        $user = Auth::user();
        $assignment = assignment::where('id', $request->assignment_id)->first();
        $assignment['attachment'] = attachment::where('id', $assignment->attachment_id)->first();
        $userassigments = UserAssigment::where('assignment_id', $assignment->id)->get();
        $assignment['user_assignment'] = $userassigments;
        foreach ($assignment['user_assignment'] as $value) {
            # code...
            $value['attachment'] = attachment::where('id', $value->attachment_id)->first();
        }

        if (($user->roles->first()->id) == 4) {
            return HelperController::api_response_format(200, $body = $assignment, $message = []);
        }

        ///////////////student
        if (($user->roles->first()->id) == 3) {
            $studentassigment = UserAssigment::where('assignment_id', $assignment->id)->where('user_id', $user->id)->first();
            if ($assignment->start_date > Carbon::now() || $assignment->due_date < Carbon::now()) {
                return 'ahmed';
                if ($studentassigment->override == 0) {
                    return HelperController::api_response_format(400, $body = [], $message = 'you are not allowed to see the assignment at this moment');
                }
            }
            if ($assignment->visiable == 0) {
                return HelperController::api_response_format(400, $body = [], $message = 'you are not allowed to see the assignment at this moment');
            }
            $stuassignment = assignment::where('id', $request->assignment_id)->first();
            $stuassignment['attachment'] = attachment::where('id', $stuassignment->attachment_id)->first();
            $stuassignment['user_submit'] = $studentassigment;
            $stuassignment['user_submit']->attachment = attachment::where('id', $stuassignment['user_submit']->attachment_id)->first();
            return HelperController::api_response_format(200, $body = $stuassignment, $message = []);
        }
    }

    public function toggleAssignmentVisibity(Request $request){
        try{
            $request->validate([
                'assignment_id'=>'required|exists:assignments,id',
            ]);

            $assigment = assignment::find($request->assignment_id);

            $assigment->visiable = ($assigment->visiable == 1)? 0 : 1;
            $assigment->save();

            return HelperController::api_response_format(200,$assigment,'Toggle Successfully');
        }catch (Exception $ex){
            return HelperController::api_response_format(400,null,'Please Try again');
        }
    }

}
