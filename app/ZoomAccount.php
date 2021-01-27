<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ZoomAccount extends Model
{
    protected $fillable = ['user_id','jwt_token','api_key','api_secret','user_zoom_id','email'];

    public function user()
    {
        return $this->belongsTo('App\User','user_id','id');
    }
    
    public function generate_jwt_token($key,$secret)
    {
        $header = json_encode([
            'typ' => 'JWT',
            'alg' => 'HS256'
        ]);
        $payload = json_encode([
            "iss" => $key,
            "exp"=> time()+3600
        ]);

        $base64UrlHeader = base64_encode($header);

        // Encode Payload
        $base64UrlPayload = base64_encode($payload);

        // Create Signature Hash
        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $secret, true);

        // Encode Signature to Base64Url String
        $base64UrlSignature = base64_encode($signature);

        // Create JWT
        $jwt = $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;

        return $jwt;
    }
}
