<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use App\User;

class ChatController extends Controller
{
    public function chat_room(Request $request)
    {
        $request->validate([
            'participants' => 'array|required',
            'participants.*' => 'string|exists:users,id',
            'name'=>'string',
            'text'=>'string',
        ]);

        $participants_chat_id = collect();
        foreach($request->participants as $participant){
            $chat_uid = User::whereId($participant)->pluck('chat_uid')->first();
            if(isset($chat_uid))
                $participants_chat_id->push($chat_uid);
        }

        $clientt = new Client();
        $data = array(
            'participants' => $participants_chat_id
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

    public function refresh_token(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            ]);
            $clientt = new Client();
            $user= User::find($request->user_id);
            $data = array(
                'refresh_token' => $user->refresh_chat_token
            );
            
            $data = json_encode($data);
            $res = $clientt->request('POST', 'https://us-central1-akwadchattest.cloudfunctions.net/refreshToken', [
                'headers'   => [
                    'Content-Type' => 'application/json'
                ], 
                'body' => $data
            ]);
            $body = json_decode($res->getBody(),true);

            $user->refresh_chat_token = $body['refresh_token'];
            $user->chat_token  = $body['id_token'];
            $user->save();

            return HelperController::api_response_format(200, $body, 'Token is refreshed');

            }



}
