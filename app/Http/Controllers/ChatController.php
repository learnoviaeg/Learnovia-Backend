<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class ChatController extends Controller
{
    public function chat_room(Request $request)
    {
        $request->validate([
            'participants' => 'array|required',
            'participants.*' => 'string',//|exists:users,chat_uid',
            'name'=>'string',
            'text'=>'string',

        ]);

        $client = new \Google_Client();
        $client->setAuthConfig(base_path('learnovia-notifications-firebase-adminsdk-z4h24-17761b3fe7.json'));
        $client->setApplicationName("learnovia-notifications");
        $client->setScopes(['https://www.googleapis.com/auth/firebase.messaging']);
        $client->useApplicationDefaultCredentials();
        if ($client->isAccessTokenExpired()) {
            $client->fetchAccessTokenWithAssertion();
        }
        $access_token = $client->getAccessToken()['access_token']; 
        $clientt = new Client();
        $data = array(
            'participants' => $request->participants,
        
        );
        if(isset($request->name)){
            $data['name'] = $request->name;
        }
        if(isset($request->text)){
            $data['initial_message'] =array(
                "text" => $request->text,
                'type'=> "string"
            );
        }
        
        $data = json_encode($data);
        $res = $clientt->request('POST', 'https://us-central1-akwadchattest.cloudfunctions.net/createRoom', [
            'headers'   => [
                'Authorization' => 'Bearer '. $access_token,
                'Content-Type' => 'application/json'
            ], 
            'body' => $data
        ]);
        return $res; 
    }
}
