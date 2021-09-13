<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Modules\Bigbluebutton\Entities\BigbluebuttonModel;
use Log;

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
        Log::debug('job of bbb -> meeting id is '.$this->bigbb['meeting_id']);
        $meetings = BigbluebuttonModel::where('meeting_id',$this->bigbb['meeting_id'])->update([
            'status' => 'past'
        ]);
        
    }
}
