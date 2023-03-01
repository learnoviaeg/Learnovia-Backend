<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use APp\Enroll;

class EnrollAdminJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $user_id;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($user_id)
    {
        $this->user_id=$user_id;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $Enrolls=Enroll::where('user_id',1)->get();

        foreach($Enrolls as $enroll)
        {
            Enroll::firstOrCreate([
                'user_id' => $this->user_id,
                'role_id' => 1,
                'year' => $enroll->year,
                'type' => $enroll->type,
                'level' => $enroll->level,
                'group' => $enroll->group,
                'segment' => $enroll->segment,
                'course' => $enroll->course,
            ]);
        }
    }
}
