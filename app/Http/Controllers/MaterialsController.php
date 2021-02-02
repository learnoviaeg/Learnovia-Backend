<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Repositories\ChainRepositoryInterface;
use App\Enroll;
use App\Material;
use Illuminate\Support\Facades\Auth;
use App\Lesson;
use App\Level;
use App\Classes;
use App\Paginate;
use DB;
use Carbon\Carbon;
use App\User;
use App\Log;
use App\UserSeen;

class MaterialsController extends Controller
{
    protected $chain;

    public function __construct(ChainRepositoryInterface $chain)
    {
        $this->chain = $chain;
        $this->middleware('auth');
        $this->middleware(['permission:material/get' , 'ParentCheck'],   ['only' => ['index']]);
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
            'sort_in' => 'in:asc,desc',
            'item_type' => 'string|in:page,media,file',
            'class' => 'nullable|integer|exists:classes,id',
            'lesson' => 'nullable|integer|exists:lessons,id' 
        ]);

        $user_course_segments = $this->chain->getCourseSegmentByChain($request);

        if(!$request->user()->can('site/show-all-courses'))//student
            $user_course_segments = $user_course_segments->where('user_id',Auth::id());

        $user_course_segments = $user_course_segments->select('course_segment')->distinct()->with('courseSegment.lessons')->get();

        $lessons = $user_course_segments->pluck('courseSegment.lessons')->collapse()->pluck('id');
      
        if($request->has('lesson')){
            if(!in_array($request->lesson,$lessons->toArray()))
                return response()->json(['message' => __('messages.error.no_active_for_lesson'), 'body' => []], 400);

            $lessons = [$request->lesson];
        }
            
        $material = Material::with(['lesson','course'])->whereIn('lesson_id',$lessons);

        if($request->user()->can('site/course/student')){
            $material->where('visible',1)->where('publish_date' ,'<=', Carbon::now());
        }

        if($request->has('sort_in'))
            $material->orderBy("publish_date",$request->sort_in);

        if($request->has('item_type'))
            $material->where('type',$request->item_type);

        if($count == 'count'){

            $counts = $material->select(DB::raw
                (  "COUNT(case `type` when 'file' then 1 else null end) as file ,
                    COUNT(case `type` when 'media' then 1 else null end) as media ,
                    COUNT(case `type` when 'page' then 1 else null end) as page" 
                ))->first()->only(['file','media','page']);

            return response()->json(['message' => __('messages.materials.count'), 'body' => $counts], 200);
        }

        $AllMat=$material->get();
        foreach($AllMat as $one){
            $one->class = Classes::find($one->lesson->courseSegment->segmentClasses[0]->classLevel[0]->class_id);
            $one->level = Level::find($one->lesson->courseSegment->segmentClasses[0]->classLevel[0]->yearLevels[0]->level_id);
            unset($one->lesson->courseSegment);
        }

        return response()->json(['message' => __('messages.materials.list'), 'body' => $AllMat->paginate(Paginate::GetPaginate($request))], 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        
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

        $material = Material::where('id',$id)->first();
        if(!isset($material))
            return response()->json(['message' => __('messages.error.data_invalid'), 'body' => null], 400);

        
        $material->seen_number = $material->seen_number + 1;
        $material->save();

        $user_views = UserSeen::updateOrCreate(['user_id' => Auth::id(),'item_id' => $material->item_id,'type' => $material->type ,'lesson_id' => $material->lesson_id],[
            'user_id' => Auth::id(),
            'item_id' => $material->item_id,
            'lesson_id' => $material->lesson_id,
            'type' => $material->type,
        ])->increment('count');


        return response()->json(['message' => 'Material updated successfully', 'body' => $material], 200);
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
