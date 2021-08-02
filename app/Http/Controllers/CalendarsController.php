<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Repositories\ChainRepositoryInterface;
use App\Timeline;
use App\Announcement;
use App\userAnnouncement;
use Auth;

class CalendarsController extends Controller
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
        $this->middleware(['permission:calendar/get' , 'ParentCheck'],   ['only' => ['index']]);
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
            'year' => 'exists:academic_years,id',
            'type' => 'exists:academic_types,id',
            'level' => 'exists:levels,id',
            'class' => 'exists:classes,id',
            'segment' => 'exists:segments,id',
            'courses'    => 'nullable|array',
            'courses.*'  => 'nullable|integer|exists:courses,id',
            'item_type'    => 'array',
            'item_type.*' => 'in:quiz,assignment,announcement',
            'calendar_year' => 'required|integer',
            'calendar_month' => 'integer|required_with:calendar_day',
            'calendar_day' => 'integer',
        ]);

        $calendar['announcements'] = [];
        $user_course_segments = $enrolls = $this->chain->getCourseSegmentByChain($request);

        if(!$request->user()->can('site/show-all-courses'))//any other user enrolled
        {
            $user_course_segments = $enrolls = $user_course_segments->where('user_id',Auth::id());
        }

        $enrolls=$enrolls->get();

        if(count($enrolls) > 0){

            //enrolled user announcements
            if(!$request->user()->can('site/show-all-courses'))
            {
                $calendar['announcements'] = userAnnouncement::where('user_id',Auth::id())->with(['announcements.chainAnnouncement'=> function ($query) use ($enrolls) {
                    $query->whereIn('year',$enrolls->pluck('year'))->whereIn('segment',$enrolls->pluck('segment'));
                }])->pluck('announcement_id');
            }

            if($request->user()->can('site/show-all-courses'))//admin
            {
                $calendar['announcements'] = Announcement::with(['chainAnnouncement' => function ($query) use ($enrolls) {
                    $query->whereIn('year',$enrolls->pluck('year'))->whereIn('segment',$enrolls->pluck('segment'));
                }])->pluck('id');
            }

        }

        $calendar['lessons'] = $user_course_segments->select('course_segment')->distinct()->with('courseSegment.lessons')->get()->pluck('courseSegment.lessons.*.id')->collapse();
        
        $timeline = Timeline::with(['class','course','level'])
                            ->where(function ($query) use ($calendar) {
                                $query->whereIn('item_id',$calendar['announcements'])->where('type','announcement')->orWhereIn('lesson_id',$calendar['lessons']);
                            })
                            ->where('visible',1)
                            ->whereYear('publish_date', $request->calendar_year)
                            ->where(function ($query) {
                                $query->whereNull('overwrite_user_id')->orWhere('overwrite_user_id', Auth::id());
                            });
        
        if(isset($request->calendar_month))
            $timeline->whereMonth('start_date','<=', $request->calendar_month)->whereMonth('due_date','>=', $request->calendar_month);

        if(isset($request->calendar_day))
            $timeline->whereDate('start_date','<=', $request->calendar_year.'-'.$request->calendar_month.'-'.$request->calendar_day)
                     ->whereDate('due_date','>=', $request->calendar_year.'-'.$request->calendar_month.'-'.$request->calendar_day);

        if($request->filled('item_type'))
            $timeline->whereIn('type', $request->item_type);

        return response()->json(['message' => __('messages.success.user_list_items'), 'body' => $timeline->orderBy('start_date', 'desc')->get()], 200);
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
