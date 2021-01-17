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
use Exception;
use Carbon\Carbon;
use DB;

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
        $client = new \Google_Client();
        $client->setAuthConfig(base_path('learnovia-notifications-firebase-adminsdk-z4h24-17761b3fe7.json'));
        $client->setApplicationName("learnovia-notifications");
        $client->setScopes(['https://www.googleapis.com/auth/firebase.messaging']);
        $client->useApplicationDefaultCredentials();
        if ($client->isAccessTokenExpired()) {
            $client->fetchAccessTokenWithAssertion();
        }
        $access_token = $client->getAccessToken()['access_token'];
        // Log::debug('access_token is '.$access_token);
        
        $user_token=User::whereIn('id',$this->request['users'])->whereNotNull('token')->get();
        // dd($this->request['users']);
        // Log::debug(' users are '. $this->request['users']);

        Log::debug('
        num of users is '. count($user_token));
        $count =0;
        foreach($user_token as $token)
        {
             Log::debug('user number ' .($count+1).' is  '. $token->id);

            $notification_id = null;
            $noti = DB::table('notifications')->where('notifiable_id', $token->id)->get();
            foreach ($noti as $not) {
                $not->data= json_decode($not->data, true);
                if($not->data['type'] == $this->request['type'] && $not->data['id'] == $this->request['id'] && $not->data['message'] == $this->request['message'])
                {
                    $notification_id = $not->id;
                }
            }

            // Log::debug('notifictaion id is' . $notification_id);
            if($this->request['type'] !='announcement'){
                $fordata = array(
                        "id" => (string)$this->request['id'],
                        "message" => $this->request['message'],
                        "fromm" => isset($this->request['from']) ? (string)$this->request['from'] : null,
                        "type" => $this->request['type'],
                        "course_id" => (string)$this->request['course_id'],
                        "class_id" => (string)$this->request['class_id'],
                        "lesson_id"=> (string)$this->request['lesson_id'],
                        "publish_date" => Carbon::parse($this->request['publish_date'])->format('Y-m-d H:i:s'),
                        "read_at" => null,
                        "link" => isset($this->request['link'])?$this->request['link']:null,
                        'deleted'=> "0",
                        'notification_id' => $notification_id,
                        "course_name" => (string)$this->request['course_name'],
                    );

            }else{
                

                if($this->request['attached_file'] != null)
                    $att= (string) $this->request['attached_file'];
                else
                    $att=$this->request['attached_file'];
                    $fordata = array(
                        "id" => (string)$this->request['id'],
                        "type" => $this->request['type'],
                        "message" => $this->request['message'],
                        "publish_date" => Carbon::parse($this->request['publish_date'])->format('Y-m-d H:i:s'),
                        "read_at" => null,
                        "title" => $this->request['title'],
                        "description" => $this->request['description'],
                        "start_date" => $this->request['start_date'],
                        "due_date" => $this->request['due_date'],
                        "attached_file" => $att,
                        'deleted'=> '0',
                        'notification_id' => $notification_id
                    );
            }
            $count++;
            
            $url= config('app.url');
            $url = substr($url, 0, strpos($url, "api"));
            $opne_link = 'learnovia.com';
            if(isset($url)){
                $opne_link = $url.'.learnovia.com/';
            }
            $data = json_encode(array(
                'message' => array(
                    "token" => $token->token,
                    "notification" => array(
                        "body" => $this->request['message'],
                        "title" => 'Learnovia',
                        "image" => "http://169.44.167.50/backend/public/storage/Announcement/5e958c73bf38bindex.jpg"
                    ),
                    "webpush" => array(
                        "fcm_options" => array(
                            "link" => $opne_link,
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
            } catch (\Exception $e) {

               Log::debug( $e->getMessage());
            }
        }
    }
}