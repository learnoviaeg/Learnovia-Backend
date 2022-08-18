<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Resources\CourseResource;
use Illuminate\Support\Facades\Auth;
use App\Repositories\ChainRepositoryInterface;
use Carbon\Carbon;
use App\Course;
use App\attachment;
use App\LastAction;
use App\User;
use App\GradeCategory;
use App\Segment;
use App\Classes;
use App\SecondaryChain;
use Modules\QuestionBank\Entities\QuestionsCategory;
use App\Lesson;
use App\Enroll;
use DB;
use App\Events\LessonCreatedEvent;

class CoursesController extends Controller
{
    protected $chain;

    /**
     * ChainController constructor.
     *
     * @param ChainRepositoryInterface $post
     */
    public function __construct(ChainRepositoryInterface $chain)
    {
        $this->chain = $chain;
        $this->middleware('auth');
        $this->middleware(['permission:course/my-courses' , 'ParentCheck'],   ['only' => ['index']]);
        $this->middleware(['permission:course/layout'],   ['only' => ['show']]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request,$status=null)
    {
        //validate the request
        $request->validate([
            'years'    => 'nullable|array',
            'years.*' => 'exists:academic_years,id',
            'types'    => 'nullable|array',
            'types.*' => 'exists:academic_types,id',
            'levels'    => 'nullable|array',
            'levels.*' => 'exists:levels,id',
            'classes'    => 'nullable|array',
            'classes.*' => 'exists:classes,id',
            'segments'    => 'nullable|array',
            'segments.*' => 'exists:segments,id',
            'paginate' => 'integer',
            'role_id' => 'integer|exists:roles,id',
            // 'for' => 'in:enroll',
            'search' => 'nullable',
            'user_id'=>'exists:users,id',
            'period' => 'in:past,future,no_segment'
        ]); 

        $paginate = 12;
       
        if($request->has('paginate'))
            $paginate = $request->paginate;

        $enrolls = $this->chain->getEnrollsByManyChain($request)->orderBy('level', 'ASC');

        if($request->has('role_id'))
            $enrolls->where('role_id',$request->role_id);

        if(!$request->has('user_id')) 
            $enrolls->where('user_id',Auth::id());
        
        if($request->templates == 1){
            $templates = Course::where('is_template',1)->get()->pluck('id');
            $enrolls->whereIn('course',$templates);
        }
        $results = $enrolls->whereHas('courses' , function($query)use ($request ) {
            if($request->filled('search'))
                $query->where('name', 'LIKE' , "%$request->search%");
            if($request->user()->can('show-hidden-courses'))
                $query->where('show',1);

        })->join('courses', 'enrolls.course', '=', 'courses.id')
            ->orderBy('courses.index', 'ASC')
            ->groupBy(['course','level'])->get();

        return response()->json(['message' => __('messages.course.list'), 'body' => CourseResource::collection($results)->paginate($paginate)], 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'no_of_lessons' => 'integer',
            'shared_lesson' => 'required|in:0,1',
            'image' => 'file|distinct|mimes:jpg,jpeg,png,gif',
            'mandatory' => 'nullable',
            'short_name' =>'required',
            'is_template' => 'nullable|boolean',
            'chains' => 'array|required',
            'chains.*.level' => 'array|required',
            'chains.*.level.*' => 'required|exists:levels,id',
            'chains.*.segment' => 'array|required_with:chains.*.level',
            'chains.*.segment.*' => 'required|exists:segments,id',
            'chains.*.class' => 'array|required_with:chains.*.level',
            'chains.*.class.*' => 'required|exists:classes,id',
        ]);
        $no_of_lessons = 4;

        if($request->is_template == 1){
            $check = Course::whereIn('level_id',$request->chains[0]['level'])->where('is_template', 1)->count();
            if($check != 0)
                return response()->json(['message' => __('messages.course.anotherTemplate'), 'body' => null], 200);
        }
        
        foreach ($request->chains as $chain){
            foreach ($chain['segment'] as $segment) {
                foreach ($chain['level'] as $level) {
                    $short_names=Course::where('segment_id',$segment)->where('short_name',$request->short_name)->get();
                    if(count($short_names)>0)
                        return HelperController::api_response_format(400, null, 'short_name must be unique');

                    $index = Course::where('level_id', $level)->max('index');
                    $course = Course::firstOrCreate([
                        'name' => $request->name,
                        'short_name' => $request->short_name,
                        'image' => isset($request->image) ? attachment::upload_attachment($request->image, 'course')->id : null,
                        'category_id' => isset($request->category) ? $request->category : null,
                        'description' => isset($request->description) ? $request->description : null,
                        'mandatory' => isset($request->mandatory) ? $request->mandatory : 1,
                        'segment_id' => $segment,
                        'level_id' => $level,
                        'is_template' => isset($request->is_template) ? $request->is_template : 0,
                        'classes' => json_encode($chain['class']),
                        'shared_lesson' => $request->shared_lesson,
                        'index' => isset($index) ? $index+1 : 1 ,
                        'show' => isset($request->show) ? $request->show : 1
                    ]);

                    if ($request->filled('no_of_lessons'))
                        $no_of_lessons = $request->no_of_lessons;

                    foreach ($chain['class'] as $class) {

                        for ($i = 1; $i <= $no_of_lessons; $i++) {
                            if($request->shared_lesson == 1){
                                $lesson=lesson::firstOrCreate([
                                    'name' => 'Lesson ' . $i,
                                    'shared_lesson' => 1,
                                    'course_id' => $course->id,
                                    'shared_classes' => json_encode($chain['class']),
                                ],[
                                    'index' => Lesson::where('course_id',$course->id)->max('index')+1,
                                ]);
                            }else{
                                $lesson=lesson::create([
                                    'name' => 'Lesson ' . $i,
                                    'index' => Lesson::where('course_id',$course->id)->max('index')+1,
                                    'shared_lesson' => 0,
                                    'course_id' => $course->id,
                                    'shared_classes' => json_encode([$class]),
                                ]);
                            }
                        }
                    }

                    //Creating defult question category
                    $quest_cat = QuestionsCategory::firstOrCreate([
                        'name' => $course->name . ' Category',
                        'course_id' => $course->id,
                    ]);

                    $gradeCat = GradeCategory::firstOrCreate([
                        'name' => $course->name . ' Total',
                        'course_id' => $course->id,
                        'calculation_type' => json_encode(['Natural']),
                    ]);
                }
            }
        }
        $courses =  Course::query();
        $enrolls = $this->chain->getEnrollsByManyChain($request)->where('user_id',Auth::id());
        $courses->whereIn('id',$enrolls->pluck('course'));
        $courses->with(['category', 'attachment','level'])->get();

        foreach($courses as $le){
            $teacher = User::whereIn('id',Enroll::where('role_id', '4')->where('course',  $le->id)
                                                ->pluck('user_id')
                            )->with('attachment')->get(['id', 'username', 'firstname', 'lastname', 'picture']);
            $le['teachers']  = $teacher ;
        }
        return response()->json(['message' => __('messages.course.add'), 'body' => null], 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $course = Course::with('attachment','level')->find($id);

        if(isset($course)){
           // LastAction::lastActionInCourse($id);
            return response()->json(['message' => __('messages.course.object'), 'body' => $course], 200);
        }
        return response()->json(['message' => __('messages.error.not_found'), 'body' => [] ], 400);
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
            'name' => 'nullable',
            'category' => 'nullable|exists:categories,id',
            'id' => 'required|exists:courses,id',
            'image' => 'nullable',
            'description' => 'nullable',
            'mandatory' => 'nullable|in:0,1',
            'shared_lesson' => 'nullable|in:0,1',
            'short_name' => 'unique:courses,short_name,'.$id,
            'course_template' => 'nullable|exists:courses,id',
            'is_template' => 'nullable|boolean|required_with:course_template',
            'old_lessons' => 'nullable|boolean|required_with:course_template',
        ]);

        $editable = ['name','show', 'category_id', 'description', 'mandatory','short_name','is_template'];
        $course = Course::find($id);
        // if course has an image
        if ($request->hasFile('image')) 
            $course->image = attachment::upload_attachment($request->image, 'course')->id;
        
        if(isset($request->shared_lesson) && $request->shared_lesson == 0)
        {
            $countAllLessons = Lesson::where('course_id', $id)->where('shared_lesson',1)->count();
            if($countAllLessons > 0)
                return HelperController::api_response_format(200, $course, __('messages.course.canNot'));

            $editable[]='shared_lesson';    
        }

        foreach($editable as $key) 
            if($request->filled($key)) 
                $course->$key = $request->$key;

        if($request->filled('course_template')){
            if($request->old_lessons == 0){
                $old_lessons = Lesson::where('course_id', $id)->get();
                $secondary_chains = SecondaryChain::whereIn('lesson_id',$old_lessons)->where('course_id',$id)->delete();
            }
            $new_lessons = Lesson::where('course_id', $request->course_template);
            foreach($new_lessons->cursor() as $lesson){
                Lesson::create([
                    'name' => $lesson->name,
                    'course_id' => $id,
                    'shared_lesson' => 1,//$lesson->shared_lesson,
                    'index' => $lesson->index,
                    'description' => $lesson->description,
                    'image' => $lesson->image,
                ]);
            }            
        }

        if($request->filled('shared_lesson'))
            $lessons = Lesson::where('course_id', $request->id)->update(['shared_lesson' => $request->shared_lesson]);

        $course->save();
        return HelperController::api_response_format(200, $course, __('messages.course.update'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id ,Request $request)
    {
        $course = Course::find($id);
        $enrolls = Enroll::where('course',$id)->where('role_id','!=',1)->count();

        if($enrolls > 0 && !$request->user()->can('course\softDelete'))
            return HelperController::api_response_format(200, [], __('messages.error.cannot_delete'));
        
        // if($request->user()->can('course\forceDelete'))
        //     $course->forceDelete();

        $course->delete();
        return app('App\Http\Controllers\CourseController')->get($request);
        // return HelperController::api_response_format(200, $course, __('messages.course.delete'));
    }

    public function Apply_Template(Request $request)
    {
        $request->validate([
            'template_id' => 'required|exists:courses,id',
            'old_lessons' => 'required|nullable|boolean',
            'courses' => 'required|array',
            'courses.*' => 'nullable|exists:courses,id',
        ]);

        $req['template_id']=$request->template_id;
        $req['courses']=$request->courses;
        $req['old_lessons']=$request->old_lessons;

        $jop = (new \App\Jobs\CourseTemplateJob($req));
        dispatch($jop);

        // foreach($request->courses as $course){
        //     $shared_ids = [];
        //     $classes_of_course = Course::find($course);
        //     if($request->old_lessons == 0){
        //         $old_lessons = Lesson::where('course_id', $course);
        //         // $secondary_chains = SecondaryChain::whereIn('lesson_id',$old_lessons->get())->where('course_id',$course)->get()->delete();
        //         $old_ids =  $old_lessons->pluck('id'); 
        //     }
        //     foreach ($classes_of_course->classes as $key => $class) {
        //         if($request->old_lessons == 0) 
        //             $secondary_chains = SecondaryChain::where('group_id',$class)->whereIn('lesson_id',$old_ids)->where('course_id',$course)->delete();                             
                
        //         $lessonsPerGroup = SecondaryChain::select('lesson_id')->where('group_id',$class)->where('course_id',$request->template_id)->distinct('lesson_id')->pluck('lesson_id');
        //         // dd($lessonsPerGroup);
        //         $new_lessons = Lesson::whereIn('id', $lessonsPerGroup)->get();
        //         foreach($new_lessons as $lesson){
        //             if(($key == 0 &&  $request->old_lessons == 1) || ($key != 0 &&  $request->old_lessons == 1 && json_decode($lesson->getOriginal('shared_classes')) == [$class] )){
        //                 $id = lesson::create([
        //                     'name' => $lesson->name,
        //                     'index' => $lesson->index,
        //                     'shared_lesson' => $lesson->shared_lesson,
        //                     'course_id' => $course,
        //                     'shared_classes' => $lesson->getOriginal('shared_classes'),
        //                     'description' => $lesson->description,
        //                 ]);
        //                 $shared_ids[] = $id->id;
        //             }else{
        //                 $id = lesson::firstOrCreate([
        //                     'name' => $lesson->name,
        //                     'index' => $lesson->index,
        //                     'shared_lesson' => $lesson->shared_lesson,
        //                     'course_id' => $course,
        //                     'shared_classes' => $lesson->getOriginal('shared_classes'),
        //                     'description' => $lesson->description,
        //                 ]);
        //                 event(new LessonCreatedEvent(Lesson::find($id->id)));
        //                 $shared_ids[] = $id->id;
        //             }
        //         }
        //     }

        //     if($request->old_lessons == 0){
        //         Lesson::whereIn('id',$old_ids)->whereNotIn('id',$shared_ids)->delete();
        //     }
        // }
        return HelperController::api_response_format(200, null, __('messages.course.template'));
    }

    public function sort(Request $request)
    {
        $request->validate([
            'course_id' => 'required|exists:courses,id',
            'index' => 'required|integer',
        ]);
        $given_course = Course::whereId($request->course_id)->first();
        
        //////sort down
        if($request->index > $given_course->index ){
            $courses = Course::where('level_id', $given_course->level_id)->where('index', '>=', $given_course->index)->Where('index','<=', $request->index);
            foreach($courses->cursor() as $course){
                $course->decrement('index');
            }
        }
        //////sort up
        if($request->index < $given_course->index ){
            $courses = Course::where('level_id', $given_course->level_id)->where('index', '<=', $given_course->index)->Where('index','>=', $request->index);
            foreach($courses->cursor() as $course){
                $course->increment('index');
            }
        }
        $given_course->update(['index' => $request->index]);
        return response()->json(['message' => 'Sorted successfully', 'body' =>  null ], 200);
    }

}
