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
use App\GradeItems;
use App\Classes;
use App\Http\Controllers\Controller;
use App\UserGrade;
use Spatie\Permission\Models\Permission;
use URL;
use Spatie\PdfToImage\Pdf;
use Org_Heigl\Ghostscript\Ghostscript;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
// use Illuminate\Routing\Controller;
use App\Http\Controllers\HelperController;
use Carbon\Carbon;
use Modules\Assigments\Entities\assignment;
use Modules\Assigments\Entities\AssignmentLesson;
use Modules\Assigments\Entities\UserAssigment;
use Modules\Assigments\Entities\assignmentOverride;
use App\Component;
use App\LessonComponent;
use App\status;
use Illuminate\Support\Facades\Validator;
use App\Timeline;
use Modules\QuestionBank\Entities\QuizOverride;
use Modules\QuestionBank\Entities\QuizLesson;
use Modules\QuestionBank\Entities\Quiz;
use App\LastAction;


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
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'assignment/submit', 'title' => 'submit assignment']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'assignment/grade', 'title' => 'grade assignment']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'assignment/override', 'title' => 'override assignment']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'assignment/delete', 'title' => 'delete assignment']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'assignment/get', 'title' => 'get assignment']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'assignment/toggle', 'title' => 'toggle assignment']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'assignment/get-all', 'title' => 'get all assignments']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'assignment/assign', 'title' => 'Assign assignment to lesson']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'assignment/editgrade', 'title' => 'edit assignment\'s grades']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'site/assignment/assigned-users', 'title' => 'assign assignment to users']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'site/assignment/getAssignment', 'title' => 'get assignment for student']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'assignment/delete-assign-lesson', 'title' => 'Delete assigned lesson']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'assignment/assignment-override', 'title' => 'assignment override']);

        $teacher_permissions=['assignment/add','assignment/update','assignment/update-assignemnt-lesson','assignment/delete-assign-lesson',
        'assignment/grade','assignment/override','assignment/delete','assignment/get','assignment/toggle','assignment/editgrade','site/assignment/assigned-users',
        'assignment/assignment-override','assignment/assign'];
        $tecaher = \Spatie\Permission\Models\Role::find(4);
        $tecaher->givePermissionTo(\Spatie\Permission\Models\Permission::whereIn('name', $teacher_permissions)->get());

        $student_permissions=['assignment/submit','assignment/get','site/assignment/getAssignment'];
        $student = \Spatie\Permission\Models\Role::find(3);
        $parent = \Spatie\Permission\Models\Role::find(7);

        $student->givePermissionTo(\Spatie\Permission\Models\Permission::whereIn('name', $student_permissions)->get());
        $parent->givePermissionTo(\Spatie\Permission\Models\Permission::whereIn('name', $student_permissions)->get());
        
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
        $role->givePermissionTo('assignment/assign');
        $role->givePermissionTo('assignment/editgrade');
        $role->givePermissionTo('site/assignment/assigned-users');
        $role->givePermissionTo('assignment/delete-assign-lesson');
        $role->givePermissionTo('assignment/assignment-override');

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

    // not used
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
        return HelperController::api_response_format(200, $body = $assignment, $message = __('messages.assignment.add'));
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
            return HelperController::api_response_format(400, null, __('messages.assignment.cant_update'));

        if ($request->hasFile('file')) {

            $request->validate([
                'file' => 'file|distinct|mimes:pdf,docs,jpg,doc,docx,mp4,avi,flv,mpga,ogg,ogv,oga,jpg,jpeg,png,gif,xlsx,xls,csv,txt',
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

        return HelperController::api_response_format(200, $body = $assigment, $message = __('messages.assignment.update'));
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
            'updated_lesson_id' =>'nullable|exists:lessons,id'
        ]);
        $AssignmentLesson = AssignmentLesson::where('assignment_id', $request->assignment_id)->where('lesson_id', $request->lesson_id)->first();
        if (!isset($AssignmentLesson)) {
            return HelperController::api_response_format(400,[], $message = __('messages.error.not_found'));
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
        $lesson=Lesson::find($request->lesson_id);
        LastAction::lastActionInCourse($lesson->courseSegment->course_id);
        if (!$request->filled('updated_lesson_id')) {
            $request->updated_lesson_id= $request->lesson_id;
            }
        $AssignmentLesson->update([
            'lesson_id' => $request->updated_lesson_id
        ]);
        $AssignmentLesson->save();

        $usersIDs = UserAssigment::where('assignment_lesson_id', $AssignmentLesson->id)->where('user_id','!=',Auth::user()->id)->pluck('user_id')->toArray();
        $lessonId = AssignmentLesson::where('assignment_id', $request->assignment_id)->pluck('lesson_id')->first();
        $courseSegment = Lesson::where('id', $request->updated_lesson_id)->pluck('course_segment_id')->first();
        $courseID = CourseSegment::where('id', $courseSegment)->pluck('course_id')->first();
        $segmentClass = CourseSegment::where('id', $courseSegment)->pluck('segment_class_id')->first();
        $ClassLevel = SegmentClass::where('id', $segmentClass)->pluck('class_level_id')->first();
        $classId = ClassLevel::where('id', $ClassLevel)->pluck('class_id')->first();
        $assignment=Assignment::find($request->assignment_id);
        LastAction::lastActionInCourse($courseID);


        $publish_date=$AssignmentLesson->publish_date;
        if(carbon::parse($publish_date)->isPast())
            $publish_date=Carbon::now();
                
        user::notify([
            'id' => $request->assignment_id,
            'message' => $assignment->name .' assignment is updated',
            'from' => Auth::user()->id,
            'users' => $usersIDs,
            'course_id' => $courseID,
            'class_id' => $classId,
            'lesson_id' => $lessonId,
            'type' => 'assignment',
            'link' => url(route('getAssignment')) . '?assignment_id=' . $request->id,
            'publish_date' => Carbon::parse($publish_date)
        ]);
            // $all[] = Lesson::find($lesson_id)->module('Assigments', 'assignment')->get();
        $all = AssignmentLesson::all();

        return HelperController::api_response_format(200, $all, $message = __('messages.assignment.update'));
    }

    /*
        assign Assigment to users
    */
    public function assignAsstoUsers($request)
    {
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
        LastAction::lastActionInCourse($courseID);
        user::notify([
            'id' => $assignment_id,
            'message' => $request['assignment_name'].' assignment is added',
            'from' => Auth::user()->id,
            'users' => $usersIDs,
            'course_id' => $courseID,
            'class_id' => $classId,
            'lesson_id' => $lesson_id,
            'type' => 'assignment',
            'link' => url(route('getAssignment')) . '?assignment_id=' . $request["assignment_lesson_id"],
            'publish_date' => $request['publish_date'],
        ]);
    }

    /*
        submit Assigment from user
    */
    public function submitAssigment(Request $request)
    {
        $rules = [
            'assignment_id' => 'required|exists:assignment_lessons,assignment_id',
            'lesson_id' => 'required|exists:assignment_lessons,lesson_id',
            'file'=>'file|distinct|mimes:pdf'
        ];
        $customMessages = [
            'file.mimes' => 'please enter a pdf file.'
        ];
    
        $this->validate($request, $rules, $customMessages);
        $roles = Auth::user()->roles->pluck('name');
        if(in_array("Parent" , $roles->toArray()))
            return HelperController::api_response_format(400, null , $message = __('messages.error.parent_cannot_submit'));
        
        $lesson=Lesson::find($request->lesson_id);
        LastAction::lastActionInCourse($lesson->courseSegment->course_id);
        $assigment = assignment::where('id', $request->assignment_id)->first();
        $assilesson = AssignmentLesson::where('assignment_id', $request->assignment_id)->where('lesson_id',$request->lesson_id)->first();
        if(!isset($assilesson))
            return HelperController::api_response_format(200, null , $message = __('messages.assignment.assignment_not_belong'));

        $override = assignmentOverride::where('user_id',Auth::user()->id)->where('assignment_lesson_id',$assilesson->id)->first();
        /*
            0===================>content
            1===================>attached_file
            2===================>can submit content or file 
        */

        if ((($assilesson->allow_attachment == 2)) && ((!isset($request->content)) && (!isset($request->file)))) {
            return HelperController::api_response_format(400, $body = [], $message = __('messages.assignment.content_or_file'));
        }
        if ((($assilesson->allow_attachment == 0)) && ((!isset($request->content)) || (isset($request->file)))) {
            return HelperController::api_response_format(400, $body = [], $message = __('messages.assignment.content_only'));
        }

        if ((($assilesson->allow_attachment == 1)) && ((isset($request->content)) || (!isset($request->file)))) {
            return HelperController::api_response_format(400, $body = [], $message = __('messages.assignment.file_only'));
        }
        // if ((($assilesson->allow_attachment == 2)) && ((!isset($request->content)) || (!isset($request->file)))) { // both 
        //     return HelperController::api_response_format(400, $body = [], $message = 'you must enter both the content and the file');
        // }
        $userassigment = UserAssigment::where('user_id', Auth::user()->id)->where('assignment_lesson_id', $assilesson->id)->first();

        if(!isset($userassigment))
            return HelperController::api_response_format(400, $body = [], $message = __('messages.error.user_not_assign'));

        if (isset($userassigment)) {
            if($override != null){
                if (((($override->start_date >  Carbon::now()) || (Carbon::now() > $override->due_date)) && ($userassigment->override == 0)) || ($userassigment->status_id == 1)) {
                    return HelperController::api_response_format(400, $body = [], $message = __('messages.error.submit_limit'));
                }
            }else{
                if (((($assilesson->start_date >  Carbon::now()) || (Carbon::now() > $assilesson->due_date)) && ($userassigment->override == 0)) || ($userassigment->status_id == 1)) {
                    return HelperController::api_response_format(400, $body = [], $message = __('messages.error.submit_limit'));
                }
            }
        }

        if (isset($request->file)) {
            $request->validate([
                'file' => 'file|distinct|mimes:txt,pdf,docs,jpg,doc,docx,mp4,avi,flv,mpga,ogg,ogv,oga,jpg,jpeg,png,gif,mpeg,rtf,odt,TXT,xls,xlsx,ppt,pptx,zip,rar',
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
        return HelperController::api_response_format(200, $body = $userassigment, $message = __('messages.success.submit_success'));
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
            'feedback' => 'string',
            'corrected_file' => 'file|distinct|mimes:pdf',
        ]);
        $assilesson = AssignmentLesson::where('assignment_id', $request->assignment_id)->where('lesson_id',$request->lesson_id)->first(); 
        if(!isset($assilesson))
            return HelperController::api_response_format(200, null, $message = __('messages.assignment.assignment_not_belong'));
            
        $lesson=Lesson::find($request->lesson_id);
        LastAction::lastActionInCourse($lesson->courseSegment->course_id);

        $userassigment = UserAssigment::where('user_id', $request->user_id)->where('assignment_lesson_id', $assilesson->id)->first();
        // $assilesson = AssignmentLesson::where('assignment_id', $request->assignment_id)->where('lesson_id',$request->lesson_id)->first();
        if ($assilesson->mark < $request->grade) {
            return HelperController::api_response_format(400, $body = [], $message = __('messages.error.grade_less_than') . $assilesson->mark);
        }
        if (isset($request->feedback)) {
            $userassigment->feedback = $request->feedback;
        }
        if (isset($request->corrected_file)) {
            $userassigment->corrected_file = attachment::upload_attachment($request->corrected_file, 'assignment', null)->id;
        }
        $userassigment->grade = $request->grade;

        $assigment = assignment::where('id', $request->assignment_id)->first();
        $usergrade=UserGrade::where('user_id',$request->user_id)
                                ->where('grade_item_id',GradeItems::where('name',$assigment->name)->pluck('id')->first())
                                ->update(['final_grade' => $request->grade]);

        $userassigment->status_id = 1;
        $userassigment->save();
        return HelperController::api_response_format(200, $body = $userassigment, $message = __('messages.grade.graded'));
    }

    public function editGradeAssignment(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:user_assigments,user_id',
            'assignment_id' => 'required|exists:assignment_lessons,assignment_id',
            'lesson_id' => 'required|exists:assignment_lessons,lesson_id',
            'grade' => 'required|integer',
            'feedback' => 'string',
            'corrected_file' => 'file|distinct|mimes:pdf',
        ]);
        $assilesson = AssignmentLesson::where('assignment_id', $request->assignment_id)->where('lesson_id',$request->lesson_id)->first(); 
        $userassigment = UserAssigment::where('user_id', $request->user_id)->where('assignment_lesson_id', $assilesson->id)->first();
        if ($assilesson->mark < $request->grade) {
            return HelperController::api_response_format(400, $body = [], $message = __('messages.error.grade_less_than') . $assilesson->mark);
        }
        if (isset($request->feedback)) {
            $userassigment->feedback = $request->feedback;
        }
        if (isset($request->corrected_file)) {
            $userassigment->corrected_file = attachment::upload_attachment($request->corrected_file, 'assignment', null)->id;
        }
        $userassigment->grade = $request->grade;
        $lesson=Lesson::find($request->lesson_id);
        LastAction::lastActionInCourse($lesson->courseSegment->course_id);

        $assigment = assignment::where('id', $request->assignment_id)->first();
        $usergrade=UserGrade::where('user_id',$request->user_id)
                                ->where('grade_item_id',GradeItems::where('name',$assigment->name)->pluck('id')->first())
                                ->update(['final_grade' => $request->grade]);

        $userassigment->save();
        return HelperController::api_response_format(200, $body = $userassigment, $message = __('messages.grade.update'));
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
            $course_segment = CourseSegment::find($request->course_segment);
            LastAction::lastActionInCourse($course_segment->course_id);
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
            return HelperController::api_response_format(400,null,__('messages.assignment.assignment_not_belong'));
        
        $lesson=Lesson::find($request->lesson_id);
        LastAction::lastActionInCourse($lesson->courseSegment->course_id);
        
        $assigment->delete();
        $all = Lesson::find($request->lesson_id)->module('Assigments', 'assignment')->get();

        return HelperController::api_response_format(200, $all, $message = __('messages.assignment.delete'));
    }

    public function deleteAssignment(Request $request)
    {
        $request->validate([
            'assignment_id' => 'required|exists:assignments,id'
        ]);
        $assign = Assignment::where('id', $request->assignment_id)->first();
        $grade_item=GradeItems::where('item_Entity',$request->assignment_id)->where('item_type',2)->first();
        if(isset($grade_item))
            $grade_item->delete();
        $assign->delete();

        return HelperController::api_response_format(200, Assignment::all(), $message = __('messages.assignment.delete'));
    }

    public function GetAssignment(Request $request)
    {

        $rules = [
            'assignment_id' => 'required|exists:assignments,id',
            'lesson_id' => 'required|exists:assignment_lessons,lesson_id'        ];
        $customMessages = [
            'assignment_id.exists' => 'This assignment is invalid.' 
        ];

        $this->validate($request, $rules, $customMessages);
        $user = Auth::user();
        $lesson=Lesson::find($request->lesson_id);
        $class = $lesson->courseSegment->segmentClasses[0]->classLevel[0]->class_id;
        LastAction::lastActionInCourse($lesson->courseSegment->course_id);

        $assignment = assignment::where('id', $request->assignment_id)->first();
        $assigLessonID = AssignmentLesson::where('assignment_id', $request->assignment_id)->where('lesson_id', $request->lesson_id)->first();        
        if(!isset($assigLessonID))
            return HelperController::api_response_format(200, null, __('messages.assignment.assignment_not_belong'));

        if( $request->user()->can('site/course/student') && $assigLessonID->visible==0)
            return HelperController::api_response_format(301,null, __('messages.assignment.assignment_hidden'));
        $userassigments = UserAssigment::where('assignment_lesson_id', $assigLessonID->id)->where('submit_date','!=',null)->get();
        $override = assignmentOverride::where('user_id',Auth::user()->id)->where('assignment_lesson_id',$assigLessonID->id)->first();
        if (count($userassigments) > 0) {
            $assignment['allow_edit'] = false;
        } else {
            $assignment['allow_edit'] = true;
        }
           /////////////student
        if ($user->can('site/assignment/getAssignment')) {
            $assignment_lesson = Lesson::where('id',$request->lesson_id)->with(['AssignmentLesson'=> function($query)use ($request){
                $query->where('assignment_id', $request->assignment_id)->where('lesson_id', $request->lesson_id);
            }])->first();
                
            if($override != null){
                $assignment_lesson->AssignmentLesson[0]->start_date = $override->start_date;
                $assignment_lesson->AssignmentLesson[0]->due_date = $override->due_date;
            }
            $assignment['lesson'] =  $assignment_lesson;
            $assignment['course_id'] = CourseSegment::where('id', $assignment_lesson->course_segment_id)->pluck('course_id')->first();
            $assignment['class'] = Lesson::find($request->lesson_id)->courseSegment->segmentClasses[0]->classLevel[0]->class_id;
            $start = $assignment_lesson->AssignmentLesson[0]->start_date;
            $due = $assignment_lesson->AssignmentLesson[0]->due_date;
            if ($assignment_lesson->AssignmentLesson[0]->start_date > Carbon::now() || $assignment_lesson->AssignmentLesson[0]->due_date < Carbon::now()) {
                if (isset($studentassigment->override) && $studentassigment->override == 0) {
                    return HelperController::api_response_format(400, $body = [], $message = __('messages.error.not_available_now'));
                }
            }
            $assigLessonID = AssignmentLesson::where('assignment_id', $request->assignment_id)->where('lesson_id', $request->lesson_id)->first();
            $assignment['user_submit']=null;
            $studentassigment = UserAssigment::where('assignment_lesson_id', $assigLessonID->id)->where('user_id', $user->id)->first();
            if(isset($studentassigment)){
                $assignment['user_submit'] =$studentassigment;
                $usr=User::find($studentassigment->user_id);
                if(isset($usr->attachment))
                    $usr->picture=$usr->attachment->path;
                $assignment['user_submit']->User=$usr;
                if (isset($studentassigment->attachment_id)) {
                    $assignment['user_submit']->attachment_id = attachment::where('id', $studentassigment->attachment_id)->first();
                }
                if (isset($studentassigment->corrected_file)) {
                    $assignment['corrected_file'] = attachment::where('id', $studentassigment->corrected_file)->first();
                }
            }
            if($start > Carbon::now() && $request->user()->can('site/course/student'))
                $assignment['started'] = false;
            else
                $assignment['started'] = true;

            if($due > Carbon::now() && $request->user()->can('site/course/student'))
                $assignment['ended'] = false;
            else
                $assignment['ended'] = true;

            return HelperController::api_response_format(200, $body = $assignment, $message = []);
        }
            ////////teacher
        if (!$user->can('site/assignment/getAssignment')) {
            $assignment_lesson = Lesson::where('id',$request->lesson_id)->with(['AssignmentLesson'=> function($query)use ($request){
                $query->where('assignment_id', $request->assignment_id)->where('lesson_id', $request->lesson_id);
            }])->first();
            $start = $assignment_lesson->AssignmentLesson[0]->start_date;
            $due = $assignment_lesson->AssignmentLesson[0]->due_date;
            $assignment['lesson'] =$assignment_lesson;
            $assignment['course_id'] = CourseSegment::where('id', $assignment_lesson->course_segment_id)->pluck('course_id')->first();
            $assignment['class'] = Lesson::find($request->lesson_id)->courseSegment->segmentClasses[0]->classLevel[0]->class_id;

            $assigLessonID = AssignmentLesson::where('assignment_id', $request->assignment_id)->where('lesson_id', $request->lesson_id)->first();
            $userassigments = UserAssigment::where('assignment_lesson_id', $assigLessonID->id)->with('user')->get();
            foreach($userassigments as $userAssignment)
            {
                if(isset($userAssignment->user)){
                    if ($userAssignment->user->can('site/course/student')) {
                        $studentassigments[]=$userAssignment;
                    }
                }
            }
            $images_path=collect([]);
            $assignment['user_submit'] = [];
            if(isset($studentassigments)){
                foreach($studentassigments as $studentassigment){
                    if(isset($studentassigment->user->attachment))
                        $studentassigment->user->picture=$studentassigment->user->attachment->path;
    
                    if (isset($studentassigment->attachment_id)) {
                        $studentassigment->attachment_id = attachment::where('id', $studentassigment->attachment_id)->first();
                    }
                    if (isset($studentassigment->corrected_file)) {
                        $studentassigment->corrected_file = attachment::where('id', $studentassigment->corrected_file)->first();
                    }
                }
                $assignment['user_submit'] = $studentassigments;
            }
            
            if($start > Carbon::now())
                $assignment['started'] = false;
            else
                $assignment['started'] = true;
            
            if($due > Carbon::now())
                $assignment['ended'] = false;
            else
                $assignment['ended'] = true;
            // return  $images_path;
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
                return HelperController::api_response_format(400, null, __('messages.error.not_found'));
            }

            $assigment->visible = ($assigment->visible == 1) ? 0 : 1;
            $assigment->save();
            $lesson=Lesson::find($request->lesson_id);
            LastAction::lastActionInCourse($lesson->courseSegment->course_id);

            return HelperController::api_response_format(200, $assigment, __('messages.success.toggle'));
        } catch (Exception $ex) {
            return HelperController::api_response_format(400, null,__('messages.error.try_again'));
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
            'closing_date' => 'date|date_format:Y-m-d H:i:s|after:' . Carbon::now(),
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
            $lesson_obj = Lesson::find($lesson);
            $course_segment =  CourseSegment::find($lesson_obj->course_segment_id);
            if( $request->filled('closing_date') && $course_segment->end_date < Carbon::parse($request->closing_date) )
                return HelperController::api_response_format(400, null ,  __('messages.date.end_before').$course_segment->end_date);
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
                    return HelperController::api_response_format(400, null, __('messages.error.data_invalid'));
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
                    'item_type' => 2, // Assignment
                    'item_Entity' => $request->assignment_id,
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
                "publish_date" => Carbon::parse($request->publish_date),
                "assignment_name" => Assignment::find($request->assignment_id)->name
            );
            LastAction::lastActionInCourse($lesson->courseSegment->course_id);
            $this->assignAsstoUsers($data);

        }
        // $all = AssignmentLesson::where('assignment_id','!=', $request->assignment_id)->get();

        return HelperController::api_response_format(200, $assignmentLesson, __('messages.assignment.add'));
    }

    public function overrideAssignment(Request $request)
    {
        $request->validate([
            'user_id' => 'required|array',
            'user_id.*' => 'exists:user_assigments,user_id',
            'assignment_id' => 'required|exists:assignment_lessons,assignment_id',
            'lesson_id' => 'required|exists:assignment_lessons,lesson_id',
            'start_date' => 'required|before:due_date',
            'due_date' => 'required|after:' . Carbon::now(),
        ]);

        $assigmentlesson = AssignmentLesson::where('assignment_id', $request->assignment_id)->where('lesson_id', $request->lesson_id)->pluck('id')->first();
        if(!isset($assigmentlesson))
            return HelperController::api_response_format(200, null , __('messages.assignment.assignment_not_belong'));
        $assignment = AssignmentLesson::find($assigmentlesson);
        $lesson = Lesson::find($assignment->lesson_id);
        $course_segment = $lesson->courseSegment;
        if($course_segment->end_date < Carbon::parse($request->due_date))
            return HelperController::api_response_format(400, null , __('messages.date.end_before').$course_segment->end_date);
      
        foreach($request->user_id as $user)
        {
            $assignmentOerride[] = assignmentOverride::firstOrCreate([
                'user_id' => $user,
                'assignment_lesson_id' => $assigmentlesson,
                'start_date' =>  $request->start_date,
                'due_date' => $request->due_date,
            ]);
        }
        $course = $lesson->courseSegment->course_id;
        LastAction::lastActionInCourse($course);
        $class = $lesson->courseSegment->segmentClasses[0]->classLevel[0]->class_id;
        $assignment_name = Assignment::find($request->assignment_id)->name;
        user::notify([
            'id' => $assignment->assignment_id,
            'message' => 'You can answer '.$assignment_name.' assignment now',
            'from' => Auth::user()->id,
            'users' => $request->user_id,
            'course_id' => $course,
            'class_id' => $class,
            'lesson_id' => $assignment->lesson_id,
            'type' => 'assignment',
            'link' => url(route('getAssignment')) . '?assignment_id=' . $assignment->assignment_id,
            'publish_date' => Carbon::parse($request->start_date),
        ]);
        return HelperController::api_response_format(200, $assignmentOerride, __('messages.assignment.override'));
    }
    public function AnnotatePDF(Request $request)
    {
        $request->validate([
            'attachment_id' => 'integer|required|exists:attachments,id', //because this file may not be assigned to user "corrected_file" 
            // 'attachment_id' => 'integer|required|exists:user_assigments,attachment_id',
            ]);
        $images_path=collect([]);
        $attachmnet=attachment::find($request->attachment_id);
        $inputFile=$attachmnet->getOriginal('path');//storage_path() . str_replace('/', '/', $studentassigment->attachment_id->getOriginal('path'));
        // Ghostscript::setGsPath("/usr/bin/gs");
        $pdf = new Pdf("storage/".$inputFile);
        // return $pdf;
        foreach (range(1, $pdf->getNumberOfPages()) as $pageNumber) {
            $name= uniqid();
            $pdf->setOutputFormat('png')->setPage($pageNumber)->saveImage('storage/assignment/'.$name);
            $b64image = base64_encode(file_get_contents( url(Storage::url('assignment/'.$name))));
            $images_path->push('data:image/png;base64,'.$b64image);
        }
        return HelperController::api_response_format(200, $images_path, 'Here pdf\'s images');
    }

    public function overwriteScript(){

        $overwrites=collect();

        $assignments_overwrite = assignmentOverride::get();

        foreach($assignments_overwrite as $overwrite){
            $assignmentLesson = AssignmentLesson::whereId($overwrite->assignment_lesson_id)->first();
            $check = Timeline::where('item_id', $assignmentLesson->assignment_id)
                            ->where('lesson_id',$assignmentLesson->lesson_id)
                            ->where('type','assignment')
                            ->where('overwrite_user_id',$overwrite->user_id)->first();
            if(isset($assignmentLesson) && !isset($check)){
                $assignment = Assignment::where('id',$assignmentLesson->assignment_id)->first();
                $lesson = Lesson::find($assignmentLesson->lesson_id);
                $course_id = $lesson->courseSegment->course_id;
                $class_id = $lesson->courseSegment->segmentClasses[0]->classLevel[0]->class_id;
                $level_id = $lesson->courseSegment->segmentClasses[0]->classLevel[0]->yearLevels[0]->level_id;
                if(isset($assignment) ){
                    $overwrites->push([
                        'item_id' => $assignmentLesson->assignment_id,
                        'name' => $assignment->name,
                        'start_date' => $overwrite->start_date,
                        'due_date' => $overwrite->due_date,
                        'publish_date' => isset($assignmentLesson->publish_date)? $assignmentLesson->publish_date : Carbon::now(),
                        'course_id' => $course_id,
                        'class_id' => $class_id,
                        'lesson_id' => $assignmentLesson->lesson_id,
                        'level_id' => $level_id,
                        'type' => 'assignment',
                        'overwrite_user_id' => $overwrite->user_id
                    ]);
                }
            }
        }

        $quiz_overwrite = QuizOverride::get();
        
        foreach($quiz_overwrite as $overwrite){
            $quizLesson = QuizLesson::whereId($overwrite->quiz_lesson_id)->first();
            $check = Timeline::where('item_id', $quizLesson->quiz_id)
                            ->where('lesson_id',$quizLesson->lesson_id)
                            ->where('type','quiz')
                            ->where('overwrite_user_id',$overwrite->user_id)->first();
            if(isset($quizLesson) && !isset($check)){
                $quiz = Quiz::where('id',$quizLesson->quiz_id)->first();
                $lesson = Lesson::find($quizLesson->lesson_id);
                $course_id = $lesson->courseSegment->course_id;
                $class_id = $lesson->courseSegment->segmentClasses[0]->classLevel[0]->class_id;
                $level_id = $lesson->courseSegment->segmentClasses[0]->classLevel[0]->yearLevels[0]->level_id;
                if(isset($quiz)){
                    $overwrites->push([
                        'item_id' => $quizLesson->quiz_id,
                        'name' => $quiz->name,
                        'start_date' => $overwrite->start_date,
                        'due_date' => $overwrite->due_date,
                        'publish_date' => isset($quizLesson->publish_date)? $quizLesson->publish_date : Carbon::now(),
                        'course_id' => $course_id,
                        'class_id' => $class_id,
                        'lesson_id' => $quizLesson->lesson_id,
                        'level_id' => $level_id,
                        'type' => 'quiz',
                        'overwrite_user_id' => $overwrite->user_id
                    ]);
                }
            }
        }

        $overwrites = $overwrites->sortBy('publish_date')->values();
        Timeline::insert($overwrites->toArray());
        if(count($overwrites->toArray())==0)
            return response()->json(['message' => 'all overwrites is assigned before ', 'body' => $overwrites], 200);

        return response()->json(['message' => 'all overwrites is assigned', 'body' => $overwrites], 200);
    }
}
