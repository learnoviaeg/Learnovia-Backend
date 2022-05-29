<?php

namespace App\Repositories;
use GuzzleHttp\Client;

class NotificationRepo implements NotificationRepoInterface
{
    public function sendNotify($users,$message,$item_id,$type,$item_type)
    {
        $data=[
            'users' => $users,
            // 'school_domain'=>substr(request()->getHost(),0,strpos(request()->getHost(),'api')),
            'school_domain'=>'test',
            // 'title'=> substr(request()->getHost(),0,strpos(request()->getHost(),'api')),
            'title'=> 'Learnovia',
            'body'=> $message,
            "item_type" => $item_type,
            "type" => $type,
            "item_id" => $item_id,
        ];
        $clientt = new Client();
        $res = $clientt->request('POST', 'http://ec2-18-212-48-229.compute-1.amazonaws.com/api/send/notifications', [
            'headers'   => [
                'username' => 'test',
                'password' => 'api_test_5eOiG7CTC',
            ],
            'form_params' => $data
        ]);
        return $res;
    }
}