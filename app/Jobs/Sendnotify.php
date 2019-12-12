<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class Sendnotify implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $message;
    public $publish_data;
    public $touserid;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($touserid,$message,$publish_data)
    {
        $this->touserid=$touserid;
        $this->message=$message;
        $this->publish_data=$publish_data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        foreach ($this->touserid as $u){
            event(new \App\Events\notify($u->id ,$this->message,$this->publish_data));
        }
    }
}
