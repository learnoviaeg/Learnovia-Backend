<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Controllers\HelperController;
use App\User;
use App\Lesson;
use Illuminate\Support\Facades\Auth;
use App\Events\MassLogsEvent;
use Illuminate\Http\Request;
use DB;
use Carbon\Carbon;
use App\Announcement;
use Modules\QuestionBank\Entities\QuizLesson;
use Modules\Assigments\Entities\AssignmentLesson;
use Modules\Page\Entities\pageLesson;
use Modules\Page\Entities\Page;
use Modules\UploadFiles\Entities\FileLesson;
use Modules\Assigments\Entities\assignment;
use Modules\Attendance\Entities\Attendance;
use Modules\Bigbluebutton\Entities\BigbluebuttonModel;
use Modules\UploadFiles\Entities\file;
use Modules\QuestionBank\Entities\Quiz;
use Modules\UploadFiles\Entities\media;
use Modules\UploadFiles\Entities\MediaLesson;
class NotificationController extends Controller
{
    public function get_google_token()
    {
        $client = new \Google_Client();
        $client->setAuthConfig(base_path('send-message-e290c-firebase-adminsdk-4oxh4-9afba5de5b.json'));
        $client->setApplicationName("send-message-e290c");
        $client->setScopes(['https://www.googleapis.com/auth/firebase.messaging']);

       $client->useApplicationDefaultCredentials();
       if ($client->isAccessTokenExpired()) {
            $client->fetchAccessTokenWithAssertion();
        }

        $access_token = $client->getAccessToken()['access_token'];

         $data = json_encode(array(
            'message' => array(
                "topic" => "mirna",
                "notification" => array(
                    "body" => "This is an FCM notification message!",
                    "title" => "FCM Message .."
                )
            )
          
        ));
        $clientt = new Client();
        $res = $clientt->request('POST', 'https://fcm.googleapis.com/v1/projects/send-message-e290c/messages:send', [
            'headers'   => [
                'Authorization' => 'Bearer '. $access_token,
                'Content-Type' => 'application/json'
            ], 
            'body' => $data
        ]);
        $result= $res->getBody();
        return $result;
    }
    
   /**
    * @description: get all Notifications From database From Notifcation Table of this user.
    * @param no required parameters
    * @return all notifications.
    */
    public function getallnotifications(Request $request)
    {
        $noti = DB::table('notifications')->select('data','read_at','id')->where('notifiable_id', $request->user()->id)->orderBy('created_at','desc')->get();
        $data=array();
        $i=0;
        foreach ($noti as $not) {
            $not->data= json_decode($not->data, true);

            if(isset($not->data['publish_date'])){
                if(Carbon::parse($not->data['publish_date']) < Carbon::now() && $not->data['type'] != 'announcement')
                {
                    $course_segments_ids = Auth::user()->enroll->pluck('course_segment');
                    $item_course_segment=Lesson::find($not->data['lesson_id']);
                    if(isset($item_course_segment))
                    {
                        $item_course_segment = $item_course_segment->courseSegment->id;
                    }
                    $data[$i]['id'] = $not->data['id'];
                    $data[$i]['read_at'] = $not->read_at;
                    $data[$i]['notification_id'] = $not->id;
                    $data[$i]['message'] = $not->data['message'];
                    $data[$i]['publish_date'] = Carbon::parse($not->data['publish_date'])->format('Y-m-d H:i:s');
                    $data[$i]['type'] = $not->data['type'];
                    $data[$i]['course_id'] = $not->data['course_id'];
                    $data[$i]['class_id'] = $not->data['class_id'];
                    $data[$i]['lesson_id'] = $not->data['lesson_id'];
                    $data[$i]['link'] = isset($not->data['link'])?$not->data['link']:null;

                    if(isset($not->data['title']))
                        $data[$i]['title'] = $not->data['title'];
                    $data[$i]['title'] = null;

                    if($not->data['type'] == 'quiz')
                        $data[$i]['item_lesson_id'] = QuizLesson::where('quiz_id',$not->data['id'])->where('lesson_id',$not->data['lesson_id'])->pluck('id')->first();
                    if($not->data['type'] == 'assignment')
                        $data[$i]['item_lesson_id'] = AssignmentLesson::where('assignment_id',$not->data['id'])->where('lesson_id',$not->data['lesson_id'])->pluck('id')->first();
                    if($not->data['type'] == 'file')
                        $data[$i]['item_lesson_id'] = FileLesson::where('file_id',$not->data['id'])->where('lesson_id',$not->data['lesson_id'])->pluck('id')->first();
                    if($not->data['type'] == 'media')
                        $data[$i]['item_lesson_id'] = MediaLesson::where('media_id',$not->data['id'])->where('lesson_id',$not->data['lesson_id'])->pluck('id')->first();
                    if($not->data['type'] == 'Page')
                        $data[$i]['item_lesson_id'] = pageLesson::where('page_id',$not->data['id'])->where('lesson_id',$not->data['lesson_id'])->pluck('id')->first();
                    if($not->data['type'] == 'meeting')
                        $data[$i]['item_lesson_id'] = BigbluebuttonModel::where('id', $not->data['id'])->pluck('id')->first();
                    if($not->data['type'] == 'Attendance')
                        $data[$i]['item_lesson_id'] = Attendance::where('id', $not->data['id'])->pluck('id')->first();

                    $deleted = 0 ;
                    // if object doesnot deleted or this student not enrolled in this course
                    if(!isset($data[$i]['item_lesson_id']) || ($not->data['type'] != 'meeting' && !in_array($item_course_segment,$course_segments_ids->toArray()))){
                        $deleted = 1;
                    }

                    $data[$i]['deleted'] = $deleted;
                }
            }
            $i++;
        }
        $final=array();
        foreach($data as $object)
            $final[]= $object;
            
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
            if($not->data['type'] != 'announcement')
            {
                $parse=Carbon::parse($not->data['publish_date'])->format('Y-m-d H:i:s');

                if(!isset($parse)){
                    $data[] = $not->data;
                }
                elseif($parse < Carbon::now())
                {
                    $data[] = $not->data;
                }
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
        $noti = DB::table('notifications')->where('notifiable_id', $request->user()->id)->get();
        foreach ($noti as $not) {
            $not->data= json_decode($not->data, true);
            if($not->data['type'] != 'announcement')
            {
                //for log event
                // $logsbefore=DB::table('notifications')->where('id', $not->id)->get();
                $check=DB::table('notifications')->where('id', $not->id)->update(['read_at' => Carbon::now()->toDateTimeString()]);
                // if($check > 0)
                //     event(new MassLogsEvent($logsbefore,'updated'));
            }
        }
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
        if(count($noti) == 0)
            return HelperController::api_response_format(200, 'there is no user notifications');
        $data=[];
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

        //for log event
        $logsbefore=DB::table('notifications')->where('created_at', '>', $request->startdate)
                        ->where('created_at', '<', $request->enddate)->get();
        $check = DB::table('notifications')->where('created_at', '>', $request->startdate)
                    ->where('created_at', '<', $request->enddate)
                    ->delete();
        if($check > 0)
            event(new MassLogsEvent($logsbefore,'deleted'));
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
            // 'id' => 'exists:notifications,id',
            'type' => 'string|in:assignment,Attendance,meeting,Page,quiz,file,media|required_with:id',
            'id' => 'int',
            'message' => 'string|required_with:id'
        ]);
        $session_id = Auth::User()->id;

        if(isset($request->id))
        {
            $noti = DB::table('notifications')->where('notifiable_id', $request->user()->id)->get();
            foreach ($noti as $not) {
                $not->data= json_decode($not->data, true);
                if($not->data['type'] != 'announcement')
                {
                    //for log event
                    // $logsbefore=DB::table('notifications')->where('id', $not->id)->get();

                    if($not->data['id'] == $request->id && $not->data['type'] == $request->type && $not->data['message'] == $request->message)
                        $check=DB::table('notifications')->where('id', $not->id)->update(['read_at' => Carbon::now()->toDateTimeString()]);
                
                    // if($check > 0)
                    //     event(new MassLogsEvent($logsbefore,'updated'));
                }
            }
            $print=self::getallnotifications($request);
            return $print;
        }
        else
        {
            // $noti = DB::table('notifications')->where('notifiable_id', $request->user()->id)->update(array('read_at' => Carbon::now()->toDateTimeString()));
            $noti = DB::table('notifications')->where('notifiable_id', $request->user()->id)->get();
            foreach ($noti as $not) {
                $not->data= json_decode($not->data, true);
                if($not->data['type'] != 'announcement'){
                    //for log event
                    // $logsbefore=DB::table('notifications')->where('id', $not->id)->get();
                    $check=DB::table('notifications')->where('id', $not->id)->update(['read_at' => Carbon::now()->toDateTimeString()]);
                    // if($check > 0)
                    //     event(new MassLogsEvent($logsbefore,'updated'));
                }
            }
            $print=self::getallnotifications($request);
            return $print;
        }
        return HelperController::api_response_format(400, $body = [], $message = 'you cannot seen this notification');
    }

    public function Notification_token(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
        ]);

        $user=$request->user();
        $user->token=$request->token;
        $user->save();
        
        return HelperController::api_response_format(200, 'token added Done');
    }

    public function change_page()
    {

        $notify = DB::table('notifications')->get();

        foreach($notify as $notify_object) {
            $decoded_data= json_decode($notify_object->data, true);
            if($decoded_data['type'] == 'Page')
                $decoded_data['type']='page';
        }
        return 'done';
    }
}
