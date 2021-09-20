<?php

namespace App\Http\Controllers;

use App\Events\TopicCreatedEvent;
use Illuminate\Http\Request;
use App\Enroll;
use App\Topic;
use Auth;

use App\Http\Resources\TopicResource;



class TopicController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $topics = Topic::paginate(10);
        return TopicResource::collection($topics);

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
            'years' => 'required',
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

        $filter = json_encode($request->all());
        // print_r($filter);
        $topic = Topic::Create([
            'title' => $request->title,
            'filter' => $filter,
            //'created_by' =>  Auth::user()->id
        ]);
         //TopicCreatedEvent::dispatch($topic);
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
            'years' => '',
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
        $topic->title = $request->title;
        $topic->filter = json_encode($request->all());
        $topic->save();
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
