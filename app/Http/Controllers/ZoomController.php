<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ZoomController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $jwtToken = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJhdWQiOm51bGwsImlzcyI6InZyeTdranJSUkQyUUNtRFppQmg5UFEiLCJleHAiOjE2MTE0Nzg3NzAsImlhdCI6MTYxMDg3Mzk3M30.N4OnPLELOMAAMCmv_U9ndErAuXBU8SMjmDUAbx7qPqc';
        
        $requestBody = [
            'topic'			=> $meetingConfig['topic'] 		?? 'PHP General Talk',
            // 1 >> instance meeting
            // 2 >> schedualed meeting
            // 3 >> meeting without fixed time
            // 8 >> instance meeting without fixed time
            'type'			=> 8,
            'start_time'	=> $meetingConfig['start_time']	?? date('Y-m-dTh:i:00').'Z',
            'duration'		=> $meetingConfig['duration'] 	?? 30,
            'password'		=> '123456',
            'timezone'		=> 'Africa/Cairo',
            'agenda'		=> 'Learnovia',
            'settings'		=> [
                    'host_video'			=> false,
                    'participant_video'		=> true,
                    'cn_meeting'			=> false,
                    'in_meeting'			=> false,
                    'join_before_host'		=> false,
                    'mute_upon_entry'		=> true,
                    'watermark'				=> false,
                    'use_pmi'				=> false,
                    'approval_type'			=> 1,
                    'registration_type'		=> 1,
                    'audio'					=> 'voip',
                    'auto_recording'		=> $meetingConfig['record'],
                    'waiting_room'			=> false
            ]
        ];

        //must be a function with url https://api.zoom.us/v2/users/hendgenady_97@outlook.com Authrization:token of JWT
        $zoomUserId = 'tAlMrgAvQ12wj-KvJmtXnw';

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0); // Skip SSL Verification
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.zoom.us/v2/users/".$zoomUserId."/meetings",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode($requestBody),
            CURLOPT_HTTPHEADER => array(
            "Authorization: Bearer ".$jwtToken,
            "Content-Type: application/json",
            "cache-control: no-cache"
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            return [
                'success' 	=> false, 
                'msg' 		=> 'cURL Error #:' . $err,
                'response' 	=> ''
            ];
        } else {
            return [
                'success' 	=> true,
                'msg' 		=> 'success',
                'response' 	=> json_decode($response)
            ];
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
