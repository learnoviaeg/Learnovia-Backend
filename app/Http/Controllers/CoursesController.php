<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Repositories\ChainRepositoryInterface;
use Carbon\Carbon;
use App\Course;
use App\LastAction;
use App\User;
use App\Enroll;
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
            'for' => 'in:enroll',
            'search' => 'nullable',
        ]);


        $paginate = 12;
        if($request->has('paginate')){
            $paginate = $request->paginate;
        }
        
        $user_courses=collect();
        if(isset($status)){

            $enrolls = $this->chain->getCourseSegmentByManyChain($request);
            if(!$request->user()->can('site/show-all-courses')){ //student or teacher
                $enrolls->where('user_id',Auth::id());
            }
    
            if($request->has('role_id')){
                $enrolls->where('role_id',$request->role_id);
            }
            $enrolls = $enrolls->with(['courseSegment.courses.attachment','levels'])->get()->groupBy(['course','level']);
    
            foreach($enrolls as $course){
                $levels=[];
                $teacher = [];
                foreach($course as $level){
                    $teacher[] = User::whereIn('id',Enroll::where('role_id', '4')->where('course',  $level[0]->course)->where('level',$level[0]->level)
                                                    ->pluck('user_id')
                                )->with('attachment')->get(['id', 'username', 'firstname', 'lastname', 'picture']);

                    $start_date = $level[0]->courseSegment->start_date;
                    $end_date = $level[0]->courseSegment->end_date;

                    if($status =="ongoing"){ // new route for  onoing courses in ative year api/course/ongoing
                        if($level[0]->courseSegment->end_date > Carbon::now() && $level[0]->courseSegment->start_date <= Carbon::now()){
                            $levels[] =  isset($level[0]->levels) ? $level[0]->levels->name : null;
                            $temp_course = $level[0]->courseSegment->courses[0];
                        }
                    }
                    if($status =="future"){// new route for  future courses in ative year api/course/future
                        if($level[0]->courseSegment->end_date > Carbon::now() && $level[0]->courseSegment->start_date > Carbon::now()){ 
                            $levels[] =  isset($level[0]->levels) ? $level[0]->levels->name : null;
                            $temp_course = $level[0]->courseSegment->courses[0];     
                        }
                    }
                    if($status =="past"){ // new route for  past courses in ative year api/course/past
                        if($level[0]->courseSegment->end_date < Carbon::now() && $level[0]->courseSegment->start_date < Carbon::now()){ 
                            $levels[] =  isset($level[0]->levels) ? $level[0]->levels->name : null;
                            $temp_course = $level[0]->courseSegment->courses[0];     
                        }
                    }
                }

                if(!isset($temp_course))
                    continue;

                $user_courses->push([
                    'id' => $temp_course->id ,
                    'name' => $temp_course->name ,
                    'short_name' => $temp_course->short_name ,
                    'image' => isset($temp_course->image) ? $temp_course->attachment->path : null,
                    'description' => $temp_course->description ,
                    'mandatory' => $temp_course->mandatory == 1 ? true : false ,
                    'level' => $levels,
                    'teachers' => collect($teacher)->collapse()->unique()->values(),
                    'start_date' => $start_date,
                    'end_date' => $end_date,
                    'progress' => $temp_course->progress ,
                ]);

                $temp_course = null;
            }
        }

        if($status == null){

            $chain_request = new Request ([
                'year' => $request->filled('years') ? $request->years[0] : null,
                'type' => $request->filled('types') ? $request->types[0] : null,
                'level' => $request->filled('levels') ? $request->levels[0] : null,
                'class' => $request->filled('classes') ? $request->classes[0] : null,
                'segment' => $request->filled('segments') ? $request->segments[0] : null,
            ]);

            $course_segments = collect($this->chain->getAllByChainRelation($request));

            if($request->for == 'enroll')
                $course_segments = $course_segments->where('start_date','<=',Carbon::now())->where('end_date','>=',Carbon::now());

            $course_segments->map(function ($cs) use ($user_courses){

                foreach($cs->courses as $cou){

                    if(count($user_courses->where('id',$cou->id)) == 0){

                        $user_courses->push([
                            'id' => $cou->id ,
                            'name' => $cou->name ,
                            'short_name' => $cou->short_name ,
                            'image' => isset($cou->image) ? $cou->attachment->path : null,
                            'description' => $cou->description ,
                            'mandatory' => $cou->mandatory == 1 ? true : false ,
                            'level' => $cou->courseSegments->pluck('segmentClasses.*.classLevel.*.yearLevels.*.levels')->collapse()->collapse()->unique()->values()->pluck('name'),
                            'teachers' => Enroll::where('course',$cou->id)->where('role_id',4)->with('user.attachment')->get()->pluck('user'),
                            'start_date' => $cou->courseSegments[0]->start_date,
                            'end_date' => $cou->courseSegments[0]->end_date,
                            'progress' => $cou->progress ,
                        ]);
                    }
                }

            });

            if($request->filled('search')){
                $user_courses = $user_courses->filter(function ($item) use ($request) {
                    return str_contains(strtolower($item['name']), strtolower($request->search));
                });
            }
        }

        return response()->json(['message' => __('messages.course.list'), 'body' => $user_courses->paginate($paginate)], 200);

    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $course = Course::with('attachment')->find($id);

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
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
