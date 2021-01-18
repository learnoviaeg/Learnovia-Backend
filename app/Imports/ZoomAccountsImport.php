<?php

namespace App\Imports;

use Validator;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use App\AccountsZoom;
use App\User;
use GuzzleHttp\Client;

class ZoomAccountsImport implements ToModel, WithHeadingRow
{
    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        Validator::make($row,[
            'username'=>'required|exists:users,username',
            'jwt_token' => 'required',
            'api_key' => 'required',
            'api_secret' => 'required',
            'email' => 'required|email',
        ])->validate();

        $user_id = User::where('username',$row['username'])->first()->pluck('id');
        $token = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJhdWQiOm51bGwsImlzcyI6InZyeTdranJSUkQyUUNtRFppQmg5UFEiLCJleHAiOjE2MTE0Nzg3NzAsImlhdCI6MTYxMDg3Mzk3M30.N4OnPLELOMAAMCmv_U9ndErAuXBU8SMjmDUAbx7qPqc';

        $clientt = new Client();
        $res = $clientt->request('get', 'https://api.zoom.us/v2/users/hendgenady_97@outlook.com', [
            'headers'   => [
                'Authorization' => 'Bearer '. $token ,
                // 'Host' => '<calculated when request is sent>',
                 'Content-Type' => 'application/json',
            ]
        ]);
        $result= $res->getBody();
        dd($result);

        $useraccount = AccountsZoom::create([
            'user_id' => $user_id,
            'JWT_token' => $row['jwt_token'],
            'Api_key' => $row['api_key'],
            'Api_secret' => $row['api_secret'],
            'JWT_token' => $row['jwt_token'],
            'Email' => $row['email'],
            'user_zoom_id' => '',
        ]);

    }
}
