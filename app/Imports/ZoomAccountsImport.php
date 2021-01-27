<?php

namespace App\Imports;

use Validator;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use App\ZoomAccount;
use App\User;
use GuzzleHttp\Client;
use App\Http\Controllers\ZoomAccountController;
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
            // 'jwt_token' => 'required',
            'api_key' => 'required',
            'api_secret' => 'required',
            'email' => 'required|email',
        ])->validate();

        $user_id = User::where('username',$row['username'])->pluck('id')->first();
        $jwt_token=ZoomAccount::generate_jwt_token($row['api_key'],$row['api_secret']);

        $curl = curl_init();
        curl_setopt_array($curl, array(
        CURLOPT_URL => "https://api.zoom.us/v2/users/". $row['email'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_HTTPHEADER => array(
            "authorization: Bearer " . $jwt_token,
            "content-type: application/json"
        ),
        ));

        $response = curl_exec($curl);

        // $err = curl_error($curl);
        // curl_close($curl);

        // if ($err) {
        //     return [
        //         'success' 	=> false, 
        //         'msg' 		=> 'cURL Error #:' . $err,
        //         'response' 	=> ''
        //     ];
        // }

        // throw new \Exception(__('messages.zoom.zoom_account'));

        $useraccount = ZoomAccount::create([
            'user_id' => $user_id,
            'jwt_token' => $jwt_token,
            'api_key' => $row['api_key'],
            'api_secret' => $row['api_secret'],
            'email' => $row['email'],
            'user_zoom_id' => (json_decode($response,true)['id']),
        ]);

    }
}
