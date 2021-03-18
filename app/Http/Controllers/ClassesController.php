<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Repositories\ChainRepositoryInterface;
use App\SegmentClass;

class ClassesController extends Controller
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
        $this->middleware(['permission:course/layout' , 'ParentCheck'],   ['only' => ['index']]);
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request,$option = null)
    {
        //validate the request
        $request->validate([
            'level' => 'exists:levels,id',
            'courses'    => 'nullable|array',
            'courses.*'  => 'nullable|integer|exists:courses,id',
        ]);

        if($option == 'all'){
            $year_types = $this->chain->getAllByChainRelation($request);

            $course_segments =  collect($year_types->get()->pluck('YearType.*.yearLevel.*.classLevels.*.segmentClass.*.courseSegment.*')[0]);
           
            $classes = SegmentClass::with('classLevel.classes')->whereIn('id',$course_segments->pluck('segment_class_id'))->get()->pluck('classLevel.*.classes.*')->collapse();

            return response()->json(['message' => __('messages.class.list'), 'body' => $classes], 200);
        }

        $enrolls = $this->chain->getCourseSegmentByChain($request);

        if(!$request->user()->can('site/show-all-courses')){ //student or teacher
            $enrolls->where('user_id',Auth::id());
        }

        $classes = $enrolls->with('classes')->get()->pluck('classes')->unique()->values();

        return response()->json(['message' => __('messages.class.list'), 'body' => $classes->filter()->values()], 200);
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
        //
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
