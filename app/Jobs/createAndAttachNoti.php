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
    public $users;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The maximum number of unhandled exceptions to allow before failing.
     *
     * @var int
     */
    public $maxExceptions = 3;

        /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    // public $timeout = 120;

        /**
     * Indicate if the job should be marked as failed on timeout.
     *
     * @var bool
     */
    // public $failOnTimeout = true;

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
        // return $createdNotification;
    }
}