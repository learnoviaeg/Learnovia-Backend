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
    public $title;
    public $type;
    public $publish_date;
    public $touserid;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($touserid,$message,$publish_date,$title,$type)
    {
        $this->touserid=$touserid;
        $this->message=$message;
        $this->title=$title;
        $this->type=$type;
        $this->publish_date=$publish_date;
        foreach ($this->touserid as $u){
            dd($u->id);
        }
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        foreach ($this->touserid as $u){
            event(new \App\Events\notify($u->id ,$this->message,$this->publish_date,$this->title,$this->type));
        }
    }
}
