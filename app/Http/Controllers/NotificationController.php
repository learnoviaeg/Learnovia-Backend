<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Controllers\HelperController;
use App\User;
use Illuminate\Support\Facades\Auth;

use Illuminate\Http\Request;
use DB;
use Carbon\Carbon;
use App\Announcement;

class NotificationController extends Controller
{
   /**
    * @description: get all Notifications From database From Notifcation Table of this user.
    * @param no required parameters
    * @return all notifications.
    */
    public function getallnotifications(Request $request)
    {
        $noti = DB::table('notifications')->select('data','read_at','id')->where('notifiable_id', $request->user()->id)->get();
        $data=array();
        $i=0;
        foreach ($noti as $not) {
            $not->data= json_decode($not->data, true);

            if(isset($not->data['publish_date'])){
                if($not->data['publish_date'] < Carbon::now() && $not->data['type'] != 'announcement')
                {
                    $data[$i] = $not->data;
                    $data[$i]['read_at'] = $not->read_at;
                    $data[$i]['notification_id'] = $not->id;
                    $data[$i]['message'] = $not->data['message'];
                    $data[$i]['publish_date'] = $not->data['publish_date'];
                    $data[$i]['type'] = $not->data['type'];
                }
            else{
                if ($not->data['type'] == 'announcement')
                    {
                        $announce_id = $not->data['id'];
                        $annocument = announcement::find($announce_id);
                        if($annocument!= null){
                            if ($annocument->publish_date <= Carbon::now()) {
                                $customize = announcement::whereId($announce_id)->first(['id', 'title', 'description']);
                                $data[$i]=$customize;
                                $data[$i]['read_at'] = $not->read_at;
                                $data[$i]['notification_id'] = $not->id;
                                $data[$i]['message'] = $not->data['message'];
                                $data[$i]['publish_date'] = $not->data['publish_date'];
                                $data[$i]['type'] = $not->data['type'];
                            }
                        }
                    }
            }
        }
            $i++;
        }
        $final=array();
        foreach($data as $object)
        {
            $final[]= $object;
        }
        // return $data;
        return HelperController::api_response_format(200, $body = $final, $message = 'all users notifications');
    }
   /**
    * @description: get unread Notifications From database From Notifcation Table
    * @param no required parameters
    * @return all unread notifications.
    */
    public function unreadnotifications(Request $request)
    {
        $data = [];
        $noti = DB::table('notifications')->select('data')->where('notifiable_id', $request->user()->id)->where('read_at', null)->get();
        foreach ($noti as $not) {
            $not->data= json_decode($not->data, true);
            if(!isset($not->data['publish_date'])){
                $data[] = $not->data;
            }
            elseif($not->data['publish_date'] < Carbon::now())
            {
                $data[] = $not->data;
            }
        }
        return HelperController::api_response_format(200, $data,'all user Unread notifications');
    }
   /**
    * @description: mark all the notifications of this user as read.
    * @param no required parameters
    * @return all notifications.
    */
    public function markasread(Request $request)
    {
        $noti = DB::table('notifications')->where('notifiable_id', $request->user()->id)->update(array('read_at' => Carbon::now()->toDateTimeString()));
        return HelperController::api_response_format(200, null, 'Read');
    }
   /**
    * @description: gets all the notifications of this user.
    * @param no required parameters
    * @return all notifications.
    */
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
   /**
    * @description: delete all the notifications within a time.
    * @param startdate and enddate are required parameters
    * @return string message which indicates that deletion done successfully.
    */
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
   /**
    * @description: mark a notification as seen.
    * @param id of notification
    * @return a string message which indicates that seeing notification done successfully.
    */
    public function SeenNotifications(Request $request)
    {
        $request->validate([
            'id' => 'exists:notifications,id',
        ]);
        $session_id = Auth::User()->id;
        if(isset($request->id))
        {
            $note = DB::table('notifications')->where('id', $request->id)->first();
            if ($note->notifiable_id == $session_id){
                $notify =  DB::table('notifications')->where('id', $request->id)->update(['read_at' =>  Carbon::now()]);
                $print=self::getallnotifications($request);
                return $print;
            }
        }
        else
        {
            $noti = DB::table('notifications')->where('notifiable_id', $request->user()->id)->update(array('read_at' => Carbon::now()->toDateTimeString()));
            $print=self::getallnotifications($request);
            return $print;
        }
        return HelperController::api_response_format(400, $body = [], $message = 'you cannot seen this notification');

    }
}