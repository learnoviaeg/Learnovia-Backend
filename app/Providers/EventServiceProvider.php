<?php

namespace App\Providers;

use Illuminate\Support\Facades\Event;
use App\Events\GradeItem;
use App\Events\CreatedGradeItem;
use App\Listeners\RecordsUserGrade;
use App\Listeners\CreateUserGrade;
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
        // 'App\Events\GradeItem' => [
        //     'App\Listeners\RecordsUserGrade',
        // ],
        // 'App\Events\CreatedGradeItem' => [
        //     'App\Listeners\CreateUserGrade',
        // ],
        CreatedGradeItem::class => [
            CreateUserGrade::class,
        ],
    ];
    // protected $subscribe = [
    //     'App\Listeners\CreateUserGrade',
    //  ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        // Event::listen('App\Event\CreatedGradeItem',function($event)
        // {
        //     dd($event);
        // });
    }
}
