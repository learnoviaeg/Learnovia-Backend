<?php

namespace App\Jobs;

use App\Announcement;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;

class SendNotifications implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $notification;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($notification)
    {
        $this->notification = $notification;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        //get list of users that should recieve this notification and has firebase token
        $users = $this->notification->users()->whereNotNull('token')->get();

        //create a new google clinet and guzzle client
        $googleClient = new \Google_Client();
        $client = new Client();

        //generate access token to firebase API
        $googleClient->setAuthConfig(base_path('learnovia-notifications-firebase-adminsdk-z4h24-17761b3fe7.json'));

        $googleClient->setApplicationName("learnovia-notifications");

        $googleClient->setScopes(['https://www.googleapis.com/auth/firebase.messaging']);

        $googleClient->useApplicationDefaultCredentials();

        if($googleClient->isAccessTokenExpired()){
            $googleClient->fetchAccessTokenWithAssertion();
        }

        $access_token = $googleClient->getAccessToken()['access_token'];
        //end of genrating access token

        //check notification type to prepare the realtime object
        if($this->notification->type == 'notification'){

            $notificationObject = [
                "item_id" => (string) $this->notification->item_id,
                "message" => $this->notification->message,
                "fromm" => (string) $this->notification->created_by,
                "item_type" => $this->notification->item_type,
                "type" => $this->notification->type,
                "course_id" => (string) $this->notification->course_id,
                "classes" => (string) $this->notification->classes,
                "lesson_id"=> (string) $this->notification->lesson_id,
                "publish_date" => Carbon::parse($this->notification->publish_date)->format('Y-m-d H:i:s'),
                "read_at" => null,
                "link" => $this->notification->link,
                'deleted'=> "0",
                'id' => (string) $this->notification->id,
                "course_name" => $this->notification->course ? $this->notification->course->name : null,
                "lesson" => $this->notification->lesson ? (string) $this->notification->lesson : null
            ];
        }

        if($this->notification->type == 'announcement'){

            $announcement = Announcement::find($this->notification->item_id);

            $notificationObject = [
                "item_id" => (string) $this->notification->item_id,
                "message" => $this->notification->message,
                "fromm" => (string) $this->notification->created_by,
                "item_type" => $this->notification->item_type,
                "type" => $this->notification->type,
                "publish_date" => Carbon::parse($this->notification->publish_date)->format('Y-m-d H:i:s'),
                "read_at" => null,
                'deleted'=> "0",
                'id' => (string) $this->notification->id,
                "title" => $announcement->title,
                "description" => $announcement->description,
                "start_date" => $announcement->start_date,
                "due_date" => $announcement->due_date,
                "attached_file" => $announcement->attachment ? $announcement->attachment->path : null,
            ];
        }
        //end preparing notifications object 

        $failedIds = array();
        //start loopong users to send them firebase notifications
        foreach($users as $key => $user)
        {

            //open link when notification arrives to device
            $apiURL= config('app.url');
            $systemURL = substr($apiURL, 0, strpos($apiURL, "api"));

            $onClickLink = 'learnovia.com';
            if(isset($url)){
                $onClickLink = $systemURL.'.learnovia.com/';
            }
            //end getting notifications URL

            //prepare notification request body
            $requestBody = json_encode(
                array(
                    'message' => array(
                        "token" => $user->token,

                        "notification" => array(
                            "body" => $this->notification->message,
                            "title" => 'Learnovia'
                        ),

                        "webpush" => array(
                            "fcm_options" => array(
                                "link" => $onClickLink,
                                "analytics_label" => "Learnovia"
                            ),

                            "data" => $notificationObject
                        ) 
                    )
                )
            );
            //end preparing notifications request body

            //start sending realtime notifications
            try {

                $client->request('POST', 'https://fcm.googleapis.com/v1/projects/learnovia-notifications/messages:send', [

                    'headers'   => [
                        'Authorization' => 'Bearer '. $access_token,
                        'Content-Type' => 'application/json'
                    ], 

                    'body' => $requestBody
                ]);     

            } catch (\Exception $e) {
                Log::debug( $e->getMessage());
                array_push($failedIds,$user->id);
                unset($users[$key]);
            }
            //end sending realtime notification
        }

        $this->notification->users()->whereIn('user_id',$users->pluck('id'))->update([
            'real_time_status' => 'sent'
        ]);

        $this->notification->users()->whereIn('user_id',$failedIds)->update([
            'real_time_status' => 'failed'
        ]);
        
        //end sending firebase notifications
    }
}
