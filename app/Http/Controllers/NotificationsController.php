<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Notifications\NewMessage;
use App\User;
use Carbon\Carbon;
use DB;
use App\LastAction;
use App\Course;
use App\Notification;
use App\Paginate;
use Illuminate\Support\Facades\Auth;

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
            'read' => 'in:1,0',
            'type'=>'string|in:announcement,notification',  
            'course_id' => 'integer|exists:courses,id',
            'component_type' => 'string|in:file,media,Page,quiz,assignment,h5p,meeting',
            'sort_in' => 'in:asc,desc', 
            'search' => 'string'
        ]);

        //check if the auth user is parent and has current child to get his child notifications
        $roles = Auth::user()->roles->pluck('name');

        if(in_array("Parent" , $roles->toArray())){

            if(Auth::user()->currentChild != null)
            {
                $currentChild =User::find(Auth::user()->currentChild->child_id);
                Auth::setUser($currentChild);
            }
        }

        $user = Auth::user();
        $notifications = $user->notifications->where('publish_date' ,'<=',Carbon::now());
      
        // for route api/notifications/{types} 
        if($types=='types'){

            $notifications_types = $notifications->where('item_type','!=','announcement')->pluck('item_type')->unique();

            return response()->json(['message' => 'notification types list.','body' => $notifications_types->unique()->values()], 200);
        }

        if($request->has('sort_in') && $request->sort_in == 'asc'){
            $notifications = $notifications->reverse()->values();
        }

        //read
        if($request->has('read') && $request->read){
            $notifications = $notifications->where('pivot.read_at','!=',null);
        }

        //unread
        if($request->has('read') && !$request->read){
            $notifications = $notifications->where('pivot.read_at',null);
        }

        if($request->type){
            $notifications = $notifications->where('type',$request->type);
        }
        
        if($request->filled('component_type')){
            $notifications = $notifications->where('item_type',$request->component_type);
        }

        if($request->filled('course_id')){
            $notifications = $notifications->where('course_id',$request->course_id);
        }

        if($request->filled('search')){

            $notifications = $notifications->filter(function ($item) use ($request) {
                if($item->message != null && str_contains(strtolower($item->message), strtolower($request->search))) 
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

        if($request->class_id){
            $request['classes'] = [$request->class_id];
        }

        (new Notification)->send($request);

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
    public function update(Request $request,$id)
    {
        $notificaton = Notification::findOrFail($id);

        $user_notification = $notificaton->users->where('pivot.user_id',Auth::id())->first();
        
        if($user_notification){

            $user_notification->pivot->read_at = Carbon::now()->toDateTimeString();
            $user_notification->pivot->save();
        }
       
        return response()->json(['message' => 'Notification was read','body' => $notificaton], 200); 
    }

    public function read(Request $request,$read=null)
    {
        $user = User::findOrFail(Auth::id());

        $type = 'notification';
        if($read && $read == 'announce'){
            $type = 'announcement';
        }

        $userNotifications = $user->notifications->where('pivot.read_at',null)->where('type',$type);
    
        foreach($userNotifications as $notificaton){
            $user->notifications()->updateExistingPivot($notificaton->id,['read_at' => Carbon::now()]);
        }
            
        return response()->json(['message' => $read .' was read','body' => null], 200);        
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
