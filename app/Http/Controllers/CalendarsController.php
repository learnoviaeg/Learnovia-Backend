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
            'item_type' => 'in:quiz,assignment,announcement',
            'calendar_year' => 'required|integer',
            'calendar_month' => 'integer|required_with:calendar_day',
            'calendar_day' => 'integer',
        ]);

        $user_course_segments = $this->chain->getCourseSegmentByChain($request);
        $calendar['announcements'] = Announcement::pluck('id');

        if(!$request->user()->can('site/show-all-courses'))//student
        {
            $user_course_segments = $user_course_segments->where('user_id',Auth::id());
            $calendar['announcements'] = userAnnouncement::where('user_id',Auth::id())->pluck('announcement_id');
        }

        $calendar['lessons'] = $user_course_segments->select('course_segment')->distinct()->with('courseSegment.lessons')->get()->pluck('courseSegment.lessons.*.id')->collapse();
        
        $timeline = Timeline::with(['class','course','level'])
                            ->where(function ($query) use ($calendar) {
                                $query->whereIn('item_id',$calendar['announcements'])->where('type','announcement')->orWhereIn('lesson_id',$calendar['lessons']);
                            })
                            ->where('visible',1)
                            ->whereYear('start_date', $request->calendar_year)
                            ->where(function ($query) {
                                $query->whereNull('overwrite_user_id')->orWhere('overwrite_user_id', Auth::id());
                            });
        
        if(isset($request->calendar_month))
            $timeline->whereMonth('start_date', $request->calendar_month);

        if(isset($request->calendar_day))
            $timeline->whereDay('start_date', $request->calendar_day);

        return response()->json(['message' => __('messages.success.user_list_items'), 'body' => $timeline->get()], 200);
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
