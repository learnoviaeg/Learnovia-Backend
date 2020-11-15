<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Repositories\ChainRepositoryInterface;
use Carbon\Carbon;
use App\Course;

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
    public function index(Request $request)
    {
        //validate the request
        $request->validate([
            'class' => 'nullable|integer|exists:classes,id',
            'paginate' => 'integer'
        ]);

        $paginate = 12;
        if($request->has('paginate')){
            $paginate = $request->paginate;
        }

        $enrolls = $this->chain->getCourseSegmentByChain($request);
        if(!$request->user()->can('site/show-all-courses')){ //student or teacher
            $enrolls->where('user_id',Auth::id());
        }

        $user_courses = $enrolls->with(['courseSegment.courses.attachment'])->get()
                                ->where('courseSegment.end_date','>',Carbon::now())
                                ->where('courseSegment.start_date','<=',Carbon::now())
                                ->pluck('courses')->unique()->values();

        return response()->json(['message' => 'User courses list', 'body' => $user_courses->paginate($paginate)], 200);

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

        if(isset($course))
            return response()->json(['message' => 'course objet', 'body' => $course], 200);

        return response()->json(['message' => 'Course not fount!', 'body' => [] ], 400);
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
