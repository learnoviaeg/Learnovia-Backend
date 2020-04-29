<?php

namespace App\Jobs;
use Illuminate\Support\Facades\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Notifications\NewMessage;


class Sendnotify implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $request;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct( $request)
    {
        $this->request=$request;
        dd($request);
        // $this->users = [];
        // foreach($this->touserid as $index => $user){
        //     if($user == null)
        //         continue;
        //     $this->users[] = $user->id;
        // }
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $client = new \Google_Client();
        $client->setAuthConfig(base_path('learnovia-notifications-firebase-adminsdk-z4h24-17761b3fe7.json'));
        $client->setApplicationName("learnovia-notifications");
        $client->setScopes(['https://www.googleapis.com/auth/firebase.messaging']);

        $client->useApplicationDefaultCredentials();
        if ($client->isAccessTokenExpired()) {
            $client->fetchAccessTokenWithAssertion();
        }
        $access_token = $client->getAccessToken()['access_token'];
        $user_token=User::whereIn('id',$this->request['users'])->whereNotNull('token')->pluck('token');
        foreach($user_token as $token)
        {
            if($this->request['type']=='announcement')
            {
                $this->request['message']='A New Announcement Added';
                $fordata = array(
                    "id" => (string)$this->request['id'],
                    "type" => $this->request['type'],
                    "message" => $this->request['message'],
                    "publish_date" => $this->request['publish_date'],
                    "read_at" => null
                );
            }else{
                $fordata = array(
                    "id" => (string)$this->request['id'],
                    "message" => $this->request['message'],
                    "fromm" => (string)$this->request['from'],
                    "type" => $this->request['type'],
                    "course_id" => (string)$this->request['course_id'],
                    "class_id" => (string)$this->request['class_id'],
                    "lesson_id"=> (string)$this->request['lesson_id'],
                    "publish_date" => $this->request['publish_date'],
                    "read_at" => null
                );
            }
            $data = json_encode(array(
                'message' => array(
                    "token" => $token,
                    "notification" => array(
                        "body" => $this->request['message'],
                        "title" => 'Learnovia',
                        "image" => "http://169.44.167.50/backend/public/storage/Announcement/5e958c73bf38bindex.jpg"
                    ),
                    "webpush" => array(
                        "fcm_options" => array(
                            "link" => "http://dev.learnovia.com",
                            "analytics_label" => "Learnovia"
                        ),
                        "data" => $fordata
                    ) 
                )
            ));
            $clientt = new Client();
            $res = $clientt->request('POST', 'https://fcm.googleapis.com/v1/projects/learnovia-notifications/messages:send', [
                'headers'   => [
                    'Authorization' => 'Bearer '. $access_token,
                    'Content-Type' => 'application/json'
                ], 
                'body' => $data
            ]);
        }

        // foreach ($this->touserid as $u){
        //     if($u != null)
        //         event(new \App\Events\notify($u->id, $this->message, $this->publish_date, $this->title, $this->type, $this->course_id,
        //          $this->class_id, $this->lesson_id));
        // }
    }
}
