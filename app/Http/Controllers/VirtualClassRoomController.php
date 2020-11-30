<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Repositories\ChainRepositoryInterface;
use Modules\Bigbluebutton\Entities\BigbluebuttonModel;
use Auth;
use Carbon\Carbon;
use App\Paginate;
use Modules\Bigbluebutton\Http\Controllers\BigbluebuttonController;

class VirtualClassRoomController extends Controller
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
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $request->validate([
            'id' => 'exists:bigbluebutton_models,id',
            'year' => 'exists:academic_years,id',
            'type' => 'exists:academic_types,id',
            'level' => 'exists:levels,id',
            'class' => 'exists:classes,id',
            'segment' => 'exists:segments,id',
            'course'    => 'exists:courses,id',
            'status'    => 'in:past,future,current',
            'start_date' => 'date|required_with:due_date',
            'due_date' => 'date|required_with:start_date',
            'sort_in' => 'in:asc,desc',
            'pagination' => 'boolean'
        ]);

        if(isset($request->course)){
            $request['courses']= [$request->course];
        }

        $sort_in = 'desc';
        if($request->has('sort_in'))
            $sort_in = $request->sort_in;

        (new BigbluebuttonController)->clear();

        $user_course_segments = $this->chain->getCourseSegmentByChain($request);

        if(!$request->user()->can('site/show-all-courses'))//student
        {
            $user_course_segments = $user_course_segments->where('user_id',Auth::id());
        }
        
        $enrolls = $user_course_segments->select(['course_segment','course','class'])->distinct()->with(['courseSegment' => function($q){
            $q->where('end_date','>',Carbon::now())->where('start_date','<',Carbon::now());
        }])->get();

        $classes =  $enrolls->pluck('class');
        if(isset($request->class)){
            $classes = [$request->class];
        }

        $meeting = BigbluebuttonModel::whereIn('course_id',$enrolls->pluck('course'))->whereIn('class_id',$classes)->orderBy('start_date',$sort_in);

        if($request->user()->can('site/course/student'))
            $meeting->where('show',1);
            
        if($request->has('status'))
            $meeting->where('status',$request->status);

        if($request->has('start_date'))
            $meeting->where('start_date', '>=', $request->start_date)->where('start_date','<=',$request->due_date);

        if($request->has('id'))
            $meeting->where('id',$request->id);

        $meetings = $meeting->get();
        foreach($meetings as $m)
            {
                $m['join'] = $m->started == 1 ? true: false;
                $m->actutal_start_date = isset($m->actutal_start_date)?Carbon::parse($m->actutal_start_date)->format('Y-m-d h:i:s a'): null;
                $m->start_date = Carbon::parse($m->start_date)->format('Y-m-d h:i:s a');
                
                if(Carbon::parse($m->start_date)->format('Y-m-d H:i:s') <= Carbon::now()->format('Y-m-d H:i:s') && Carbon::now()->format('Y-m-d H:i:s') <= Carbon::parse($m->start_date)
                ->addMinutes($m->duration)->format('Y-m-d H:i:s'))
                {
                    (new BigbluebuttonController)->create_hook($request);

                    if($request->user()->can('bigbluebutton/session-moderator') && $m->started == 0)
                        $m['join'] = true; //startmeeting has arrived but meeting didn't start yet
                }
            }

        if(count($meetings) == 0)
            return HelperController::api_response_format(200 , [] , 'Classroom is not found');

        return HelperController::api_response_format(200 , $meetings->paginate(Paginate::GetPaginate($request)),'Classrooms list');
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
