<?php

namespace Modules\Assigments\Http\Controllers;

use App\attachment;
use App\CourseSegment;
use App\Enroll;
use App\User;
use App\Lesson;
use App\SegmentClass;
use App\ClassLevel;
use App\Classes;
use Spatie\Permission\Models\Permission;
use URL;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use App\Http\Controllers\HelperController;
use Carbon\Carbon;
use Modules\Assigments\Entities\assignment;
use Modules\Assigments\Entities\AssignmentLesson;
use Modules\Assigments\Entities\UserAssigment;
use App\Component;
use App\LessonComponent;
use App\status;

class AssigmentsController extends Controller
{
    public function install_Assignment()
    {
        if (\Spatie\Permission\Models\Permission::whereName('assignment/add')->first() != null) {
            return \App\Http\Controllers\HelperController::api_response_format(400, null, 'This Component is installed before');
        }

        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'assignment/add', 'title' => 'add assignment']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'assignment/update', 'title' => 'update assignment']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'assignment/update-assignemnt-lesson', 'title' => 'update assignemnt lesson']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'assignment/delete-assign-lesson', 'title' => 'delete assignemnt lesson']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'assignment/submit', 'title' => 'submit assignment']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'assignment/grade', 'title' => 'grade assignment']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'assignment/override', 'title' => 'override assignment']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'assignment/delete', 'title' => 'delete assignment']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'assignment/get', 'title' => 'get assignment']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'assignment/toggle', 'title' => 'toggle assignment']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'assignment/get-all', 'title' => 'get all assignments']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'assignment/editgrade', 'title' => 'edit assignments grades']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'site/assignment/assigned-users', 'title' => 'assign Assignment to Users']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'site/assignment/getAssignment', 'title' => 'get Assignment']);
        
        $role = \Spatie\Permission\Models\Role::find(1);
        $role->givePermissionTo('assignment/add');
        $role->givePermissionTo('assignment/update');
        $role->givePermissionTo('assignment/update-assignemnt-lesson');
        $role->givePermissionTo('assignment/submit');
        $role->givePermissionTo('assignment/grade');
        $role->givePermissionTo('assignment/override');
        $role->givePermissionTo('assignment/delete');
        $role->givePermissionTo('assignment/get');
        $role->givePermissionTo('assignment/toggle');
        $role->givePermissionTo('assignment/get-all');
        $role->givePermissionTo('assignment/editgrade');
        $role->givePermissionTo('site/assignment/assigned-users');
        $role->givePermissionTo('site/assignment/getAssignment');
        $role->givePermissionTo('delete-assign-lesson');

        Component::create([
            'name' => 'Assigments',
            'module'=>'Assigments',
            'model' => 'assignment',
            'type' => 3,
            'active' => 1
        ]);

        $status = status::all();

        if($status->isEmpty())
        {
            $addstatus=array(
                array('name'=>'Graded'),
                array('name' => 'Not Graded')
            );
            status::insert($addstatus);
        }

        return \App\Http\Controllers\HelperController::api_response_format(200, null, 'Component Installed Successfully');
    }

    public function getAllAssigment(Request $request){
        $request->validate([
            'course' => 'required_with:class|integer|exists:courses,id',
            'class' => 'required_with:course|integer|exists:classes,id',
            'assignment_id' => 'exists:assignments,id',
        ]);
        $ASSIGNMENTS = collect([]);
        if(isset($request->class)){

            $class = Classes::with([
                'classlevel.segmentClass.courseSegment' =>
                    function ($query) use ($request) {
                        $query->with(['lessons'])->where('course_id',$request->course);
                    }])->whereId($request->class)->first();
            foreach($class->classlevel->segmentClass as $segmentClass){
                foreach($segmentClass->courseSegment as $courseSegment){
                    // return $courseSegment;

                    foreach($courseSegment->lessons as $lesson){
                        
                        foreach($lesson->AssignmentLesson as $AssignmentLesson){
                            $assignments = $AssignmentLesson->where('visible',1)->Assignment;
                            if($request->filled('assignment_id'))
                            $assignments = $AssignmentLesson->where('visible',1)->where('assignment_id',$request->assignment_id)->Assignment;
                            foreach ($assignments as $assignment) {
                                $Answer= collect([]);
                                $st_answer = UserAssigment::where('assignment_id',$assignment->id)->get();
                                foreach($st_answer as $st_answer){
                                $Ans  = Null;
                                if(isset($st_answer))
                                    $Ans = $st_answer;                    
                                if(isset($st_answer->attachment_id))
                                    $Ans = $st_answer->attachment;
                                    $Answer ->push($Ans);
                             }
                             $assignment->student_answer =   $Answer;
                                $attachment =  $assignment->attachment;
                                if(isset($attachment)){
                                    $assignment->path  = URL::asset('storage/files/'.$attachment->type.'/'.$attachment->name);
                                }

                                unset($assignment->attachment);

                                $ASSIGNMENTS->push($assignment);
                            }
                        }
                    }

                }
            }
        }
        else{
            $assignments = assignment::all();

            if($request->filled('assignment_id'))
                $assignments = assignment::where('id',$request->assignment_id)->get();
            foreach ($assignments as $assignment) {
                $Answer= collect([]);
                $st_answer = UserAssigment::where('assignment_id',$assignment->id)->get();
                foreach($st_answer as $st_answer){
                $Ans  = Null;
                if(isset($st_answer))
                    $Ans = $st_answer;                    
                if(isset($st_answer->attachment_id))
                    $Ans = $st_answer->attachment;
                    $Answer ->push($Ans);
             }
             $assignment->student_answer =   $Answer;
             if($request->filled('lesson_id')) 
                $assignment->lesson = AssignmentLesson::where('assignment_id',$assignment->id)->where('lesson_id',$request->lesson_id)->first();

               $attachment =  $assignment->attachment;
                if(isset($attachment)){
                    $assignment->path  = URL::asset('storage/files/'.$attachment->type.'/'.$attachment->name);
                }

                unset($assignment->attachment);

                $ASSIGNMENTS->push($assignment);
            }
        }
        return HelperController::api_response_format(200,$ASSIGNMENTS);
    }

    //Create assignment
    public function createAssigment(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'content' => 'string',
        ]);
        if (!isset($request->file) && !isset($request->content)) {
            return HelperController::api_response_format(400, $body = [], $message = 'please enter file or content');
        }
        $assigment = new assignment;
        if (isset($request->file)) {
            $request->validate([
                'file' => 'file|distinct|mimes:txt,pdf,docs,jpg,doc,docx,mp4,avi,flv,mpga,ogg,ogv,oga,jpg,jpeg,png,gif',
            ]);
            if (isset($request->file_description)) {
                $description = $request->file_description;
            } else {
                $description = Null;
            }
            $assigment->attachment_id = attachment::upload_attachment($request->file, 'assigment', $description)->id;
        }
        if($request->filled('content'))
            $assigment->content = $request->content;    
        $assigment->name = $request->name;
        $assigment->save();        
        return HelperController::api_response_format(200, $body = $assigment, $message = 'assigment added');
    }

    /*
        update Assigment
    */
    public function updateAssigment(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:assignments,id',
            'name' => 'string',
            'file_description' => 'string',
            'content'  => 'string',
    
        ]);
        $assigment = assignment::find($request->id);
        $CheckIfAnswered= UserAssigment::where('assignment_id',$request->id)->where('submit_date','!=',null)->get();
        if(count($CheckIfAnswered) > 0)
            return HelperController::api_response_format(200, 'Cannot update,Assigment was submitted before!');

        if ($request->hasFile('file')) {

            $request->validate([
                'file' => 'file|distinct|mimes:txt,pdf,docs,jpg,doc,docx,mp4,avi,flv,mpga,ogg,ogv,oga,jpg,jpeg,png,gif',
            ]);
            if (isset($request->file_description)) {
                $description = $request->file_description;
            
            $assigment->attachment_id = attachment::upload_attachment($request->file, 'assigment', $description)->id;
        }
        }
        if ($request->filled('content')) 
            $assigment->content = $request->content;
        if ($request->filled('name')) 
            $assigment->name = $request->name;
        $assigment->save();

        return HelperController::api_response_format(200, $body = $assigment, $message = 'assigment edited');
    }
    public function updateAssignmentLesson(Request $request)
    {
        $request->validate([
            'is_graded' => 'boolean',
            'mark' => 'integer',
            'lesson_id' => 'required|integer|exists:lessons,id',
            'assignment_id' => 'required|exists:assignments,id',
            'allow_attachment' => 'integer|min:0|max:3',
            'opening_date' => 'date |date_format:Y-m-d H:i:s|before:closing_date',
            'closing_date' => 'date |date_format:Y-m-d H:i:s',
            'visible' => 'boolean',
            'publish_date' => 'date |date_format:Y-m-d H:i:s|before:closing_date',
            'grade_category'=>'exists:grade_categories,id',

        ]);
        $AssignmentLesson=AssignmentLesson::where('assignment_id',$request->assignment_id)->where('lesson_id',$request->lesson_id)->first();
        if(!isset($AssignmentLesson)){
            return HelperController::api_response_format(400, $message = 'Assignment Lesson Not Found');
        }  
         if($request->filled('is_graded'))
            $AssignmentLesson->is_graded = $request->is_graded;
        if($request->filled('mark'))
            $AssignmentLesson->mark = $request->mark;
        if($request->filled('grade_category'))
            $AssignmentLesson->grade_category = $request->grade_category;
        if($request->filled('visible'))
            $AssignmentLesson->visible = $request->visible;
        if($request->filled('allow_attachment'))
            $AssignmentLesson->allow_attachment = $request->allow_attachment;
        if($request->filled('mark'))
            $AssignmentLesson->start_date = $request->opening_date;
        if($request->filled('closing_date'))
            $AssignmentLesson->due_date = $request->closing_date;
        if($request->filled('publish_date'))
            $AssignmentLesson->publish_date = $request->publish_date;
        $AssignmentLesson->save();


        $usersIDs = UserAssigment::where('assignment_id',$request->assignment_id)->pluck('user_id')->toArray();
        $lessonId=AssignmentLesson::where('assignment_id',$request->lesson_id)->pluck('lesson_id')->first();
        $courseSegment=Lesson::where('id',$lessonId)->pluck('course_segment_id')->first();
        $courseID=CourseSegment::where('id',$courseSegment)->pluck('course_id')->first();
        $segmentClass=CourseSegment::where('id',$courseSegment)->pluck('segment_class_id')->first();
        $ClassLevel=SegmentClass::where('id',$segmentClass)->pluck('class_level_id')->first();
        $classId=ClassLevel::where('id',$ClassLevel)->pluck('class_id')->first();
        user::notify([
            'id' => $request->assignment_id,
            'message' => 'Assignment is updated',
            'from' => Auth::user()->id,
            'users' => $usersIDs,
            'course_id' => $courseID,
            'class_id'=>$classId,
            'type' => 'assignment',
            'link' => url(route('getAssignment')) . '?assignment_id=' . $request->id,
            'publish_date' => $AssignmentLesson->start_date
        ]);
        $all = Assignment::all();
            return HelperController::api_response_format(200,$all, $message = 'Assignment Lesson Updated Successfully');
    }

    /*
        assign Assigment to users
    */
    public function assignAsstoUsers($request)
    {
      $roles = Permission::where('name','assignment/submit')->first();
      $roles_id= $roles->roles->pluck('id');
      $usersIDs = Enroll::where('course_segment', $request['course_segment'])->whereIn('role_id' , $roles_id)->pluck('user_id')->toarray();
      foreach ($usersIDs as $userId) {
            $userassigment = new UserAssigment;
            $userassigment->user_id = $userId;
            $userassigment->assignment_id = $request['assignments_id'];
            $userassigment->status_id = 2;
            $userassigment->override = 0;
            $userassigment->save();
        }
        $courseID=CourseSegment::where('id',$request['course_segment'])->pluck('course_id')->first();
        user::notify([
                'id' => $request['assignments_id'],
                'message' => 'A new Assignment is added',
                'from' => Auth::user()->id,
                'users' => $usersIDs,
                'course_id' => $courseID,
                'class_id'=>$request['class'],
                'type' => 'assignment',
                'link' => url(route('getAssignment')) . '?assignment_id=' . $request['assignments_id'],
                'publish_date' => $request['publish_date']
            ]);
    }

    /*
        submit Assigment from user
    */
    public function submitAssigment(Request $request)
    {
        $request->validate([
            'assignment_id' => 'required|exists:user_assigments,assignment_id',
        ]);
        $assigment = assignment::where('id', $request->assignment_id)->first();
        $assilesson = AssignmentLesson::where('assignment_id',$request->assignment_id)->first();
        /*
            0===================>content
            1===================>attached_file
            2===================>both
            3===================>can submit content or file
        */

        if ((($assilesson->allow_attachment == 3)) && ((!isset($request->content)) && (!isset($request->file)))) {
            return HelperController::api_response_format(400, $body = [], $message = 'you must enter the content or the file');
        }
        if ((($assilesson->allow_attachment == 0)) && ((!isset($request->content)) || (isset($request->file)))) {
            return HelperController::api_response_format(400, $body = [], $message = 'you must enter only the content');
        }

        if ((($assilesson->allow_attachment == 1)) && ((isset($request->content)) || (!isset($request->file)))) {
            return HelperController::api_response_format(400, $body = [], $message = 'you must enter only the file');
        }
        if ((($assilesson->allow_attachment == 2)) && ((!isset($request->content)) || (!isset($request->file)))) {
            return HelperController::api_response_format(400, $body = [], $message = 'you must enter both the content and the file');
        }
        $userassigment = UserAssigment::where('user_id', Auth::user()->id)->where('assignment_id', $request->assignment_id)->first();

        if(!isset($userassigment)){
            return HelperController::api_response_format(400, $body = [], $message = 'This user isn\'t assign to this assignment');
        }

        if (((($assilesson->start_date >  Carbon::now()) || (Carbon::now() > $assilesson->due_date)) && ($userassigment->override == 0)) || ($userassigment->status_id == 1)) {
            return HelperController::api_response_format(400, $body = [], $message = 'sorry you are not allowed to submit anymore');
        }
        if (isset($request->file)) {

            $request->validate([
                'file' => 'file|distinct|mimes:txt,pdf,docs,jpg,doc,docx,mp4,avi,flv,mpga,ogg,ogv,oga,jpg,jpeg,png,gif',
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
        $assilesson=AssignmentLesson::where('assignment_id',$request->assignment_id)->first();
        if ($assilesson->mark < $request->grade) {
            return HelperController::api_response_format(400, $body = [], $message = 'please put grade less than ' . $assilesson->mark);
        }
        if (isset($request->feedback)) {
            $userassigment->feedback = $request->feedback;
        }
        $userassigment->grade = $request->grade;
        $userassigment->status_id = 1;
        $userassigment->save();
        return HelperController::api_response_format(200, $body =$userassigment , $message = 'assigment graded sucess');
    }
    public function editGradeAssignment(Request $request){
        $request->validate([
            'user_id' => 'required|exists:user_assigments,user_id',
            'assignment_id' => 'required|exists:user_assigments,assignment_id',
            'grade' => 'required|integer',
            'feedback' => 'string'
        ]);

        $userassigment = UserAssigment::where('user_id', $request->user_id)->where('assignment_id', $request->assignment_id)->first();
        $assigment = assignment::where('id', $request->assignment_id)->first();
        $assilesson=AssignmentLesson::where('assignment_id',$request->assignment_id)->first();
        if ($assilesson->mark < $request->grade) {
            return HelperController::api_response_format(400, $body = [], $message = 'please put grade less than ' . $assilesson->mark);
        }
        if (isset($request->feedback)) {
            $userassigment->feedback = $request->feedback;
        }
        $userassigment->grade = $request->grade;
        $userassigment->save();
        return HelperController::api_response_format(200, $body = $userassigment , $message = 'assigment graded sucess');

    }

    /*
        override assigment users
    */
    public function override(Request $request)
    {
        $request->validate([
            'user_id' => 'exists:user_assigments,user_id',
            'assignment_id' => 'required|exists:user_assigments,assignment_id',
            'course_segment' => 'exists:enrolls,course_segment'
        ]);
        if (!isset($request->user_id) && !isset($request->course_segment)) {
            return HelperController::api_response_format(400, $body = [], $message = 'please enter user id or course segment id');
        }
        if (isset($request->user_id)) {
            $userassigment = UserAssigment::where('user_id', $request->user_id)->where('assignment_id', $request->assignment_id)->first();
            if ($userassigment == null) {
                return HelperController::api_response_format(400, $body =  $userassigment, $message = 'this user is not assigned to this course');
            }
            $userassigment->override = 1;
            $userassigment->save();
            return HelperController::api_response_format(200, $body =  $userassigment, $message = 'user ' . $request->user_id . ' now can submit');
        }
        if (isset($request->course_segment)) {
            $usersIDs = Enroll::where('course_segment', $request['course_segment'])->pluck('user_id')->toarray();
            foreach ($usersIDs as $userId) {
                # code...
                $userassigment = UserAssigment::where('user_id', $userId)->where('assignment_id', $request->assignment_id)->first();
                if ($userassigment == null) {
                    return HelperController::api_response_format(400, $body =  $userassigment, $message = 'this user is not assigned to this course');
                }
                $userassigment->override = 1;
                $userassigment->save();
            }
            return HelperController::api_response_format(200, $body =  $usersIDs, $message = 'those users now can submit');
        }
    }

    /*
        delete assigment
    */
    public function deleteAssignmentLesson(Request $request)
    {
        $request->validate([
            'assignment_id' => 'required|exists:assignment_lessons,assignment_id',
            'lesson_id' => 'required|exists:assignment_lessons,lesson_id'
        ]);
        $assigment = AssignmentLesson::where('assignment_id', $request->assignment_id)->where('lesson_id',$request->lesson_id)->first();
        $assigment->delete();
        return HelperController::api_response_format(200, $body = [], $message = 'Assigment Lesson deleted succesfully');
    }

    public function deleteAssignment(Request $request)
    {
        $request->validate([
            'assignment_id' => 'required|exists:assignments,id'
        ]);
        $assign=Assignment::where('id',$request->assignment_id);
        $assign->delete();
        return HelperController::api_response_format(200, $body = [], $message = 'Assigment deleted succesfully');
    }


    
    public function GetAssignment(Request $request)
    {
        $request->validate([
            'assignment_id' => 'required|exists:assignments,id',
            'lesson_id' => 'required|exists:assignment_lessons,lesson_id'
        ]);

        $user = Auth::user();
        $assignment = assignment::where('id', $request->assignment_id)->first();
        $assignment['attachment'] = attachment::where('id', $assignment->attachment_id)->first();
        $userassigments = UserAssigment::where('assignment_id', $assignment->id)->get();
        $assignmentt = AssignmentLesson::where('assignment_id', $request->assignment_id)->where('lesson_id',$request->lesson_id)->first();
        $assignment['lesson']= $assignmentt;
        if (count($userassigments)>0){
            $assignment['allow_edit']= false;
        }
        else{
            $assignment['allow_edit']= true;

        }
        $assignment['user_assignment'] = $userassigments;

        foreach ($assignment['user_assignment'] as $value) {
            # code...
            $value->user;
            $value['attachment'] = attachment::where('id', $value->attachment_id)->first();
        }

//        if (($user->roles->first()->id) == 4 || ($user->roles->first()->id) == 1) {
        if (!($user->can('site/assignment/getAssignment'))) {
            $assignment->lesson = $assignmentt;
            return HelperController::api_response_format(200, $body = $assignment, $message = []);
        }
        ///////////////student
//        if (($user->roles->first()->id) == 3) {
        if ($user->can('site/assignment/getAssignment')) {
            $studentassigment = UserAssigment::where('assignment_id', $assignment->id)->where('user_id', $user->id)->first();
            $assignment['lesson'] = $assignmentt;
            if ($assignment->start_date > Carbon::now() || $assignment->due_date < Carbon::now()) {
                if (isset($studentassigment->override) && $studentassigment->override== 0) {
                    return HelperController::api_response_format(400, $body = [], $message = 'you are not allowed to see the assignment at this moment');
                }
            }

            $stuassignment = assignment::where('id', $request->assignment_id)->first();
            $stuassignment['attachment'] = attachment::where('id', $stuassignment->attachment_id)->first();
            $stuassignment['user_submit'] = $studentassigment;
            if(isset($stuassignment['user_submit']->attachment_id)) {
                $stuassignment['user_submit']->attachment = attachment::where('id', $stuassignment['user_submit']->attachment_id)->first();
            }
            return HelperController::api_response_format(200, $body = $assignment, $message = []);
        }
    }

    public function toggleAssignmentVisibity(Request $request)
    {
        try {
            $request->validate([
                'assignment_id' => 'required|exists:assignments,id',
                'lesson_id' => 'required|exists:assignment_lessons,lesson_id'
            ]);

            $assigment = AssignmentLesson::where('assignment_id',$request->assignment_id)
                ->where('lesson_id',$request->lesson_id)->first();
            if(!isset($assigment)){
                return HelperController::api_response_format(400, null, 'Try again , Data invalid');
            }

            $assigment->visible = ($assigment->visible == 1) ? 0 : 1;
            $assigment->save();

            return HelperController::api_response_format(200, $assigment, 'Toggle Successfully');
        } catch (Exception $ex) {
            return HelperController::api_response_format(400, null, 'Please Try again');
        }
    }
    public function AssignAssignmentToLesson(Request $request)
    {
     $request->validate([
        'assignment_id' => 'required|exists:assignments,id',
        'lesson_id' => 'required|exists:lessons,id',
        'is_graded' => 'required|boolean',
        'mark' => 'required|integer',
        'allow_attachment' => 'required|integer|min:0|max:3',
        'publish_date' => 'required|date|date_format:Y-m-d H:i:s|before:closing_date',
        'opening_date' => 'required|date|date_format:Y-m-d H:i:s|before:closing_date',
        'closing_date' => 'date|date_format:Y-m-d H:i:s',
        'grade_category'=>'exists:grade_categories,id',
        'scale'=>'exists:scales,id',
        'visible'=> 'boolean',
        ]);
        $assignment_lesson = new AssignmentLesson;
        $assignment_lesson->lesson_id = $request->lesson_id;
        $assignment_lesson->assignment_id = $request->assignment_id;
        $assignment_lesson->publish_date = $request->publish_date;
        $assignment_lesson->start_date = $request->opening_date;
        $assignment_lesson->mark = $request->mark;
        $assignment_lesson->is_graded = $request->is_graded;
        $assignment_lesson->allow_attachment = $request->allow_attachment;

        if($request->filled('closing_date'))
            $assignment_lesson->due_date = $request->closing_date;
        if($request->filled('scale'))
            $assignment_lesson->scale_id = $request->scale;
        if($request->filled('visible'))
            $assignment_lesson->visible = $request->visible;
        if($request->filled('grade_category'))
            $assignment_lesson->grade_category = $request->grade_category;
        $assignment_lesson->save();

        LessonComponent::create([
            'lesson_id' => $request->lesson_id,
            'comp_id' => $request->assignment_id,
            'module' => 'Assigments',
            'model' =>'assignment',
            'index' => LessonComponent::getNextIndex($request->lesson_id),
        ]);
        $lesson = Lesson::find($request->lesson_id);
        $data = array(
            "course_segment" => $lesson->course_segment_id,
            "assignments_id" => $request->assignment_id,
            "submit_date" => Carbon::now(),
            "publish_date"=>$request->opening_date,
            "class"=>$request->class
        );
        $this->assignAsstoUsers($data);
        $all = AssignmentLesson::all();
        return HelperController::api_response_format(200, $all, 'Assignment is assigned to a lesson Successfully');
        }
    
}
