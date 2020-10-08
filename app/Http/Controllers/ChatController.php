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
            'participants.*' => 'string|exists:users,chat_uid',
            'name'=>'string',
            'text'=>'string',
        ]);

        $clientt = new Client();
        $data = array(
            'participants' => $request->participants
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
                'Content-Type' => 'application/json'
            ], 
            'body' => $data
        ]);
        
        $body = json_decode($res->getBody(),true);
        if(isset($body['room_id']))
            return HelperController::api_response_format(200, $body, 'Chat room created successfully');

        return HelperController::api_response_format(400, null, 'Error while generating chat room.');
        
    }
}
