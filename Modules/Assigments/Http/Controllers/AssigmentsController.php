<?php

namespace Modules\Assigments\Http\Controllers;

use App\attachment;
use App\CourseSegment;
use App\Enroll;
use App\User;
use App\Lesson;
use App\SegmentClass;
use App\ClassLevel;
use App\GradeCategory;
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
use Illuminate\Support\Facades\Validator;

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
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'delete-assign-lesson', 'title' => 'Delete assign']);

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
            'module' => 'Assigments',
            'model' => 'assignment',
            'type' => 3,
            'active' => 1
        ]);

        $status = status::all();

        if ($status->isEmpty()) {
            $addstatus = array(
                array('name' => 'Graded'),
                array('name' => 'Not Graded')
            );
            status::insert($addstatus);
        }

        return \App\Http\Controllers\HelperController::api_response_format(200, null, 'Component Installed Successfully');
    }

    public function getAllAssigment(Request $request)
    {
        $request->validate([
            'course' => 'required_with:class|integer|exists:courses,id',
            'class' => 'required_with:course|integer|exists:classes,id',
            'assignment_id' => 'exists:assignments,id',
        ]);
        $ASSIGNMENTS = collect([]);
        if (isset($request->class)) {

            $class = Classes::with([
                'classlevel.segmentClass.courseSegment' =>
                function ($query) use ($request) {
                    $query->with(['lessons'])->where('course_id', $request->course);
                }
            ])->whereId($request->class)->first();
            foreach ($class->classlevel->segmentClass as $segmentClass) {
                foreach ($segmentClass->courseSegment as $courseSegment) {
                    // return $courseSegment;

                    foreach ($courseSegment->lessons as $lesson) {

                        foreach ($lesson->AssignmentLesson as $AssignmentLesson) {
                            $assignments = $AssignmentLesson->where('visible', 1)->Assignment;
                            if ($request->filled('assignment_id'))
                                $assignments = $AssignmentLesson->where('visible', 1)->where('assignment_id', $request->assignment_id)->Assignment;
                            foreach ($assignments as $assignment) {
                                $Answer = collect([]);
                                $st_answer = UserAssigment::where('assignment_id', $assignment->id)->get();
                                foreach ($st_answer as $st_answer) {
                                    $Ans  = Null;
                                    if (isset($st_answer))
                                        $Ans = $st_answer;
                                    if (isset($st_answer->attachment_id))
                                        $Ans = $st_answer->attachment;
                                    $Answer->push($Ans);
                                }
                                $assignment->student_answer =   $Answer;
                                $attachment =  $assignment->attachment;
                                if (isset($attachment)) {
                                    $assignment->path  = URL::asset('storage/files/' . $attachment->type . '/' . $attachment->name);
                                }

                                unset($assignment->attachment);

                                $ASSIGNMENTS->push($assignment);
                            }
                        }
                    }
                }
            }
        } else {
            $assignments = assignment::all();

            if ($request->filled('assignment_id'))
                $assignments = assignment::where('id', $request->assignment_id)->get();
            foreach ($assignments as $assignment) {
                $Answer = collect([]);
                $st_answer = UserAssigment::where('assignment_id', $assignment->id)->get();
                foreach ($st_answer as $st_answer) {
                    $Ans  = Null;
                    if (isset($st_answer))
                        $Ans = $st_answer;
                    if (isset($st_answer->attachment_id))
                        $Ans = $st_answer->attachment;
                    $Answer->push($Ans);
                }
                $assignment->student_answer =   $Answer;
                if ($request->filled('lesson_id'))
                    $assignment->lesson = AssignmentLesson::where('assignment_id', $assignment->id)->where('lesson_id', $request->lesson_id)->first();

                $attachment =  $assignment->attachment;
                if (isset($attachment)) {
                    $assignment->path  = URL::asset('storage/files/' . $attachment->type . '/' . $attachment->name);
                }

                unset($assignment->attachment);

                $ASSIGNMENTS->push($assignment);
            }
        }
        return HelperController::api_response_format(200, $ASSIGNMENTS);
    }

    //Create assignment
    public function createAssigment(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'content' => 'string|required_without:file',
            'file' => 'file|distinct|mimes:txt,pdf,docs,jpg,doc,docx,mp4,avi,flv,mpga,ogg,ogv,oga,jpg,jpeg,png,gif,csv,doc,docx,mp3,mpeg,ppt,pptx,rar,rtf,zip,xlsx,xls,|required_without:content',
        ]);
       
        $assignment = new assignment;
        if ($request->hasFile('file')) {
            $assignment->attachment_id = attachment::upload_attachment($request->file, 'assignment', null)->id;
        }
        if ($request->filled('content'))
            $assignment->content = $request->content;
        $assignment->name = $request->name;
        $assignment->save();
        return HelperController::api_response_format(200, $body = $assignment, $message = 'Assignment added successfully');
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
        $assigmentLessons = AssignmentLesson::where('assignment_id',$request->id)->pluck('id');
        $CheckIfAnswered = UserAssigment::whereIn('assignment_lesson_id', $assigmentLessons)->where('submit_date', '!=', null)->get();
        if (count($CheckIfAnswered) > 0)
            return HelperController::api_response_format(400, 'Cannot update,Assigment was submitted before!');

        if ($request->hasFile('file')) {

            $request->validate([
                'file' => 'file|distinct|mimes:txt,pdf,docs,jpg,doc,docx,mp4,avi,flv,mpga,ogg,ogv,oga,jpg,jpeg,png,gif',
            ]);
            // if (isset($request->file_description)) {
                $description = (isset($request->file_description))? $request->file_description :null;

                $assigment->attachment_id = attachment::upload_attachment($request->file, 'assigment', $description)->id;
            // }
        }
        if ($request->filled('content'))
            $assigment->content = $request->content;
        if ($request->filled('name'))
            $assigment->name = $request->name;
        $assigment->save();

        return HelperController::api_response_format(200, $body = $assigment, $message = 'Assignment edited successfully');
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
            'grade_category' => 'exists:grade_categories,id',
        ]);
            $AssignmentLesson = AssignmentLesson::where('assignment_id', $request->assignment_id)->where('lesson_id', $request->lesson_id)->first();
            if (!isset($AssignmentLesson)) {
                return HelperController::api_response_format(400, $message = 'Assignment lesson not found');
            }
            if ($request->filled('is_graded'))
                $AssignmentLesson->is_graded = $request->is_graded;
            if ($request->filled('mark'))
                $AssignmentLesson->mark = $request->mark;
            if ($request->filled('grade_category'))
                $AssignmentLesson->grade_category = $request->grade_category;
            if ($request->filled('visible'))
                $AssignmentLesson->visible = $request->visible;
            if ($request->filled('allow_attachment'))
                $AssignmentLesson->allow_attachment = $request->allow_attachment;
            if ($request->filled('opening_date'))
                $AssignmentLesson->start_date = $request->opening_date;
            if ($request->filled('closing_date'))
                $AssignmentLesson->due_date = $request->closing_date;
            if ($request->filled('publish_date'))
                $AssignmentLesson->publish_date = $request->publish_date;
            $AssignmentLesson->save();

            $usersIDs = UserAssigment::where('assignment_lesson_id', $AssignmentLesson->id)->where('user_id','!=',Auth::user()->id)->pluck('user_id')->toArray();
            $lessonId = AssignmentLesson::where('assignment_id', $request->assignment_id)->pluck('lesson_id')->first();
            $courseSegment = Lesson::where('id', $request->lesson_id)->pluck('course_segment_id')->first();
            $courseID = CourseSegment::where('id', $courseSegment)->pluck('course_id')->first();
            $segmentClass = CourseSegment::where('id', $courseSegment)->pluck('segment_class_id')->first();
            $ClassLevel = SegmentClass::where('id', $segmentClass)->pluck('class_level_id')->first();
            $classId = ClassLevel::where('id', $ClassLevel)->pluck('class_id')->first();
            user::notify([
                'id' => $request->assignment_id,
                'message' => 'Assignment is updated',
                'from' => Auth::user()->id,
                'users' => $usersIDs,
                'course_id' => $courseID,
                'class_id' => $classId,
                'lesson_id' => $lessonId,
                'type' => 'assignment',
                'link' => url(route('getAssignment')) . '?assignment_id=' . $request->id,
                'publish_date' => $AssignmentLesson->start_date
            ]);
            // $all[] = Lesson::find($lesson_id)->module('Assigments', 'assignment')->get();
        $all = AssignmentLesson::all();

        return HelperController::api_response_format(200, $all, $message = 'Assignment edited successfully');
    }

    /*
        assign Assigment to users
    */
    public function assignAsstoUsers($request)
    {
        // $roles = Permission::where('name', 'assignment/submit')->first();
        // $roles_id = $roles->roles->pluck('id');
        // $usersIDs = Enroll::where('course_segment', $request['course_segment'])->whereIn('role_id', $roles_id)->where('user_id','!=',Auth::user()->id)->pluck('user_id')->toarray();
        $usersIDs = Enroll::where('course_segment', $request['course_segment'])->where('user_id','!=',Auth::user()->id)->pluck('user_id')->toarray();
        foreach ($usersIDs as $userId) {
            $userassigment = new UserAssigment;
            $userassigment->user_id = $userId;
            $userassigment->assignment_lesson_id = $request['assignment_lesson_id'];
            $userassigment->status_id = 2;
            $userassigment->override = 0;
            $userassigment->save();
        }
        $courseID = CourseSegment::where('id', $request['course_segment'])->pluck('course_id')->first();
        $classId = CourseSegment::find($request['course_segment'])->segmentClasses[0]->classLevel[0]->class_id;
        $lesson_id = AssignmentLesson::where('id',$request['assignment_lesson_id'])->pluck('lesson_id')->first();
        $assignment_id = AssignmentLesson::where('id',$request['assignment_lesson_id'])->pluck('assignment_id')->first();
        user::notify([
            'id' => $assignment_id,
            'message' => 'A new Assignment is added',
            'from' => Auth::user()->id,
            'users' => $usersIDs,
            'course_id' => $courseID,
            'class_id' => $classId,
            'lesson_id' => $lesson_id,
            'type' => 'assignment',
            'link' => url(route('getAssignment')) . '?assignment_id=' . $request["assignment_lesson_id"],
            'publish_date' => $request['publish_date']
        ]);
    }

    /*
        submit Assigment from user
    */
    public function submitAssigment(Request $request)
    {
        $request->validate([
            'assignment_id' => 'required|exists:assignment_lessons,assignment_id',
            'lesson_id' => 'required|exists:assignment_lessons,lesson_id',
        ]);
        $assigment = assignment::where('id', $request->assignment_id)->first();
        $assilesson = AssignmentLesson::where('assignment_id', $request->assignment_id)->where('lesson_id',$request->lesson_id)->first();
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
        $userassigment = UserAssigment::where('user_id', Auth::user()->id)->where('assignment_lesson_id', $assilesson->id)->first();

        if(!isset($userassigment))
            return HelperController::api_response_format(400, $body = [], $message = 'This user isn\'t assign to this assignment');

        if (isset($userassigment)) {
            if (((($assilesson->start_date >  Carbon::now()) || (Carbon::now() > $assilesson->due_date)) && ($userassigment->override == 0)) || ($userassigment->status_id == 1)) {
                return HelperController::api_response_format(400, $body = [], $message = 'sorry you are not allowed to submit anymore');
            }
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
        } 
        // else {
        //     $userassigment->attachment_id = null;
        // }
        
        if (isset($request->content)) {
            $userassigment->content = $request->content;
        } else {
            $userassigment->content = null;
        }

        $userassigment->submit_date = Carbon::now();
        $userassigment->save();
        return HelperController::api_response_format(200, $body = $userassigment, $message = 'Answer submitted successfully');
    }

    /*
        grade assigment
    */
    public function gradeAssigment(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:user_assigments,user_id',
            'assignment_id' => 'required|exists:assignment_lessons,assignment_id',
            'lesson_id' => 'required|exists:assignment_lessons,lesson_id',
            'grade' => 'required|integer',
            'feedback' => 'string'
        ]);
        $assilesson = AssignmentLesson::where('assignment_id', $request->assignment_id)->where('lesson_id',$request->lesson_id)->first(); 
        $userassigment = UserAssigment::where('user_id', $request->user_id)->where('assignment_lesson_id', $assilesson->id)->first();
        $assigment = assignment::where('id', $request->assignment_id)->first();
        // $assilesson = AssignmentLesson::where('assignment_id', $request->assignment_id)->where('lesson_id',$request->lesson_id)->first();
        if ($assilesson->mark < $request->grade) {
            return HelperController::api_response_format(400, $body = [], $message = 'please put grade less than ' . $assilesson->mark);
        }
        if (isset($request->feedback)) {
            $userassigment->feedback = $request->feedback;
        }
        $userassigment->grade = $request->grade;
        $userassigment->status_id = 1;
        $userassigment->save();
        return HelperController::api_response_format(200, $body = $userassigment, $message = 'Grade submitted successfully');
    }
    public function editGradeAssignment(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:user_assigments,user_id',
            'assignment_id' => 'required|exists:assignment_lessons,assignment_id',
            'lesson_id' => 'required|exists:assignment_lessons,lesson_id',
            'grade' => 'required|integer',
            'feedback' => 'string'
        ]);
        $assilesson = AssignmentLesson::where('assignment_id', $request->assignment_id)->where('lesson_id',$request->lesson_id)->first(); 
        $userassigment = UserAssigment::where('user_id', $request->user_id)->where('assignment_lesson_id', $assilesson->id)->first();
        if ($assilesson->mark < $request->grade) {
            return HelperController::api_response_format(400, $body = [], $message = 'please put grade less than ' . $assilesson->mark);
        }
        if (isset($request->feedback)) {
            $userassigment->feedback = $request->feedback;
        }
        $userassigment->grade = $request->grade;
        $userassigment->save();
        return HelperController::api_response_format(200, $body = $userassigment, $message = 'Grade edited successfully');
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
            return HelperController::api_response_format(200, $body =  $usersIDs, $message = 'Those users now can submit');
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
        $assigment = AssignmentLesson::where('assignment_id', $request->assignment_id)->where('lesson_id', $request->lesson_id)->first();
        if(!isset($assigment))
            return HelperController::api_response_format(400,null,'This assigment is not assigned to this lesson');
        $assigment->delete();
        $all = Lesson::find($request->lesson_id)->module('Assigments', 'assignment')->get();

        return HelperController::api_response_format(200, $all, $message = 'Assignment deleted successfully');
    }

    public function deleteAssignment(Request $request)
    {
        $request->validate([
            'assignment_id' => 'required|exists:assignments,id'
        ]);
        $assign = Assignment::where('id', $request->assignment_id);
        $assign->delete();

        return HelperController::api_response_format(200, Assignment::all(), $message = 'Assignment deleted successfully');
    }



    public function GetAssignment(Request $request)
    {
        $request->validate([
            'assignment_id' => 'required|exists:assignments,id',
            'lesson_id' => 'required|exists:assignment_lessons,lesson_id'
        ]);

        $user = Auth::user();
        
        $assignment = assignment::where('id', $request->assignment_id)->first();
        $assigLessonID = AssignmentLesson::where('assignment_id', $request->assignment_id)->where('lesson_id', $request->lesson_id)->first();
        if(!isset($assigLessonID))
            return HelperController::api_response_format(200, null, 'this assigment doesn\'t belong to this lesson');

        $userassigments = UserAssigment::where('assignment_lesson_id', $assigLessonID->id)->where('submit_date','!=',null)->get();
        
        if (count($userassigments) > 0) {
            $assignment['allow_edit'] = false;
        } else {
            $assignment['allow_edit'] = true;
        }
        ///////////////student
        if ($user->can('site/assignment/getAssignment')) {
            $assignment_lesson = Lesson::where('id',$request->lesson_id)->with(['AssignmentLesson'=> function($query)use ($request){
                $query->where('assignment_id', $request->assignment_id)->where('lesson_id', $request->lesson_id);}])->first();
                $assignment['lesson'] =  $assignment_lesson;
                if ($assignment_lesson->AssignmentLesson[0]->start_date > Carbon::now() || $assignment_lesson->AssignmentLesson[0]->due_date < Carbon::now()) {
                    if (isset($studentassigment->override) && $studentassigment->override == 0) {
                        return HelperController::api_response_format(400, $body = [], $message = 'you are not allowed to see the assignment at this moment');
                    }
                }
            $assigLessonID = AssignmentLesson::where('assignment_id', $request->assignment_id)->where('lesson_id', $request->lesson_id)->first();
            $studentassigment = UserAssigment::where('assignment_lesson_id', $assigLessonID->id)->where('user_id', $user->id)->first();
            // return $studentassigment->attachment_id;
            if(isset($studentassigment)){
            $assignment['user_submit'] =$studentassigment;
            if (isset($studentassigment->attachment_id)) {
                $assignment['user_submit']->attachment_id = attachment::where('id', $studentassigment->attachment_id)->first();
            }
            }
            $assignment['course_id'] = CourseSegment::where('id', $assignment_lesson->course_segment_id)->pluck('course_id')->first();
            return HelperController::api_response_format(200, $body = $assignment, $message = []);
            }
            ////////teacher
            if (!$user->can('site/assignment/getAssignment')) {
            $assignment_lesson = Lesson::where('id',$request->lesson_id)->with(['AssignmentLesson'=> function($query)use ($request){
            $query->where('assignment_id', $request->assignment_id)->where('lesson_id', $request->lesson_id);}])->first();
            $assignment['lesson'] =$assignment_lesson;
            $assigLessonID = AssignmentLesson::where('assignment_id', $request->assignment_id)->where('lesson_id', $request->lesson_id)->first();
            $assignment['class'] = Lesson::find($request->lesson_id)->courseSegment->segmentClasses[0]->classLevel[0]->class_id;

            $studentassigments = UserAssigment::where('assignment_lesson_id', $assigLessonID->id)->with('user')->get();
            foreach($studentassigments as $studentassigment){
            if (isset($studentassigment->attachment_id)) {
                $studentassigment->attachment_id = attachment::where('id', $studentassigment->attachment_id)->first();
            }
        }
            $assignment['user_submit'] = $studentassigments;
            $assignment['course_id'] = CourseSegment::where('id', $assignment_lesson->course_segment_id)->pluck('course_id')->first();
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

            $assigment = AssignmentLesson::where('assignment_id', $request->assignment_id)
                ->where('lesson_id', $request->lesson_id)->first();
            if (!isset($assigment)) {
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
            'lesson_id' => 'required|array',
            'lesson_id.*' => 'exists:lessons,id',
            'is_graded' => 'required|boolean',
            'mark' => 'required|integer',
            'allow_attachment' => 'required|integer|min:0|max:3',
            'publish_date' => 'required|date|date_format:Y-m-d H:i:s|before:closing_date',
            'opening_date' => 'required|date|date_format:Y-m-d H:i:s|before:closing_date',
            'closing_date' => 'date|date_format:Y-m-d H:i:s',
            'grade_category' => 'array|required_if:is_graded,==,1',
            'grade_category.*' => 'exists:grade_categories,id',
            'scale' => 'exists:scales,id',
            'visible' => 'boolean',
        ]);
        $assignmentLesson =AssignmentLesson::all();

        foreach($request->lesson_id as $key => $lesson){

            $assignment_lesson = new AssignmentLesson;
            $assignment_lesson->lesson_id = $lesson;
            $assignment_lesson->assignment_id = $request->assignment_id;
            $assignment_lesson->publish_date = $request->publish_date;
            $assignment_lesson->start_date = $request->opening_date;
            $assignment_lesson->mark = $request->mark;
            $assignment_lesson->is_graded = $request->is_graded;
            $assignment_lesson->allow_attachment = $request->allow_attachment;

            if ($request->filled('closing_date'))
                $assignment_lesson->due_date = $request->closing_date;
            if ($request->filled('scale'))
                $assignment_lesson->scale_id = $request->scale;
            if ($request->filled('visible'))
                $assignment_lesson->visible = $request->visible;
            if ($request->filled('grade_category'))
            {
                $lessonAll = Lesson::find($lesson);
                $gradeCats = $lessonAll->courseSegment->GradeCategory;
                $flag = false;
                foreach ($gradeCats as $grade){
                    if($grade->id == $request->grade_category[$key]){
                        $flag =true;
                    }
                }
                if($flag==false){
                    return HelperController::api_response_format(400, null,'this grade category invalid');
                }
                $assignment_lesson->grade_category = $request->grade_category[$key];
            }
            if($request->is_graded)
            { 
                $grade_category=GradeCategory::find($request->grade_category[$key]);
                $name_assignment = Assignment::find($request->assignment_id)->name;
                $grade_category->GradeItems()->create([
                    'grademin' => 0,
                    'grademax' => $request->mark,
                    'item_no' => 1,
                    'scale_id' => (isset($request->scale)) ? $request->scale : 1,
                    'grade_pass' => (isset($request->grade_to_pass)) ? $request->grade_to_pass : null,
                    'aggregationcoef' => (isset($request->aggregationcoef)) ? $request->aggregationcoef : null,
                    'aggregationcoef2' => (isset($request->aggregationcoef2)) ? $request->aggregationcoef2 : null,
                    'item_type' => (isset($request->item_type)) ? $request->item_type : null,
                    'name' => $name_assignment,
                    'weight' => 0,
                ]);
                $assignment_lesson->save();
                $assignment_lesson['grade_items']=$grade_category->GradeItems;
            }else{
            $assignment_lesson->save();
            }
            $assignmentLesson [] = $assignment_lesson;
            LessonComponent::create([
                'lesson_id' => $lesson,
                'comp_id' => $request->assignment_id,
                'module' => 'Assigments',
                'model' => 'assignment',
                'index' => LessonComponent::getNextIndex($lesson),
            ]);
            $lesson = Lesson::find($lesson);
            $data = array(
                "course_segment" => $lesson->course_segment_id,
                "assignment_lesson_id" => $assignment_lesson->id,
                "submit_date" => Carbon::now(),
                "publish_date" => $request->opening_date
            );
            $this->assignAsstoUsers($data);

        }
        // $all = AssignmentLesson::where('assignment_id','!=', $request->assignment_id)->get();

        return HelperController::api_response_format(200, $assignmentLesson, 'Assignment added successfully');
    }
}
