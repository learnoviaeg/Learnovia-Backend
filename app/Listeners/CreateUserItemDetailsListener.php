<?php

namespace App\Listeners;

use App\Events\UserItemDetailsEvent;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class CreateUserItemDetailsListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  UserItemDetailsEvent  $event
     * @return void
     */
    public function handle(UserItemDetailsEvent $event)
    {
        //
    }
}
