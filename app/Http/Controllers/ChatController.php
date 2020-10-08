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
        
        return json_decode($res->getBody(),true);
    }
}
