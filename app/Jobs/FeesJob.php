<?php

namespace App\Jobs;
use Illuminate\Http\Request;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Installment;
use App\Repositories\ChainRepositoryInterface;
use App\Repositories\NotificationRepoInterface;
use Carbon\Carbon;
use App\Parents;

class FeesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $installment;
    public $chain;
    public $notification;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Installment $installment , $chain , $notification)
    {
        $this->installment = $installment;
        $this->chain = $chain;
        $this->notification = $notification;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $reqNot=[
            'message' => 'Fees is due by '.$this->installment->date ,
            'item_id' => $this->installment->id,
            'item_type' => 'fees',
            'type' => 'notification',
            'publish_date' => Carbon::now()->format('Y-m-d H:i:s'),
        ];
        $Installment_percentage = Installment::where('date' , '<=' , Carbon::parse($this->installment->date)->format('Y-m-d'))->sum('percentage');
        $students = $this->chain->getEnrollsByManyChain(new Request())->select('user_id')->distinct('user_id')->where('role_id', 3)
                    ->whereHas('user.fees',function($q) use ($Installment_percentage){  $q->where('percentage', '>', $Installment_percentage );  })->pluck('user_id');

        $users = Parents::select('parent_id')->distinct('parent_id')->whereIn('child_id', $students)->pluck('parent_id');
        if($users->count() > 0)
            $this->notification->sendNotify($users->toArray(),$reqNot);
        
    }
}
