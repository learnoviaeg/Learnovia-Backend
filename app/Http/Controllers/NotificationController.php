<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Controllers\HelperController;
use App\User;
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
            'to' => 2,
            'course_id' => 2,
            'type' => 'annoucn'
        ]);
    }

    //  get all Notification From data base From Notifcation Table
    public function getallnotifications(Request $request)
    {
        $noti = DB::table('notifications')->select('data')->where('notifiable_id', $request->user()->id)->get();
        return HelperController::api_response_format(200, $body = $noti, $message = 'all users notifications');
    }

    //  the unread Notification From data base From Notifcation Table
    public function unreadnotifications(Request $request)
    {
        $noti = DB::table('notifications')->select('data')->where('notifiable_id', $request->user()->id)->where('read_at', null)->get();
        return HelperController::api_response_format(200, $body = $noti, $message = 'all user Unread notifications');
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
        return HelperController::api_response_format(200, $body = $noti, $message = 'all user notifications');
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
}
