<?php

namespace App\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,    
        ],

        'App\Events\MassLogsEvent' => [
            'App\Listeners\MassLogsListener',
        ],

        'App\Events\GradeItemEvent' => [
            'App\Listeners\ItemDetailslistener',
        ],

        'App\Events\QuizAttemptEvent' => [
            'App\Listeners\AttemptItemlistener',
        ],

        'App\Events\GradeAttemptEvent' => [
            'App\Listeners\GradeAttemptItemlistener',
        ],
        'App\Events\RefreshGradeTreeEvent' => [
            'App\Listeners\RefreshGradeTreeListener',
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        //
    }
}
