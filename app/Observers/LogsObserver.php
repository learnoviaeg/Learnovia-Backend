<?php

namespace App\Observers;

use App\Jobs\createdLogsJob;
use App\Jobs\updatedLogsJob;
use App\Jobs\deletedLogsJob;
use App\Log;
use App\User;
use Auth;

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

        $dispatch=(new createdLogsJob($req));
        dispatch($dispatch);
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

        // $user = User::find(Auth::id());

        // Log::create([
        //     'user' => isset($user) ? $user->username : 'installer',
        //     'action' => 'updated',
        //     'model' => substr(get_class($req),strripos(get_class($req),'\\')+1),
        //     'data' => serialize($arr),
        // ]);

        $dispatch=(new updatedLogsJob($req));
        dispatch($dispatch);
    }

    /**
     * Handle the user "deleted" event.
     *
     * @return void
     */
    public function deleted($req)
    {
        $user = User::find(Auth::id());

        Log::create([
            'user' => isset($user) ? $user->username : 'installer',
            'action' => 'deleted',
            'model' => substr(get_class($req),strripos(get_class($req),'\\')+1),
            'data' => serialize($req),
        ]);

        // $dispatch=(new deletedLogsJob($req));
        // dispatch($dispatch);
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
