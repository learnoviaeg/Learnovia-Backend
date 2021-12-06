<?php

namespace App\Jobs;

use App\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class createAndAttachNoti implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $notification;

        /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($notification,$users)
    {
        $this->notification = $notification;
        $this->users = $users;
    }

        /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $createdNotification = Notification::create($this->notification);
        $createdNotification->users()->attach($this->users);
        return $createdNotification;
    }
}