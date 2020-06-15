<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Controllers\HelperController;
use App\User;
use App\Lesson;
use Illuminate\Support\Facades\Auth;

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
                    $item_course_segment=Lesson::find($not->data['lesson_id'])->courseSegment->id;
                    switch($request->type){
                        case "assignment":
                             $object = Assignment::find( $not->data['id']);
                             $data[$i]['item_lesson_id'] = AssignmentLesson::where('assignment_id',$not->data['id'])->where('lesson_id',$not->data['lesson_id'])->pluck('id')->first();
                        break;
                        case "Attendance": 
                            $object = Attendance::find( $not->data['id']);
                        break;   
                        case "meeting" :
                            $object = BigbluebuttonModel::find( $not->data['id']);
                        break;
                        case "Page":
                            $object = Page::find( $not->data['id']);
                            $data[$i]['item_lesson_id'] = pageLesson::where('page_id',$not->data['id'])->where('lesson_id',$not->data['lesson_id'])->pluck('id')->first();

                        break;    
                        case "quiz": 
                            $object = Quiz::find( $not->data['id']);
                            $data[$i]['item_lesson_id'] = QuizLesson::where('quiz_id',$not->data['id'])->where('lesson_id',$not->data['lesson_id'])->pluck('id')->first();

                        break;
                        case "file": 
                            $object = file::find( $not->data['id']);
                            $data[$i]['item_lesson_id'] = FileLesson::where('file_id',$not->data['id'])->where('lesson_id',$not->data['lesson_id'])->pluck('id')->first();

                        break; 
                        case "media": 
                            $object = media::find( $not->data['id']);
                            $data[$i]['item_lesson_id'] = MediaLesson::where('media_id',$not->data['id'])->where('lesson_id',$not->data['lesson_id'])->pluck('id')->first();

                        break;
                    }
                    $deleted = 0 ;
                    // if object doesnot deleted or this student not enrolled in this course
                    if(!isset($object) || !in_array($item_course_segment,$course_segments_ids->toArray())){
                        $deleted = 1;
                    }
                    
                    $data[$i]['id'] = $not->data['id'];
                    $data[$i]['read_at'] = $not->read_at;
                    $data[$i]['notification_id'] = $not->id;
                    $data[$i]['message'] = $not->data['message'];
                    $data[$i]['publish_date'] = $not->data['publish_date'];
                    $data[$i]['type'] = $not->data['type'];
                    $data[$i]['course_id'] = $not->data['course_id'];
                    $data[$i]['class_id'] = $not->data['class_id'];
                    $data[$i]['lesson_id'] = $not->data['lesson_id'];
                    $data[$i]['deleted'] = $deleted;

                    if(isset($not->data['title']))
                        $data[$i]['title'] = $not->data['title'];
                    $data[$i]['title'] = null;

         
                }
            }
            $i++;
        }
        $final=array();
        foreach($data as $object)
        {
            $final[]= $object;
        }
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
                $parse=Carbon::parse($not->data['publish_date']);

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
                DB::table('notifications')->where('id', $not->id)->update(array('read_at' => Carbon::now()->toDateTimeString()));
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
            // 'id' => 'exists:notifications,id',
            'type' => 'string|in:assignment,Attendance,meeting,Page,quiz,file,media|required_with:id',
            'id' => 'int',
            'message' => 'string|required_with:id'
        ]);
        $session_id = Auth::User()->id;

        if(isset($request->id))
        {
            // $note = DB::table('notifications')->where('id', $request->id)->first();
            // if ($note->notifiable_id == $session_id){
            //     $notify =  DB::table('notifications')->where('id', $request->id)->update(['read_at' =>  Carbon::now()]);
                // $print=self::getallnotifications($request);
                // return $print;
            // }
              
            $noti = DB::table('notifications')->where('notifiable_id', $request->user()->id)->get();
            foreach ($noti as $not) {
                $not->data= json_decode($not->data, true);
                if($not->data['type'] != 'announcement')
                {
                    if($not->data['id'] == $request->id && $not->data['type'] == $request->type && $not->data['message'] == $request->message)
                    {
                        DB::table('notifications')->where('id', $not->id)->update(array('read_at' => Carbon::now()->toDateTimeString()));
                    }
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
                if($not->data['type'] != 'announcement')
                {
                    DB::table('notifications')->where('id', $not->id)->update(array('read_at' => Carbon::now()->toDateTimeString()));
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
}
