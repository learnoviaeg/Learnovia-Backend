<?php

namespace App\Observers;

use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Log;
use App\User;

class LogsObserver
{
    /**
     * Handle the user "created" event.
     *
     * @return void
     */
    public function created($req)
    {
        // $user = User::find(Auth::id());
        // $log=Log::create([
        //     'user' => isset($user) ? $user->username : 'installer',
        //     'action' => 'created',
        //     'model' => substr(get_class($req),strripos(get_class($req),'\\')+1),
        //     'data' => serialize($req),
        // ]);
    }

    /**
     * Handle the user "updated" event.
     *
     * @return void
     */
    public function updated($req)
    {
        // $arr=array();
        // $arr['before']=$req->getOriginal();
        // $arr['after']=$req;

        // Log::create([
        //     'user' => isset($user) ? $user->username : 'installer',
        //     'action' => 'updated',
        //     'model' => substr(get_class($req),strripos(get_class($req),'\\')+1),
        //     'data' => serialize($arr),
        // ]);
    }

    /**
     * Handle the user "deleted" event.
     *
     * @return void
     */
    public function deleted($req)
    {
        Log::create([
            'user' => User::find(Auth::id())->username,
            'action' => 'deleted',
            'model' => substr(get_class($req),strripos(get_class($req),'\\')+1),
            'data' => serialize($req),
        ]);
    }

    /**
     * Handle the user "restored" event.
     *
     * @return void
     */
    public function restored($req)
    {
        //
    }

    /**
     * Handle the user "force deleted" event.
     *
     * @return void
     */
    public function forceDeleted($req)
    {
        //
    }
}
