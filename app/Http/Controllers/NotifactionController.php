<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\User;
use App\Notifications\Notificationlearnovia;
use Illuminate\Support\Facades\Notification;
use Illuminate\Notifications\Notifiable;
use Illuminate\Http\Request;
use Validator;
use  DB;
use Carbon\Carbon;
use App\Http\Controllers\HelperController;
class NotifactionController extends Controller
{
    public function index()
    {
        return user::notify([
            'message' => 'two',
            'from'=>1,
            'to'=>2,
            'course_id'=>2,
            'type'=>'annoucn'
        ]);
        
    }
    //
//     public function notify(Request $request)
//   {

//     $validater=Validator::make($request->all(),[
//       'message'=>'required',
//       'from'=>'required|integer|exists:users,id',
//       'to'=>'required|integer|exists:users,id',
//       'course_id'=>'required|integer|exists:courses,id',
//       'type'=>'required|string'
//       ]);
  
//   if ($validater->fails())
//   {
//       $errors=$validater->errors();
//       return response()->json($errors,400);
//   }
  
//     $touserid=$request->to;
//     $toUser = User::find($touserid);
//     Notification::send($toUser, new Notificationlearnovia($request));
//   }


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

  public function GetNotifcations(Request $request)
  {
    $request->validate([
        'userid' => 'required|exists:users,id'

    ]);
    $noti=DB::table('notifications')->where('notifiable_id',$request->userid)->pluck('data');
    $data=array();
    foreach($noti as $not)
    {
        $data[]=json_decode($not,true);
       
    }
    return HelperController::api_response_format(200, $body = $data, $message = 'all user notifications');    
}
    public function DeletewithTime(Request $request)
{
    $request->validate([
        'startdate' => 'required|before:enddate|before:'.Carbon::now(),
        'enddate' => 'required'
    ]);
    $check=DB::table('notifications')->where('created_at','>',$request->startdate)
    ->where('created_at','<',$request->enddate)
    ->delete();
    return HelperController::api_response_format(200, $body = [], $message = 'notifications deleted');    

}
}