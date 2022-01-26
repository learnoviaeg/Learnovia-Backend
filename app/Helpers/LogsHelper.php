<?php

namespace App\Helpers;
use App\Log;

class LogsHelper
{
    public function logs($request, $users)
    {
        //users ==> effected user
        foreach ($users as $user)
        {
            $Log=Log::create([
                'user' => $request['username'],
                'action' => $request['action'],
                'model' => $request['model'],
                'data' => $request['data'],
                'effected_users' => $user
            ]);
        }
    }
}