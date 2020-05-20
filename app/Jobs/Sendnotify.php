<?php

namespace App\Jobs;
use Illuminate\Support\Facades\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Notifications\NewMessage;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use App\User;
// use Illuminate\Support\Facades\Log;
use Log;
// use Exception;



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
  
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Log::debug('starting  jop ');
        $client = new \Google_Client();
        $client->setAuthConfig(base_path('learnovia-notifications-firebase-adminsdk-z4h24-17761b3fe7.json'));
        $client->setApplicationName("learnovia-notifications");
        $client->setScopes(['https://www.googleapis.com/auth/firebase.messaging']);
        $client->useApplicationDefaultCredentials();
        if ($client->isAccessTokenExpired()) {
            $client->fetchAccessTokenWithAssertion();
        }
        $access_token = $client->getAccessToken()['access_token'];
        Log::debug('access_token is '.$access_token);

        $user_token=User::whereIn('id',$this->request['users'])->whereNotNull('token')->pluck('token');
        // dd($this->request['users']);
        // Log::debug(' users '. $this->request['users']);

        Log::debug(' num users_token is '. count($user_token));
        $count =0;
        foreach($user_token as $token)
        {
            Log::debug('single token is '. $token);

            if($this->request['type'] !='announcement'){
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
                    Log::debug('type is not announcement '+  $fordata );
            }else{

                $this->request['message']='A new announcement is added';
                Log::debug('type is announcement and count is '. $count);
                if($this->request['attached_file'] != null)
                    $att= (string) $this->request['attached_file'];
                else
                    $att=$this->request['attached_file'];
                    $fordata = array(
                        "id" => (string)$this->request['id'],
                        "type" => $this->request['type'],
                        "message" => $this->request['message'],
                        "publish_date" => $this->request['publish_date'],
                        "read_at" => null,
                        "title" => $this->request['title'],
                        "description" => $this->request['description'],
                        "start_date" => $this->request['start_date'],
                        "due_date" => $this->request['due_date'],
                        "attached_file" => $att,
                    );
            }
            $count++;
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
                            "link" => "https://dev.learnovia.com",
                            "analytics_label" => "Learnovia"
                        ),
                        "data" => $fordata
                    ) 
                )
            ));
            Log::debug("data is " . $data);
            $clientt = new Client();
            try {
                $res = $clientt->request('POST', 'https://fcm.googleapis.com/v1/projects/learnovia-notifications/messages:send', [
                    'headers'   => [
                        'Authorization' => 'Bearer '. $access_token,
                        'Content-Type' => 'application/json'
                    ], 
                    'body' => $data
                ]);              
                Log::debug('request success');
            } catch (RequestException $ex) {
                
                Log::debug('request fails');
            }
            

        }

     
    }
}
