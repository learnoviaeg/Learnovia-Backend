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

        $validater = Validator::make($request->all(), [
            'message' => 'required',
            'from' => 'required|integer|exists:users,id',
            'to' => 'required|integer|exists:users,id',
            'course_id' => 'required|integer|exists:courses,id',
            'type' => 'required|string'
        ]);

        if ($validater->fails()) {
            return HelperController::api_response_format(400, $validater->errors());
        }
        $touserid = $request->to;
        $toUser = User::find($touserid);
        Notification::send($toUser, new NewMessage($request));
        return HelperController::api_response_format(200, [], 'Sent');
    }


    //  get all Notification From data base From Notifcation Table
    public function getallnotifications(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:notifications,notifiable_id'
        ]);
        $noti = DB::table('notifications')->select('data')->where('notifiable_id', $request->id)->get();
        return HelperController::api_response_format(200, $noti);
    }

    //  the unread Notification From data base From Notifcation Table
    public function unreadnotifications(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:notifications,notifiable_id'
        ]);
        $noti = DB::table('notifications')->select('data')->where('notifiable_id', $request->id)->where('read_at', null)->get();
        return HelperController::api_response_format(200, $noti);

    }

    //  make alll the Notification Readto the user id
    public function markasread(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:notifications,notifiable_id'
        ]);
        $noti = DB::table('notifications')->where('notifiable_id', $request->id)->update(array('read_at' => Carbon::now()->toDateTimeString()));
        return HelperController::api_response_format(200, $noti , 'Marked as read');
    }
}