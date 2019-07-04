<?php
namespace App\Http\Controllers;
 
use App\Http\Controllers\Controller;
use App\Message;
use App\User;
use App\Notifications\NewMessage;
use Illuminate\Support\Facades\Notification;
use Illuminate\Notifications\Notifiable;
use Illuminate\Http\Request;
use Validator;
use  DB;
use Carbon\Carbon;
class NotificationController extends Controller
{
                  // Sending Notification 
  public function notify(Request $request)
  {

    $validater=Validator::make($request->all(),[
      'message'=>'required',
      'from'=>'required|integer|exists:users,id',
      'to'=>'required|integer|exists:users,id',
      'course_id'=>'required|integer|exists:courses,id',
      'type'=>'required|string'
      ]);
  
  if ($validater->fails())
  {
      $errors=$validater->errors();
      return response()->json($errors,400);
  }
  
    $touserid=$request->to;
    $toUser = User::find($touserid);
    Notification::send($toUser, new NewMessage($request));
  }


                      //  get all Notification From data base From Notifcation Table
  public function getallnotifications(Request $request){
    //$downloads=DB::table('lectures')->get();
    $noti=DB::table('notifications')->select('data')->where('notifiable_id',$request->id)->get();    
    return $noti;

  }

                      //  the unread Notification From data base From Notifcation Table
  public function unreadnotifications(Request $request){
    //$downloads=DB::table('lectures')->get();
    $noti=DB::table('notifications')->select('data')->where('notifiable_id',$request->id)->where('read_at',null)->get();    
    return $noti;

  }
                      //  make alll the Notification Readto the user id
  public function markasread(Request $request){
    //$downloads=DB::table('lectures')->get();
   // dd(Carbon::now()->toDateTimeString());

    $noti=DB::table('notifications')->where('notifiable_id',$request->id)->update(array('read_at' => Carbon::now()->toDateTimeString()));    
    

  }
}