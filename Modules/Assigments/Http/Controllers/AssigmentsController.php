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
/*


            add Assigment


*/

    public function createAssigment(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'is_graded' => 'required|boolean',
            'mark' => 'required|integer',
            'allow_attachment' => 'required|integer|min:0|max:3',
            'opening_date' => 'required|before:closing_date|after:'. Carbon::now(),
            'closing_date' => 'required',
            'visiable'=>'required|boolean',
            'course_segment' => 'required|exists:enrolls,course_segment',
        ]);
        if(!isset($request->file)&&!isset($request->content))
        {
            return HelperController::api_response_format(400, $body = [], $message = 'please enter file or content');
        }
        $assigment=new assignment;
        if(isset($request->file))
        {

            $request->validate([
                'file' => 'file|distinct|mimes:txt,pdf,docs,jpg',
            ]);
            if(isset($request->file_description))
            {
                $description=$request->file_description;
            }
            else {
                $description=Null;
            }
            $assigment->attachment_id= attachment::upload_attachment($request->file,'assigment',$description)->id;
        }
        if(isset($request->content))
        {
            $assigment->content=$request->content;
        }
        $assigment->name=$request->name;
        $assigment->is_graded=$request->is_graded;
        $assigment->mark=$request->mark;
        $assigment->allow_attachment=$request->allow_attachment;
        $assigment->opening_date=$request->opening_date;
        $assigment->closing_date=$request->closing_date;
        $assigment->visiable=$request->visiable;
        $assigment->save();

        $data= array("course_segment"=>$request->course_segment, "assignments_id"=>$assigment->id, "submit_date"=>$request->submit_date);

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
            'allow_attachment' => 'required|boolean',
            'opening_date' => 'required|before:closing_date|after:'. Carbon::now(),
            'closing_date' => 'required',
            'visiable'=>'required|boolean',
            'file_description'=>'string',

        ]);
        if(!isset($request->file)&&!isset($request->content))
        {
            return HelperController::api_response_format(400, $body = [], $message = 'please enter file or content');
        }
        $assigment=assignment::find($request->id);
        if(isset($request->file))
        {

            $request->validate([
                'file' => 'file|distinct|mimes:txt,pdf,docs,jpg',
            ]);
            if(isset($request->file_description))
            {
                $description=$request->file_description;
            }
            else {
                $description=Null;
            }
            $assigment->attachment_id= attachment::upload_attachment($request->file,'assigment',$description)->id;
        }
        else {
            $assigment->attachment_id=null;
        }
        if(isset($request->content))
        {
            $assigment->content=$request->content;
        }
        $assigment->name=$request->name;
        $assigment->is_graded=$request->is_graded;
        $assigment->mark=$request->mark;
        $assigment->allow_attachment=$request->allow_attachment;
        $assigment->opening_date=$request->opening_date;
        $assigment->closing_date=$request->closing_date;
        $assigment->visiable=$request->visiable;
        $assigment->save();

        return HelperController::api_response_format(200, $body = $assigment, $message = 'assigment added');
    }
/*


            assign Assigment to users


*/

public function assignAsstoUsers($request)
{
    $usersIDs=Enroll::where('course_segment',$request['course_segment'])->pluck('user_id')->toarray();
    foreach ($usersIDs as $userId) {
        # code...
        $userassigment= new UserAssigment;
        $userassigment->user_id=$userId;
        $userassigment->assignment_id=$request['assignments_id'];
        $userassigment->status_id=2;
        $userassigment->override=0;
        $userassigment->save();


    }

}
/*


            submit Assigment from user


*/


public function submitAssigment(Request $request)
{
    $request->validate([
        'user_id' => 'required|exists:user_assigments,user_id',
        'assignment_id'=>'required|exists:user_assigments,assignment_id',
    ]);
    $assigment=assignment::where('id',$request->assignment_id)->first();

/*

        0===================>content
        1===================>attached_file
        2===================>both
        3===================>can submit content or file



*/
        if((($assigment->allow_attachment==3))&&((!isset($request->content))&&(!isset($request->file))))
        {
            return HelperController::api_response_format(400, $body = [], $message = 'you must enter the content or the file');
        }
        if((($assigment->allow_attachment==0))&&((!isset($request->content))||(isset($request->file))))
        {
            return HelperController::api_response_format(400, $body = [], $message = 'you must enter only the content');
        }

        if((($assigment->allow_attachment==1))&&((isset($request->content))||(!isset($request->file))))
        {
            return HelperController::api_response_format(400, $body = [], $message = 'you must enter only the file');
        }
        if((($assigment->allow_attachment==2))&&((!isset($request->content))||(!isset($request->file))))
        {
            return HelperController::api_response_format(400, $body = [], $message = 'you must enter both the content and the file');
        }
    $userassigment= UserAssigment::where('user_id',$request->user_id)->where('assignment_id',$request->assignment_id)->first();
    if(((($assigment->opening_date >  Carbon::now())||( Carbon::now() > $assigment->closing_date ))&&($userassigment->override==0))||($userassigment->status_id ==1)||($assigment->visiable==0))
    {
        return HelperController::api_response_format(400, $body = [], $message = 'sorry you are not allowed to submit anymore');
    }
    if(isset($request->file))
    {

        $request->validate([
            'file' => 'file|distinct|mimes:txt,pdf,docs,jpg',
        ]);
        if(isset($request->file_description))
        {
            $description=$request->file_description;
        }
        else {
            $description=Null;
        }
        $userassigment->attachment_id= attachment::upload_attachment($request->file,'assigment',$description)->id;
    }
    else{
        $userassigment->attachment_id=null;
    }
    if(isset($request->content))
    {
        $userassigment->content=$request->content;
    }
    else {
        $userassigment->content=null;
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
        'assignment_id'=>'required|exists:user_assigments,assignment_id',
        'grade'=>'required|integer',
        'feedback'=>'string'
    ]);
    $userassigment= UserAssigment::where('user_id',$request->user_id)->where('assignment_id',$request->assignment_id)->first();
    $assigment=assignment::where('id',$request->assignment_id)->first();
    if($assigment->mark < $request->grade)
    {
        return HelperController::api_response_format(400, $body = [], $message = 'please put grade less than '.$assigment->mark);

    }
    if(isset($request->feedback))
    {
        $userassigment->feedback=$request->feedback;
    }
    $userassigment->grade=$request->grade;
    $userassigment->status_id=1;
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
        'assignment_id'=>'required|exists:user_assigments,assignment_id',
        'course_segment' => 'exists:enrolls,course_segment'
    ]);
    if(!isset($request->user_id)&&!isset($request->course_segment))
    {
        return HelperController::api_response_format(400, $body = [], $message = 'please enter user id or course segment id');
    }
    if(isset($request->user_id))
    {
        $userassigment= UserAssigment::where('user_id',$request->user_id)->where('assignment_id',$request->assignment_id)->first();
        if($userassigment==null)
        {
            return HelperController::api_response_format(400, $body =  $userassigment, $message = 'this user is not assigned to this course');
        }
        $userassigment->override=1;
        $userassigment->save();
        return HelperController::api_response_format(200, $body =  $userassigment, $message = 'user '.$request->user_id .' now can submit');

    }
    if(isset($request->course_segment))
    {
        $usersIDs=Enroll::where('course_segment',$request['course_segment'])->pluck('user_id')->toarray();
        foreach ($usersIDs as $userId) {
            # code...
            $userassigment= UserAssigment::where('user_id',$userId)->where('assignment_id',$request->assignment_id)->first();
            if($userassigment==null)
            {
                return HelperController::api_response_format(400, $body =  $userassigment, $message = 'this user is not assigned to this course');
            }
            $userassigment->override=1;
            $userassigment->save();
        }
        return HelperController::api_response_format(200, $body =  $usersIDs, $message = 'those users now can submit');


    }
}
/*


            delete assigment


*/
public function deleteAssigment(Request $request)
{
    $request->validate([
        'assignment_id'=>'required|exists:user_assigments,assignment_id',
    ]);
    $assigment=assignment::where('id',$request->assignment_id)->first();
    $assigment->delete();
    return HelperController::api_response_format(200, $body = [], $message = 'Assigment deleted succesfully');

}
public function GetAssignment(Request $request)
{
    $request->validate([
        'assignment_id'=>'required|exists:assignments,id',
    ]);
    $user = Auth::user();
    $assignment=assignment::where('id',$request->assignment_id)->first();
    $assignment['attachment']=attachment::where('id',$assignment->attachment_id)->first();
    $userassigments=UserAssigment::where('assignment_id',$assignment->id)->get();
    $assignment['user_assignment']=$userassigments;
    foreach ($assignment['user_assignment'] as $value)
    {
        # code...
    $value['attachment']=attachment::where('id',$value->attachment_id)->first();
    }

    if(($user->roles->first()->id)==4)
    {
        return HelperController::api_response_format(200, $body = $assignment, $message = []);
    }

    ///////////////student
    if(($user->roles->first()->id)==3)
    {
        $studentassigment=UserAssigment::where('assignment_id',$assignment->id)->where('user_id',$user->id)->first();
        if($assignment->opening_date > Carbon::now() || $assignment->closing_date < Carbon::now())
        {
            if($studentassigment->override==0){
                return HelperController::api_response_format(400, $body = [], $message = 'you are not allowed to see the assignment at this moment');
            }
        }
        if($assignment->visiable==0)
        {
            return HelperController::api_response_format(400, $body = [], $message = 'you are not allowed to see the assignment at this moment');
        }
        $stuassignment=assignment::where('id',$request->assignment_id)->first();
        $stuassignment['attachment']=attachment::where('id',$stuassignment->attachment_id)->first();
        $stuassignment['user_submit']=$studentassigment;
        $stuassignment['user_submit']->attachment=attachment::where('id',$stuassignment['user_submit']->attachment_id)->first();
        return HelperController::api_response_format(200, $body = $stuassignment, $message = []);



    }

}

}
