<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Notifications\NewMessage;
use Illuminate\Support\Facades\Notification;
use App\User;
use Carbon\Carbon;
use Auth;
use DB;
use App\LastAction;
use App\Course;
use App\Paginate;

class NotificationsController extends Controller
{

    public function __construct()
    {
        $this->middleware('permission:notifications/send', ['only' => ['store']]);
        $this->middleware('permission:notifications/get-all', ['only' => ['index']]);
        $this->middleware('permission:notifications/seen', ['only' => ['update']]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request,$types=null)
    {
        $request->validate([
            'read' => 'in:unread,read',
            'type'=>'string|in:announcement,notification',  
            'course_id' => 'integer|exists:courses,id',
            'component_type' => 'string|in:file,media,Page,quiz,assignment,h5p,meeting',
            'sort_in' => 'in:asc,desc', 
            'search' => 'string'
        ]);

     

        $notify = DB::table('notifications')->select('data','read_at','id')
            ->where('notifiable_id', $request->user()->id)->orderBy('created_at','desc')->get();
                                            
        $notifications = collect();
        $notifications_types =collect();
        if(isset($decoded_data['course_id'])){
            LastAction::lastActionInCourse(isset($decoded_data['course_id']));
        }

        foreach($notify as $notify_object) {

            $decoded_data= json_decode($notify_object->data, true);

            $notifications->push([
                'id' => $decoded_data['id'],
                'read_at' => $notify_object->read_at,
                'notification_id' => $notify_object->id,
                'message' => $decoded_data['message'],
                'publish_date' => Carbon::parse($decoded_data['publish_date'])->format('Y-m-d H:i:s'),
                'type' => $decoded_data['type'],
                'course_id' => isset($decoded_data['course_id']) ? $decoded_data['course_id'] : null ,
                'class_id' => isset($decoded_data['class_id']) ? $decoded_data['class_id'] : null,
                'lesson_id'  => isset($decoded_data['lesson_id']) ? $decoded_data['lesson_id'] : null,
                'link' => isset($decoded_data['link'])?$decoded_data['link']:null,
                'course_name' => isset($decoded_data['course_name'])?$decoded_data['course_name']:null,
            ]);
            $notifications_types->push($decoded_data['type']);
        }
        // for route api/notifications/{types} 
        if($types=='types')
            return response()->json(['message' => 'notification types list.','body' => $notifications_types->unique()->values()], 200);

        $notifications = $notifications->where('publish_date', '<=', Carbon::now())->sortByDesc('publish_date');
        if($request->has('sort_in') && $request->sort_in == 'asc')
            $notifications = $notifications->where('publish_date', '<=', Carbon::now())->sortBy('publish_date');

        if($request->has('read') && $request->read == 'unread')//get unread
            $notifications = $notifications->where('read_at',null);

        if($request->has('read') && $request->read == 'read')//get read
            $notifications = $notifications->where('read_at','!=',null);

        if($request->type == 'announcement')
            $notifications = $notifications->where('type','announcement');
        
        if($request->type == 'notification'){
            $notifications = $notifications->where('type','!=','announcement');
            if($request->filled('component_type'))
                $notifications = $notifications->where('type',$request->component_type);
        }
        if($request->filled('course_id'))
            $notifications = $notifications->where('course_id',$request->course_id);

        if($request->filled('search')){
            $notifications = $notifications->filter(function ($item) use ($request) {
            if(  (($item['message']!=null) && str_contains(strtolower($item['message']), strtolower($request->search)))) 
                return $item; 
        });
        }
        return response()->json(['message' => 'User notification list.','body' => $notifications->values()->paginate(Paginate::GetPaginate($request))], 200);
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

        if(isset($request->course_id))
            LastAction::lastActionInCourse($request->course_id);
        
        if(!isset($request->class_id))
            $request['class_id'] = null;
        
        if(!isset($request->link))
            $request['link'] = null;

        if(!isset($request->publish_date))
            $request['publish_date'] = Carbon::now()->format('Y-m-d H:i:s');

        if(!isset($request->from))
            $request['from'] = Auth::id();

        $request['course_name'] = null;
        if(isset($request->course_id))
            $request['course_name'] = Course::whereId($request->course_id)->pluck('name')->first();

        $users = User::whereIn('id',$request->users)->whereNull('deleted_at')->get();

        $date = Carbon::parse($request->publish_date);

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

        $notify = DB::table('notifications')->where('id', $id)
                    ->update([
                        'read_at' => Carbon::now()->toDateTimeString()
                    ]);

        if($notify == 0 )
            return response()->json(['message' => 'This notification not found','body' => null], 404);

        return response()->json(['message' => 'Notification readed','body' => $notify], 200);        
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
