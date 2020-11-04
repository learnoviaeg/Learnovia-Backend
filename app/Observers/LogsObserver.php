<?php

namespace App\Observers;

use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Log;
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
        Log::info(User::find(Auth::id())->username.' created '.$req);
    }

    /**
     * Handle the user "updated" event.
     *
     * @return void
     */
    public function updated($req)
    {
        Log::info(User::find(Auth::id())->username.' updated '.$req); 
    }

    /**
     * Handle the user "deleted" event.
     *
     * @return void
     */
    public function deleted($req)
    {
        Log::info(User::find(Auth::id())->username.' deleted '.$req);
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
        Log::info(User::find(Auth::id())->username.' deleted '.$req);
    }
}
