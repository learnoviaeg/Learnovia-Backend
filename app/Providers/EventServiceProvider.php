<?php

namespace App\Providers;

use Illuminate\Support\Facades\Event;
use App\Providers\DispatcherContract;
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

        'App\Events\CreatedGradeCatEvent' => [
            'App\Listeners\IncreaseIndexListener',
        ],

        'App\Events\RefreshGradeTreeEvent' => [
            'App\Listeners\RefreshGradeTreeListener',
        ],

        'App\Events\UpdatedAttemptEvent' => [
            'App\Listeners\FireAutoCorrectionEventListener',
        ],

        'App\Events\GradeAttemptEvent' => [
            'App\Listeners\GradeAttemptItemlistener',
        ],

        'App\Events\UpdatedQuizQuestionsEvent' => [
            'App\Listeners\UpdateQuizGradeListener',
            'App\Listeners\createTimelineListener',
            'App\Listeners\updateWeightDetailsListener',
        ],

         'App\Events\UserEnrolledEvent' => [
            'App\Listeners\AddUserGradersListener',
        ],

        'App\Events\CourseCreatedEvent' => [
            'App\Listeners\EnrollAdminListener',
        ],

        'App\Events\LessonCreatedEvent' => [
            'App\Listeners\AddSecondChainListener',
        ],

        'App\Events\TakeAttendanceEvent' => [
            'App\Listeners\CalculateSessionsListener',
        ],

        'App\Events\SessionCreatedEvent' => [
            'App\Listeners\LogsCreatedListener',
        ],

        //7esab daragat el2s2la el manual(and_why & essay)
        // 'App\Events\ManualCorrectionEvent' => [
        //     'App\Listeners\GradeManualListener',
        // ],

        'App\Events\updateQuizAndQuizLessonEvent' => [
            'App\Listeners\updateTimelineListener',
            // 'App\Listeners\updateGradeCatListener',
        ],    

        'App\Events\GraderSetupEvent' => [
            'App\Listeners\RefreshGraderSetupListener',
        ],

        'App\Events\UserGradesEditedEvent' => [
            'App\Listeners\CalculateUserGradesListener',
        ],

        'App\Events\AssignmentCreatedEvent' => [
            'App\Listeners\AssignmentGradeCategoryListener',
        ],

        'App\Events\GradeCalculatedEvent' => [
            'App\Listeners\LetterPercentageListener',
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
