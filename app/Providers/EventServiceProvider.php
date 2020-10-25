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
        
        'App\Events\UserGradeEvent' => [
            'App\Listerners\UserGradeListener',
        ],

        'App\Events\AssignmentLessonEvent' => [
            'App\Listerners\CreateAssignmentLessonListener',
            'App\Listerners\UpdateAssignmentLessonListener',
            'App\Listerners\DeleteAssignmentLessonListener',
        ],

        'App\Events\QuizLessonEvent' => [
            'App\Listerners\CreateQuizLessonListener',
            'App\Listerners\UpdateQuizLessonListener',
            'App\Listerners\QuizLessonListener',
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
