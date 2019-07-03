<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HelperController extends Controller
{
    public static function api_response_format($code, $body = [], $message = [])
    {
        return response()->json([
            'message' => $message,
            'body' => $body
        ], $code);
    }

    public static function NOTFOUND(){
        return response()->json([
            'message' => 'NotFOund',
            'body' => []
        ], 404);
    }
}
