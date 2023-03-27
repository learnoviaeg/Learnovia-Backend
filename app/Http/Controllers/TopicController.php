<?php

namespace App\Http\Controllers;

use App\Events\TopicCreatedEvent;
use Illuminate\Http\Request;
use App\Enroll;
use App\Topic;
use App\EnrollTopic;
use App\AcademicYear;
use Auth;
use App\Repositories\ChainRepositoryInterface;
use Illuminate\Support\Facades\Paginator;

use App\Http\Resources\TopicResource;



class TopicController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function __construct(ChainRepositoryInterface $chain)
    {
        $this->chain = $chain;
        $this->middleware('auth');
        $this->middleware('permission:topic/crud', ['only' => ['store' , 'update' , 'destroy']]);
        $this->middleware(['permission:topic/get'],   ['only' => ['index' , 'show']]);
  
    }
    public function index(Request $request)
    {
        $request->validate([
            'search' => 'string',
            'years' => 'array',
            'years.*'  => 'nullable|exists:academic_years,id',
            'types'  => 'array',
            'types.*'  => 'nullable|exists:academic_types,id',
            'levels' => 'array',
            'levels.*' => 'nullable|exists:levels,id',
            'classes' => 'array',
            'classes.*' => 'nullable|exists:classes,id',
            'segments'  => 'array',
            'segments.*'  => 'nullable|exists:segments,id',
            'courses' => 'array',
            'courses.*' => 'nullable|exists:courses,id',
            'roles' => 'array',
            'roles.*' => 'nullable|exists:roles,id',
            'user_id' => 'array',
            'user_id.*' => 'nullable|exists:users,id',
        ]);

        $topic_ids =  EnrollTopic::Query();
        if(Auth::user()->can('site/show-all-courses') && $request->filled('years')){
            $enrolls = $this->chain->getEnrollsByManyChain($request);
            $enrolls->where('user_id',Auth::id());
            $topic_ids->whereIn('enroll_id' , $enrolls->pluck('id'));
        }
        if(!Auth::user()->can('site/show-all-courses')){
            $enrolls = $this->chain->getEnrollsByManyChain($request);
            $enrolls->where('user_id',Auth::id());
            $topic_ids->whereIn('enroll_id' , $enrolls->pluck('id'));
        }
        $topics = Topic::with('created_by')->whereIn('id' , $topic_ids->pluck('topic_id'));
        if($request->filled('search')){
           $topics->where('title', 'LIKE' , "%$request->search%"); 
        }
        return HelperController::api_response_format(200, $topics->paginate(HelperController::GetPaginate($request)), __('messages.topic.list'));
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
            'title' => 'required',
            'years' => 'array',
            'years.*'  => 'nullable|exists:academic_years,id',
            'types'  => 'array',
            'types.*'  => 'nullable|exists:academic_types,id',
            'levels' => 'array',
            'levels.*' => 'nullable|exists:levels,id',
            'classes' => 'array',
            'classes.*' => 'nullable|exists:classes,id',
            'segments'  => 'array',
            'segments.*'  => 'nullable|exists:segments,id',
            'courses' => 'array',
            'courses.*' => 'nullable|exists:courses,id',
            'roles' => 'array',
            'roles.*' => 'nullable|exists:roles,id',
            'user_id' => 'array',
            'user_id.*' => 'nullable|exists:users,id',
        ]);
        //Enroll admin if not exist in request
        if($request->filled('roles') && !in_array(1,$request->roles))
        {  
            $role = array();
            $role = $request->roles;
            $role[] = "1";
            $request->merge(['roles' => $role]);

            if($request->filled('user_id') && !in_array(1,$request->user_id)){
                $users = array();
                $users = $request->user_id;
                $users[] = "1";
                $request->merge(['user_id' => $users]);
            }
        }
        //Get enrolls by chain filteration
        $enrolls = $this->chain->getEnrollsByManyChain($request);
        $filter = json_encode($request->all());
        $topic = Topic::Create([
            'title' => $request->title,
            'filter' => $filter,
            'created_by' => Auth::id(),
        ]);
        foreach($enrolls->get() as $enroll)
        {
           EnrollTopic::Create([
               'enroll_id' => $enroll->id,
               'topic_id' => $topic->id,
           ]);
        }
        return HelperController::api_response_format(200, $topic , __('messages.topic.add'));

    }
    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $topic = Topic::with('created_by')->find($id);
        return HelperController::api_response_format(200, $topic);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Topic $topic)
    {
        $request->validate([
            'title' => 'required',
            'years' => 'array',
            'years.*'  => 'nullable|exists:academic_years,id',
            'types'  => 'array',
            'types.*'  => 'nullable|exists:academic_types,id',
            'levels' => 'array',
            'levels.*' => 'nullable|exists:levels,id',
            'classes' => 'array',
            'classes.*' => 'nullable|exists:classes,id',
            'segments'  => 'array',
            'segments.*'  => 'nullable|exists:segments,id',
            'courses' => 'array',
            'courses.*' => 'nullable|exists:courses,id',
            'roles' => 'array',
            'roles.*' => 'nullable|exists:roles,id',
            'user_id' => 'array',
            'user_id.*' => 'nullable|exists:users,id',
        ]);
        $enrolls = $this->chain->getEnrollsByManyChain($request);
        $topic->title = $request->title;
        $topic->filter = json_encode($request->all());
        $topic->save();

        EnrollTopic::where('topic_id' , $topic->id)->delete();
    
        foreach($enrolls->get() as $enroll)
        {
           EnrollTopic::Create([
               'enroll_id' => $enroll->id,
               'topic_id' => $topic->id,

           ]);
        }
        return HelperController::api_response_format(200, $topic,__('messages.topic.update'));

    }
    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Topic $topic)
    {
        $topic->delete();      
        return HelperController::api_response_format(200,[],__('messages.topic.delete'));

    }  
    
  
}
