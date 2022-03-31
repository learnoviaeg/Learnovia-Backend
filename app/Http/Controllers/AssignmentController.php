<?php

namespace App\Http\Controllers;

use App\attachment;
use Illuminate\Http\Request;
use App\Repositories\ChainRepositoryInterface;
use App\Enroll;
use Illuminate\Support\Facades\Auth;
use App\Lesson;
use App\Timeline;
use App\Level;
use App\Notifications\AssignmentNotification;
use App\Course;
use App\Segment;
use App\Classes;
use Modules\Assigments\Entities\AssignmentLesson;
use Modules\Assigments\Entities\assignment;
use Modules\Assigments\Entities\UserAssigment;
use Modules\Assigments\Entities\assignmentOverride;
use App\Paginate;
use App\Helpers\CoursesHelper;
use App\Events\AssignmentCreatedEvent;
use App\LastAction;
use Carbon\Carbon;
use App\SecondaryChain;
use App\GradeCategory;
use App\GradeItems;
use App\Repositories\SettingsReposiotryInterface;
use App\Events\GraderSetupEvent;
use Illuminate\Database\Eloquent\Builder;

class AssignmentController extends Controller
{
    public function __construct(ChainRepositoryInterface $chain,SettingsReposiotryInterface $setting)
    {
        $this->chain = $chain;
        $this->setting = $setting;

        $this->middleware('auth');
        $this->middleware(['permission:assignment/get', 'ParentCheck'],   ['only' => ['index','show']]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request,$count = null)
    {
        $request->validate([
            'year' => 'exists:academic_years,id',
            'type' => 'exists:academic_types,id',
            'level' => 'exists:levels,id',
            'segment' => 'exists:segments,id',
            'courses'    => 'nullable|array',
            'courses.*'  => 'nullable|integer|exists:courses,id',
            'class' => 'nullable|integer|exists:classes,id',
            'lesson' => 'nullable|integer|exists:lessons,id',
            'sort_in' => 'in:asc,desc',
        ]);

        $enrolls = $this->chain->getEnrollsByChain($request)->where('user_id',Auth::id())->get()->pluck('id');
        $lessons = SecondaryChain::whereIn('enroll_id', $enrolls)->where('user_id',Auth::id())->get()->pluck('lesson_id')->unique();
        if($request->filled('lesson')){
            if (!in_array($request->lesson,$lessons->toArray()))
                return response()->json(['message' => __('messages.error.no_active_for_lesson'), 'body' => []], 400);

            $lessons  = [$request->lesson];
        }

        $sort_in = 'desc';
        if($request->has('sort_in'))
            $sort_in = $request->sort_in;

        $assignment_lessons = AssignmentLesson::whereIn('lesson_id',$lessons)->with('Assignment');

        if($request->user()->can('site/course/student')){
            $assignment_lessons
            ->where('visible',1)
            ->where('publish_date' ,'<=', Carbon::now())
            ->whereHas('Assignment',function($q){
                $q->where(function($query) {                //Where accessible
                        $query->doesntHave('courseItem')
                                ->orWhereHas('courseItem.courseItemUsers', function (Builder $query){
                                    $query->where('user_id', Auth::id());
                                });
                    });
            });
        }

        if($count == 'count'){

            return response()->json(['message' => __('messages.assignment.count'), 'body' => $assignment_lessons->count()], 200);
        }

        $assignment_lessons = $assignment_lessons->get();

        $assignments = collect([]);
        foreach($assignment_lessons as $assignment_lesson){
            $assignment=assignment::whereId($assignment_lesson->assignment_id)->with('assignmentLesson')->first();
            $lessonn = Lesson::find($assignment_lesson->lesson_id);
            $classesIDS = SecondaryChain::select('group_id')->distinct()->where('lesson_id',$lessonn->id)->pluck('group_id');
            $classes = Classes::whereIn('id',$classesIDS)->get();
            $assignment['lesson'] = $lessonn;
            $assignment['class'] = $classes;
            $assignment['course'] = Course::whereId($lessonn->course_id)->first();
            $assignment['level'] = level::find($assignment['course']->level_id);
            $assignments[]=$assignment;
        }

        return response()->json(['message' => __('messages.assignment.list'), 'body' => $assignments->sortByDesc('created_at')->paginate(Paginate::GetPaginate($request))], 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $settings = $this->setting->get_value('create_assignment_extensions');

        $rules = [
            'name' => 'required|string',
            'content' => 'string|required_without:file',
            'file' => 'file|distinct|required_without:content|mimes:'.$settings,
            //assignment_lesson
            'lesson_id' => 'required|array',
            'lesson_id.*' => 'exists:lessons,id',
            'is_graded' => 'required|boolean',
            'mark' => 'required|numeric|min:0',
            'allow_attachment' => 'required|integer|min:0|max:3',
            'publish_date' => 'date|date_format:Y-m-d H:i:s|before:closing_date',
            'opening_date' => 'required|date|date_format:Y-m-d H:i:s|before:closing_date',
            'closing_date' => 'date|date_format:Y-m-d H:i:s|after:' . Carbon::now(),
            'grade_category' => 'required_if:is_graded,==,1|exists:grade_categories,id',
            'allow_edit_answer' => 'boolean',
            'scale' => 'exists:scales,id',
            'visible' => 'required|boolean',
            'users_ids' => 'array',
            'users_ids.*' => 'exists:users,id'
        ];

        $customMessages = [
            'file.mimes' => __('messages.error.extension_error')
        ];

        $this->validate($request, $rules, $customMessages);

        $assignment = Assignment::firstOrCreate([
            'name' => $request->name,
            'attachment_id' => ($request->hasFile('file')) ? attachment::upload_attachment($request->file, 'assignment', null)->id : null,
            'content' => isset($request->content) ? $request->content : null,
            'created_by' => Auth::id(),
        ]);

        foreach($request->lesson_id as $key => $lesson){

            $assignment_lesson = AssignmentLesson::firstOrCreate([
                'lesson_id' => $lesson,
                'assignment_id' => $assignment->id,
                'publish_date' => isset($request->publish_date) ? $request->publish_date : $request->opening_date,
                'due_date' => isset($request->closing_date) ? $request->closing_date : null,
                'allow_edit_answer' => isset($request->allow_edit_answer) ? $request->allow_edit_answer : 0,
                'scale_id' => isset($request->scale) ? $request->scale : null,
                'visible' => $request->visible,
                // 'grade_category' => isset($request->grade_category) ? $request->grade_category : $pp->id,
                'is_graded' => $request->is_graded,
                'start_date' => $request->opening_date,
                'mark' => $request->mark,
                'is_graded' => $request->is_graded,
                'allow_attachment' => $request->allow_attachment,
            ]);

            $lesson_obj = Lesson::find($lesson);
            $pp=GradeCategory::where('course_id',$lesson_obj->course->id)->whereNull('parent')->first();
            $assignment_lesson->grade_category=$pp->id;

            $secondary_chains = SecondaryChain::where('lesson_id',$lesson_obj->id)->get()->keyBy('group_id');
            foreach($secondary_chains as $secondary_chain){
                $segment = Segment::find($secondary_chain->Enroll->segment);
                if( $request->filled('closing_date') && $segment->end_date < Carbon::parse($request->closing_date))
                    return HelperController::api_response_format(400, null ,  __('messages.date.end_before').$segment->end_date);
            }

            if($request->is_graded)
            {
                GradeItems::create([
                    'grade_category_id' => $request->grade_category,
                    'grademin' => 0,
                    'grademax' => $request->mark,
                    'item_no' => 1,
                    'scale_id' => (isset($request->scale)) ? $request->scale : 1,
                    'grade_pass' => (isset($request->grade_to_pass)) ? $request->grade_to_pass : null,
                    'aggregationcoef' => (isset($request->aggregationcoef)) ? $request->aggregationcoef : null,
                    'aggregationcoef2' => (isset($request->aggregationcoef2)) ? $request->aggregationcoef2 : null,
                    'type' => 'Assignment',
                    'item_Entity' => $assignment->id,
                    'name' => $assignment->name,
                    'weight' => 0,
                ]);
            }

            if(isset($request->users_ids)){
                CoursesHelper::giveUsersAccessToViewCourseItem($assignment->id, 'assignment', $request->users_ids);
                Assignment::where('id',$assignment->id)->update(['restricted' => 1]);
            }

            LastAction::lastActionInCourse($assignment_lesson->lesson->course_id);

            //sending notifications
            $notification = new AssignmentNotification($assignment_lesson, $assignment->name.' assignment is added');
            $notification->send();

            ///create grade category for assignment
            event(new AssignmentCreatedEvent($assignment_lesson));

            $assignment_lesson->save();
        }

        return HelperController::api_response_format(200, $body = $assignment, $message = __('messages.assignment.add'));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($assignment_id,$lesson_id)
    {
        $user = Auth::user();

        $assigLessonID = AssignmentLesson::where('assignment_id', $assignment_id)->where('lesson_id', $lesson_id)->first();
        if(!isset($assigLessonID))
            return response()->json(['message' => __('messages.assignment.assignment_not_belong'), 'body' => [] ], 400);

        $assignment = assignment::where('id',$assignment_id)->with('assignmentLesson','courseItem.courseItemUsers')->first();
        if(!isset($assignment))
            return response()->json(['message' => __('messages.error.not_found'), 'body' => [] ], 400);

        $lesson_drag = Lesson::find($lesson_id);
        LastAction::lastActionInCourse($lesson_drag->course_id);

        $assignment['started'] = false;
        $assignment['allow_edit'] = true;
        if(Carbon::parse($assigLessonID->start_date) < Carbon::now())
            $assignment['started'] = true;

        $ifStudent=[];
        $users = UserAssigment::where('assignment_lesson_id', $assigLessonID->id)->where('submit_date', '!=', null)->pluck('user_id');
        if(isset($users))
            $ifStudent=Enroll::whereIn('user_id',$users)->where('role_id',3)->get();

        if(count($ifStudent) > 0)
            $assignment['allow_edit'] = false;

        $assignment['user_submit']=null;
        $assignment['visible'] = $assigLessonID->visible;
          /////////////student
        if ($user->can('site/assignment/getAssignment')) {
            $studentassigment = UserAssigment::where('assignment_lesson_id', $assigLessonID->id)->where('user_id', $user->id)->first();
            if(isset($studentassigment)){
                $assignment['user_submit'] =$studentassigment;
            }
        }

        return response()->json(['message' => __('messages.assignment.assignment_object'), 'body' => $assignment], 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'string',
            'file_description' => 'string',
            'content'  => 'string',
            'is_graded' => 'boolean',
            'mark' => 'numeric|min:0',
            'lesson_id' => 'required|integer|exists:lessons,id',
            //allow attachement
                // 0===================>content
                // 1===================>attached_file
                // 2===================>can submit content or file
            'allow_attachment' => 'integer|min:0|max:3',
            'opening_date' => 'date |date_format:Y-m-d H:i:s|before:closing_date',
            'closing_date' => 'date |date_format:Y-m-d H:i:s',
            'visible' => 'boolean',
            'publish_date' => 'date |date_format:Y-m-d H:i:s|before:closing_date',
            'grade_category' => 'exists:grade_categories,id',
            'updated_lesson_id' =>'nullable|exists:lessons,id'
        ]);

        if ($request->hasFile('file')) {

            $settings = $this->setting->get_value('create_assignment_extensions');

            $request->validate([
                'file' => 'file|distinct|mimes:'.$settings,
            ]);
        }

        $assignment = assignment::find($id);

        $assigmentLesson = AssignmentLesson::where('lesson_id',$request->lesson_id)->where('assignment_id',$id)->first();
        LastAction::lastActionInCourse($assigmentLesson->Lesson->course_id);
        
        if(!Carbon::parse($assigmentLesson->start_date) < Carbon::now())
        {
            $assigmentLesson->update([
                'start_date' => isset($request->opening_date) ? $request->opening_date : $assigmentLesson->start_date,
                'publish_date' => isset($request->publish_date) ? $request->publish_date : $assigmentLesson->publish_date,
            ]);
        }

        $assigmentLesson->update([
            'due_date' => isset($request->closing_date) ? $request->closing_date : $assigmentLesson->due_date,
            'visible' => isset($request->visible) ? $request->visible : $assigmentLesson->visible,
            'allow_edit_answer' => isset($request->allow_edit_answer) ? $request->allow_edit_answer : $assigmentLesson->allow_edit_answer,
        ]);
        
        $ifStudent=[];
        $users = UserAssigment::where('assignment_lesson_id', $assigmentLesson->id)->where('submit_date', '!=', null)->pluck('user_id');
        if(isset($users))
            $ifStudent=Enroll::whereIn('user_id',$users)->where('role_id',3)->get();

        if(isset($request->updated_lesson_id) && $request->updated_lesson_id !=$request->lesson_id && $ifStudent > 0)
            return HelperController::api_response_format(400,[], $message = __('messages.error.not_allowed_to_edit'));

        if (count($ifStudent) >= 0)
        {
            if(isset($request->allow_attachment) && $request->allow_attachment == 3)
            $assigmentLesson->update([
                'allow_attachment' => isset($request->allow_attachment) ? $request->allow_attachment : $assigmentLesson->allow_attachment,
            ]);
        }
        if (count($ifStudent) <= 0){
            $description = (isset($request->file_description))? $request->file_description :null;

            $assignment->update([
                'content' => isset($request->content) ? $request->content : $assignment->content,
                'name' => isset($request->name) ? $request->name : $assignment->name,
                'attachment_id' => $request->hasFile('file') ? attachment::upload_attachment($request->file, 'assignment', $description)->id : null,
            ]);
            $assigmentLesson->update([
                'mark' => isset($request->mark) ? $request->mark : $assigmentLesson->mark,
                'is_graded' => isset($request->is_graded) ? $request->is_graded : $assigmentLesson->is_graded,
                'grade_category' => isset($request->grade_category) ? $request->grade_category : $assigmentLesson->grade_category,
                'lesson_id' => isset($request->updated_lesson_id) ? $request->updated_lesson_id : $assigmentLesson->lesson_id,
                'allow_attachment' => isset($request->allow_attachment) ? $request->allow_attachment : $assigmentLesson->allow_attachment,
            ]);
        }

        // if($request->file == 'No_file')
        //     $assignment->attachment_id=null;

        $assignment->save();

        if ($request->filled('allow_attachment') )
            $assigmentLesson->allow_attachment = $request->allow_attachment;

        $assigmentLesson->save();
        // $usersIDs = SecondaryChain::select('user_id')->distinct()->where('role_id',3)->where('lesson_id',$assigmentLesson->lesson_id)->pluck('user_id');
        // if ($request->filled('updated_lesson_id') && $request->updated_lesson_id !=$request->lesson_id ) {
        //     $old_students = UserAssigment::where('assignment_lesson_id', $assigmentLesson->id)->delete();
        //     foreach ($usersIDs as $userId) {
        //         $userassigment = new UserAssigment;
        //         $userassigment->user_id = $userId;
        //         $userassigment->assignment_lesson_id = $assigmentLesson->id;
        //         $userassigment->status_id = 2;
        //         $userassigment->override = 0;
        //         $userassigment->save();
        //     }
        // }

        $AssignmentLesson = AssignmentLesson::where('assignment_id', $id)->where('lesson_id', $request->lesson_id)->first();

        $assignment_category = GradeCategory::where('lesson_id', $AssignmentLesson->lesson_id)->where('instance_id' , $AssignmentLesson->assignment_id)
                                ->where('item_type' , 'Assignment')->where('instance_type' , 'Assignment')->where('type','item');
        $parent=$assignment_category->first()->Parents;

        //update assignment category
        if($assignment_category->count() > 0 )
            $assignment_category->update([
                'lesson_id' => $request->updated_lesson_id
            ]);

        ///create grade category for assignment
        event(new AssignmentCreatedEvent($AssignmentLesson));

        $userGradesJob = (new \App\Jobs\RefreshUserGrades($this->chain , $parent));
        dispatch($userGradesJob);

        return HelperController::api_response_format(200, null, $message = __('messages.assignment.update'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id, Request $request)
    {
        $request->validate([
            'lesson_id' => 'required|exists:assignment_lessons,lesson_id'
        ]);
        $assigment = AssignmentLesson::where('assignment_id', $id)->where('lesson_id', $request->lesson_id)->first();
        if(!isset($assigment))
            return HelperController::api_response_format(400,null,__('messages.assignment.assignment_not_belong'));

        Timeline::where('item_id',$id)->where('type','assignment')->where('lesson_id',$request->lesson_id)->delete();

        $lesson=Lesson::find($request->lesson_id);
        LastAction::lastActionInCourse($lesson->course_id);

        $assigment->delete();

        $assign = Assignment::where('id', $id)->first();
        $assign->delete();

        $grade_category = GradeCategory::where('instance_id', $id)->where('item_type', 'Assignment')->where('instance_type', 'Assignment')
        ->where('type' , 'item')->where('lesson_id', $request->lesson_id);

        if($grade_category->count() > 0){
            $parent = GradeCategory::find($grade_category->first()->parent);
            $grade_category->delete();
            event(new GraderSetupEvent($parent));
        }

        $all = Lesson::find($request->lesson_id)->module('Assigments', 'assignment')->get();

        return HelperController::api_response_format(200, $all, $message = __('messages.assignment.delete'));
    }

    public function getAssignmentAssignedUsers(Request $request){

        $request->validate([
            'id' => 'required|exists:assignments,id',
        ]);

        $assignment = Assignment::with(['Lesson', 'courseItem.courseItemUsers'])->find($request->id);

        foreach($assignment->Lesson as $lesson){
            if($lesson->shared_lesson ==1)
                $result['assignment_classes']= $lesson->shared_classes->pluck('id');
            else
                $result['assignment_classes'][]= $lesson->shared_classes->pluck('id')->first();
        }

        $result['restricted'] = $assignment->restricted;
        if(isset($assignment['courseItem'])){

            $courseItemUsers = $assignment['courseItem']->courseItemUsers;
            foreach($courseItemUsers as $user)
                $result['assigned_users'][] = $user->user_id;
        }

        return response()->json($result, 200);
    }

    public function editAssignmentAssignedUsers(Request $request){
        $request->validate([
            'id' => 'required|exists:assignments,id',
            'users_ids' => 'array',
            'users_ids.*' => 'exists:users,id'
        ]);

        $assignment= Assignment::find($request->id);
        
        $assignment->restricted=1;
        if(!isset($request->users_ids))
            $assignment->restricted=0;
        
        $assignment->save();

        CoursesHelper::updateCourseItem($request->id, 'assignment', $request->users_ids);
        return response()->json(['message' => 'Updated successfully'], 200);
    }
}
