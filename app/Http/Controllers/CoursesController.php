<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Resources\CourseResource;
use Illuminate\Support\Facades\Auth;
use App\Repositories\ChainRepositoryInterface;
use Carbon\Carbon;
use App\Course;
use App\LastAction;
use App\User;
use App\Segment;
use App\Classes;
use App\SecondaryChain;
use Modules\QuestionBank\Entities\QuestionsCategory;
use App\Lesson;
use App\Enroll;
use DB;

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
        $this->middleware(['permission:course/layout' , 'ParentCheck'],   ['only' => ['show']]);
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
       
        if($request->has('paginate')){
            $paginate = $request->paginate;
        }
            $enrolls = $this->chain->getEnrollsByManyChain($request);
            // if(!$request->user()->can('site/show-all-courses') && !isset($request->user_id)) //student or teacher
            if(!$request->user()->can('site/show-all-courses')) //student or teacher
                $enrolls->where('user_id',Auth::id());

            if($request->has('role_id')){
                $enrolls->where('role_id',$request->role_id);
            }
            if($request->templates == 1){
                $templates = Course::where('is_template',1)->get()->pluck('id');
                $enrolls->whereIn('course',$templates);
            }
             $results = $enrolls->with('SecondaryChain.Teacher')->groupBy(['course','level'])->get();
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
            // 'category' => 'exists:categories,id',
            // 'level_id' => 'required|exists:levels,id',
            // 'segment_id' => 'required|exists:segments,id',
            'no_of_lessons' => 'integer',
            // 'shared_lesson' => 'required_with:no_of_lessons|in:0,1',
            'image' => 'file|distinct|mimes:jpg,jpeg,png,gif',
            // 'description' => 'string',
            'mandatory' => 'nullable',
            'short_name' =>'required',
            'is_template' => 'nullable|boolean',
            'chains.*.level' => 'array|required_with:chains.*.year',
            'chains.*.level.*' => 'required|exists:levels,id',
            'chains.*.segment' => 'array|required_with:chains.*.year',
            'chains.*.segment.*' => 'required|exists:segments,id',
        ]);

        $no_of_lessons = 4;
        foreach ($request->chains as $chain){
            foreach ($chain['segment'] as $segment) {
                foreach ($chain['level'] as $level) {
                    $short_names=Course::where('segment_id',$segment)->where('short_name',$request->short_name)->get();
                    if(count($short_names)>0)
                        return HelperController::api_response_format(400, null, 'short_name must be unique');
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
                    ]);
                    $level_id=$course->level_id;
                    $segment=Segment::find($course->segment_id);
                    $segment_id=$segment->id;
                    $year_id=$segment->academic_year_id;
                    $type_id=$segment->academic_type_id;
                    $classes=Classes::where('level_id',$course->level_id)->get();
                    // dd($classes);
                    if ($request->filled('no_of_lessons'))
                        $no_of_lessons = $request->no_of_lessons;

                    for ($i = 1; $i <= $no_of_lessons; $i++) {
                        $lesson=lesson::firstOrCreate([
                            'name' => 'Lesson ' . $i,
                            'index' => $i,
                            'shared_lesson' => isset($request->shared_lesson) ? $request->shared_lesson : 0,
                            'course_id' => $course->id
                        ]);
                    }

                    foreach($classes as $class)
                    {
                        $enroll=Enroll::firstOrCreate([
                            'user_id'=> 1,
                            'role_id' => 1,
                            'year' => $year_id,
                            'type' => $type_id,
                            'segment' => $segment_id,
                            'level' => $level_id,
                            'group' => $class->id,
                            'course' => $course->id
                        ]);

                        for ($i = 1; $i <= $no_of_lessons; $i++) {
                            $lesson=lesson::firstOrCreate([
                                'name' => 'Lesson ' . $i,
                                'index' => $i,
                                'shared_lesson' => isset($request->shared_lesson) ? $request->shared_lesson : 0,
                                'course_id' => $course->id
                            ]);

                            SecondaryChain::firstOrCreate([
                                'user_id' => 1,
                                'role_id' => 1,
                                'group_id' => $enroll->group,
                                'course_id' => $enroll->course,
                                'lesson_id' => $lesson->id,
                                'enroll_id' => $enroll->id
                            ]);

                            // event(new LessonCreatedEvent($lesson,$enroll));
                        }
                    }

                    //Creating defult question category
                    $quest_cat = QuestionsCategory::firstOrCreate([
                        'name' => $course->name . ' Category',
                        'course_id' => $course->id,
                    ]);
                }
            }
        }
        $courses =  Course::with(['category', 'attachment','level'])->get();
        foreach($courses as $le){
            $teacher = User::whereIn('id',Enroll::where('role_id', '4')->where('course',  $le->id)
                                                ->pluck('user_id')
                            )->with('attachment')->get(['id', 'username', 'firstname', 'lastname', 'picture']);
            $le['teachers']  = $teacher ;
        }
        return response()->json(['message' => __('messages.course.list'), 'body' => $courses->paginate(HelperController::GetPaginate($request))], 200);

        // return $courses;
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
            LastAction::lastActionInCourse($id);
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
            'short_name' => 'unique:courses,short_name,'.$id,
            'course_template' => 'nullable|exists:courses,id',
            'is_template' => 'nullable|boolean|required_with:course_template',
            'old_lessons' => 'nullable|boolean|required_with:course_template',
        ]);

        $editable = ['name', 'category_id', 'description', 'mandatory','short_name','is_template'];
        $course = Course::find($id);
        // if course has an image
        if ($request->hasFile('image')) 
            $course->image = attachment::upload_attachment($request->image, 'course')->id;
        
        foreach ($editable as $key) 
            if ($request->filled($key)) 
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
        $course->save();
        return HelperController::api_response_format(200, $course, __('messages.course.update'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $course = Course::find($id);
        $enrolls = Enroll::where('course',$id)->get();
        if(count($enrolls)>0)
            return HelperController::api_response_format(200, [], __('messages.error.cannot_delete'));

        $course->delete();
        return HelperController::api_response_format(200, $course, __('messages.course.delete'));
    }
}
