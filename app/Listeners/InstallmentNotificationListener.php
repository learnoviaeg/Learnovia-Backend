<?php

namespace App\Listeners;

use App\Events\CreateInstallmentEvent;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\NotificationSetting;
use App\Installment;
use App\Fees;
use Carbon\Carbon;

class InstallmentNotificationListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
    }

    /**
     * Handle the event.
     *
     * @param  CreateInstallmentEvent  $event
     * @return void
     */
    public function handle(CreateInstallmentEvent $event)
    {
        $notification_settings =  NotificationSetting::select('after_min')->where('type' , 'fees')->first();
        if(isset($notification_settings))
            $notification_settings_days = $notification_settings->after_min;

        if((isset($notification_settings) && $notification_settings->after_min > 0) )
        {    
            foreach(Installment::cursor() as $installment)
            {
                $notification_date = Carbon::parse($installment->date)->subDays($notification_settings_days);
                $resulted_date = Carbon::parse($notification_date);
                $seconds = Carbon::parse($resulted_date->diffInSeconds(Carbon::now()));
                $job = (new \App\Jobs\FeesJob($installment, $event->chain, $event->notification))->delay($seconds);
                dispatch($job);
            }
        }
    }
}
