<?php

namespace App\Repositories;
use GuzzleHttp\Client;

class NotificationRepo implements NotificationRepoInterface
{
    public function sendNotify($users,$reqNot)
    {
        $data=[
            'users' => $users,
            // 'school_domain'=>substr(request()->getHost(),0,strpos(request()->getHost(),'api')),
            'school_domain'=>'test',
            // 'title'=> substr(request()->getHost(),0,strpos(request()->getHost(),'api')),
            'title'=> 'Learnovia',
            'body'=> $reqNot['title'],
            "item_type" => $reqNot['item_type'],
            "type" => $reqNot['type'],
            "item_id" => $reqNot['item_id'],
            'course_name' => isset($reqNot['course_name']) ? $reqNot['course_name'] : null,
            'lesson_id' => isset($reqNot['lesson_id']) ? $reqNot['lesson_id'] : null,
            'publish_date' => isset($reqNot['publish_date']) ? $reqNot['publish_date'] : null
        ];

        // $clientt = new Client();
        // $res = $clientt->request('POST', 'http://ec2-100-26-60-206.compute-1.amazonaws.com/api/send/notifications', [
        //     'headers'   => [
        //         'username' => 'test',
        //         'password' => 'api_test_5eOiG7CTC',
        //     ],
        //     'form_params' => $data
        // ]);

        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => 'http://ec2-100-26-60-206.compute-1.amazonaws.com/api/send/notifications',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS =>json_encode($data),
        CURLOPT_HTTPHEADER => array(
                'username: test',
                'password: api_test_5eOiG7CTC',
                'Content-Type: application/json'
            ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);

        return $response;
    }
}