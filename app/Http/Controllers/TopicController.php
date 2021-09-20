<?php

namespace App\Http\Controllers;

use App\Events\TopicCreatedEvent;
use Illuminate\Http\Request;
use App\Enroll;
use App\Topic;
use App\EnrollTopic;
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
            'segments'  => 'array',
            'segments.*'  => 'nullable|exists:segments,id',
            'courses' => 'array',
            'courses.*' => 'nullable|exists:courses,id',
            'roles' => 'array',
            'roles.*' => 'nullable|exists:roles,id',
            'users' => 'array',
            'users.*' => 'nullable|exists:users,id',
        ]);
        $enrolls = $this->chain->getEnrollsByManyChain($request);
        $topic_ids =  EnrollTopic::whereIn('enroll_id' , $enrolls->pluck('id'))->pluck('topic_id');
        $topics = Topic::whereIn('id' , $topic_ids);
        return HelperController::api_response_format(200, $topics->paginate(HelperController::GetPaginate($request)));
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
            'segments'  => 'array',
            'segments.*'  => 'nullable|exists:segments,id',
            'courses' => 'array',
            'courses.*' => 'nullable|exists:courses,id',
            'roles' => 'array',
            'roles.*' => 'nullable|exists:roles,id',
            'users' => 'array',
            'users.*' => 'nullable|exists:users,id',
        ]);

        $enrolls = $this->chain->getEnrollsByManyChain($request);
        $filter = json_encode($request->all());
        $topic = Topic::Create([
            'title' => $request->title,
            'filter' => $filter,
            //'created_by' =>  Auth::user()->id
        ]);
        foreach($enrolls->get() as $enroll)
        {
           EnrollTopic::Create([
               'enroll_id' => $enroll->id,
               'topic_id' => $topic->id,
           ]);
        }
        return new TopicResource($topic);
    }
    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Topic $topic)
    {
        return new TopicResource($topic);
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
            'segments'  => 'array',
            'segments.*'  => 'nullable|exists:segments,id',
            'courses' => 'array',
            'courses.*' => 'nullable|exists:courses,id',
            'roles' => 'array',
            'roles.*' => 'nullable|exists:roles,id',
            'users' => 'array',
            'users.*' => 'nullable|exists:users,id',
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
        return new TopicResource($topic);
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
    }  
    
  
}
