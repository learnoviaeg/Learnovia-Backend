<?php

namespace App\Http\Controllers;

use App\Repositories\ChainRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Lesson;
use App\SecondaryChain;

class LessonsController extends Controller
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
    public function index(Request $request)
    {
        //validate the request
        $request->validate([
            // 'level' => 'exists:levels,id',
            'classes'    => 'nullable|array',
            'classes.*'  => 'nullable|integer|exists:classes,id',
            'courses'    => 'nullable|array',
            'courses.*'  => 'nullable|integer|exists:courses,id',
            'shared' => 'in:0,1'
        ]);
        $enrolls = $this->chain->getEnrollsByManyChain($request)->get()->pluck('id');
        // if($request->user()->can('site/show-all-courses')){//admin
        $lessons = SecondaryChain::select('*')->distinct()->where('user_id',Auth::id())->whereIn('enroll_id',$enrolls);
        if($request->filled('classes'))
            $lessons->whereIn('group_id',$request->classes);
        $result_lessons = $lessons->get()->groupBy('lesson_id');
        /*
        if($request->filled('classes')){
            foreach($result_lessons as $key=>$lesson){
                if(count($lesson) != count($request->classes)){
                    unset($result_lessons[$lesson[0]->lesson_id]);
                }
            }
        }
        */
        $result = Lesson::whereIn('id',$result_lessons->keys());
        if($request->filled('shared')){
            $result->where('shared_lesson', $request->shared)
            ->where(function($query) use ($request){        //in case of "shared", get shared lessons only
                $query->whereRaw('JSON_CONTAINS(`shared_classes`, '. '\'["'. implode('","',$request->classes) . '"]\'' .') AND JSON_LENGTH(`shared_classes`) = JSON_LENGTH(\'' . '["'. implode('","',$request->classes) . '"]\''. ')')
                ->orWhereRaw('JSON_CONTAINS(`shared_classes`, '. '\'['. implode(',',$request->classes) . ']\'' .') AND JSON_LENGTH(`shared_classes`) = JSON_LENGTH(\'' . '['. implode(',',$request->classes) . ']\''. ')');
            });
        }

        //When adding materials
        if($request->filled('material_filter')){
            $result->where(function($query) use ($request){
                $query->whereRaw('JSON_CONTAINS(`shared_classes`, '. '\'["'. implode('","',$request->classes) . '"]\'' .') AND JSON_LENGTH(`shared_classes`) = JSON_LENGTH(\'' . '["'. implode('","',$request->classes) . '"]\''. ')')
                ->orWhereRaw('JSON_CONTAINS(`shared_classes`, '. '\'['. implode(',',$request->classes) . ']\'' .') AND JSON_LENGTH(`shared_classes`) = JSON_LENGTH(\'' . '['. implode(',',$request->classes) . ']\''. ')');
            });
        }

        return response()->json(['message' => __('messages.lesson.list'), 'body' => $result->orderBy('index', 'ASC')->get()], 200);
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
