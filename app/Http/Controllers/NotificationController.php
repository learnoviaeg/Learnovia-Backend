<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Controllers\HelperController;
use App\User;
use Illuminate\Support\Facades\Auth;

use Illuminate\Http\Request;
use DB;
use Carbon\Carbon;

class NotificationController extends Controller
{
    public function index()
    {
        return user::notify([
            'message' => 'two',
            'from' => 1,
            'users' => [2],
            'course_id' => 2,
            'class_id'=>3,
            'type' => 'annoucn'
        ]);
    }

    //  get all Notification From data base From Notifcation Table
    public function getallnotifications(Request $request)
    {
        $noti = DB::table('notifications')->select('data')->where('notifiable_id', $request->user()->id)->get();
        $data=array();
        foreach ($noti as $not) {
            $not->data= json_decode($not->data, true);
            if(isset($not->data['publish_date'])){
                if($not->data['publish_date'] < Carbon::now())
                {
                    $data[] = $not->data;
                }
            }else{
                $data[] = $not->data;
            }
        }
        return HelperController::api_response_format(200, $body = $data, $message = 'all users notifications');
    }

    //  the unread Notification From data base From Notifcation Table
    public function unreadnotifications(Request $request)
    {
        $noti = DB::table('notifications')->select('data')->where('notifiable_id', $request->user()->id)->where('read_at', null)->get();
        foreach ($noti as $not) {
            $not->data= json_decode($not->data, true);
            if($not->data['publish_date'] < Carbon::now())
            {
                $data[] = $not->data;
            }
        }
        return HelperController::api_response_format(200, $body = $data, $message = 'all user Unread notifications');
    }

    //  make all the Notification Readto the user id
    public function markasread(Request $request)
    {
        $noti = DB::table('notifications')->where('notifiable_id', $request->user()->id)->update(array('read_at' => Carbon::now()->toDateTimeString()));
        return HelperController::api_response_format(200, null, 'Read');
    }

    public function GetNotifcations(Request $request)
    {
        $noti = DB::table('notifications')->where('notifiable_id', $request->user()->id)->pluck('data');
        foreach ($noti as $not) {
            $not= json_decode($not, true);
            if($not['publish_date'] < Carbon::now())
            {
                $data[] = $not;
            }
        }
        return HelperController::api_response_format(200, $body = $data, $message = 'all user notifications');
    }

    public function DeletewithTime(Request $request)
    {
        $request->validate([
            'startdate' => 'required|before:enddate|before:' . Carbon::now(),
            'enddate' => 'required'
        ]);
        $check = DB::table('notifications')->where('created_at', '>', $request->startdate)
            ->where('created_at', '<', $request->enddate)
            ->delete();
        return HelperController::api_response_format(200, $body = [], $message = 'notifications deleted');

    }

    public function SeenNotifications(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:notifications,id'
        ]);
        $session_id = Auth::User()->id;
        $note = DB::table('notifications')->where('id', $request->id)->first();
        if ($note->notifiable_id == $session_id){
            $notify =  DB::table('notifications')->where('id', $request->id)->update(['read_at' =>  Carbon::now()]);
            return HelperController::api_response_format(200, $body = [], $message = 'this notification  is seened successfully ');
        }
        return HelperController::api_response_format(400, $body = [], $message = 'you cannot seen this notification');

    }
}
