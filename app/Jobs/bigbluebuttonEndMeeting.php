<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Modules\Bigbluebutton\Entities\BigbluebuttonModel;

class bigbluebuttonEndMeeting implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $bigbb;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($bigbb)
    {
        $this->bigbb=$bigbb;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $meetings = BigbluebuttonModel::where('meeting_id',$this->bigbb['meeting_id'])->where('status','future')->where('started',0)->update([
            'status' => 'past'
        ]);
    }
}
