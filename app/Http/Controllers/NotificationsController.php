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
use App\Notifications\QuizNotification;
use App\Notifications\SendNotification;
use App\Paginate;
use Illuminate\Support\Facades\Auth;
use Modules\QuestionBank\Entities\QuizLesson;

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
            'users'=>'array',
            'users.*' => 'integer|exists:users,id',
            'type' => 'required|string',
            'message' => 'string|required_if:message_type,==,customized',
            'course_id' => 'integer|exists:courses,id|required_if:message_type,==,quiz_notify',
            'class_id'=>'integer|exists:classes,id',
            'lesson_id'=>'integer|exists:lessons,id|required_if:message_type,==,quiz_notify',
            'link' => 'string',
            'publish_date' => 'date',
            'from' => 'integer|exists:users,id',
            'message_type' => 'required|in:customized,quiz_notify'
        ]);

        //sending static notification when teacher click notify students button
        if($request->has('message_type') && $request->message_type == 'quiz_notify'){

            $quizLesson = QuizLesson::where('quiz_id',$request->id)->where('lesson_id',$request->lesson_id)->first();
        
            $message = __('messages.quiz.quiz_notify', ['quizName' => $quizLesson->quiz->name, 'courseName' => $quizLesson->lesson->course->name]);

            //sending notifications     
            $notification = new QuizNotification($quizLesson,$message);

            if($request->filled('users')){
                $notification->setUsers($request->users);
            }

            $notification->send();

            return response()->json(['message' => 'Notification sent.','body' => null], 200);           
        }

        $notification = [
            'item_id' => $request->id,
            'item_type' => $request->type,
            'message' => $request->message,
            'type' => 'notification'
        ];

        if($request->class_id){
            $notification['classes'] = json_encode([$request->class_id]);
        }

        if($request->course_id){
            $notification['course_id'] = $request->course_id;
        }

        if($request->lesson_id){
            $notification['lesson_id'] = $request->lesson_id;
        }

        if($request->link){
            $notification['link'] = $request->link;
        }

        $from = Auth::id();
        if($request->from){
            $from = $request->from;
        }
        $notification['created_by'] = $from;

        $publish_date = Carbon::now();
        if($request->publish_date){
            $publish_date = $request->publish_date;
        }
        $notification['publish_date'] = $publish_date;

        //assign notification to given users
        $createdNotification = (new SendNotification)->toDatabase($notification,$request->users);
            
        //firebase Notifications
        (new SendNotification)->toFirebase($createdNotification);

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
