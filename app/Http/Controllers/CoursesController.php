<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Repositories\ChainRepositoryInterface;
use Carbon\Carbon;

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
        ]);

        $enrolls = $this->chain->getCourseSegmentByChain($request);
        if(!$request->user()->can('site/show-all-courses')){ //student or teacher
            $enrolls->where('user_id',Auth::id());
        }

        $enrolls = $enrolls->with(['courseSegment.courses.attachment','levels'])->get()->groupBy(['level','course']);

        $user_courses=collect();
        $i=0;
        foreach($enrolls as $level){

            foreach($level as $course){

                if($course[0]->courseSegment->end_date > Carbon::now() && $course[0]->courseSegment->start_date <= Carbon::now()){
                    
                    if(!isset($course[0]->courseSegment->courses[0]))
                        continue;

                    $user_courses->push([
                        'id' => $course[0]->courseSegment->courses[0]->id ,
                        'name' => $course[0]->courseSegment->courses[0]->name ,
                        'short_name' => $course[0]->courseSegment->courses[0]->short_name ,
                        'image' => isset($course[0]->courseSegment->courses[0]->image) ? $course[0]->courseSegment->courses[0]->attachment->path : null,
                        'description' => $course[0]->courseSegment->courses[0]->description ,
                        'mandatory' => $course[0]->courseSegment->courses[0]->mandatory == 1 ? true : false ,
                        'level' => isset($course[0]->levels) ? $course[0]->levels->name : null, 
                    ]);
    
                }
            }
        }

        return response()->json(['message' => 'User courses list', 'body' => $user_courses->unique()->values()], 200);

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
