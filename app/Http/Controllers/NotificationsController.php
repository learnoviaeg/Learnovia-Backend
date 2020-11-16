<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Notifications\NewMessage;
use Illuminate\Support\Facades\Notification;
use App\User;
use Carbon\Carbon;
use Auth;

class NotificationsController extends Controller
{

    public function __construct()
    {
        $this->middleware('permission:notifications/send', ['only' => ['store']]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
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
            'id' => 'required',
            'users'=>'required|array',
            'users.*' => 'required|integer|exists:users,id',
            'type' => 'required|string',
            'message' => 'required',
            'course_id' => 'integer|exists:courses,id',
            'class_id'=>'integer|exists:classes,id',
            'lesson_id'=>'integer|exists:lessons,id',
            'link' => 'string',
            'publish_date' => 'date',
            'from' => 'integer|exists:users,id',
        ]);

        if(!isset($request->lesson_id))
            $request['lesson_id'] = null;

        if(!isset($request->course_id))
            $request['course_id'] = null;   

        if(!isset($request->class_id))
            $request['class_id'] = null;
        
        if(!isset($request->link))
            $request['link'] = null;

        if(!isset($request->publish_date))
            $request['publish_date'] = Carbon::now();

        if(!isset($request->from))
            $request['from'] = Auth::id();

        $users = User::whereIn('id',$request->users)->whereNull('deleted_at')->get();

        $date = $request->publish_date;

        $seconds = $date->diffInSeconds(Carbon::now()); //calculate time the job should fire at
        if($seconds < 0) {
            $seconds = 0;
        }
        
        //user firebase realtime notifications 
        $job = ( new \App\Jobs\Sendnotify($request->toArray()))->delay($seconds);

        dispatch($job);

        //store notifications in DB
        Notification::send($users, new NewMessage($request));

        return response()->json(['message' => 'Notification sent.','body' => null], 200);
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
