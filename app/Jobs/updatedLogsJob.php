<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class updatedLogsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($request)
    {
        $this->req=$request;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $arr=array();
        $arr['before']=$this->req->getOriginal();
        $arr['after']=$this->req;

        Log::create([
            'user' => isset($user) ? $user->username : 'installer',
            'action' => 'updated',
            'model' => substr(get_class($this->req),strripos(get_class($this->req),'\\')+1),
            'data' => serialize($arr),
        ]);
    }
}
