<?php

namespace App\Listeners;

use App\Events\MassLogsEvent;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Auth;
use App\Log;
use App\User;
use App\Repositories\EnrollmentRepositoryInterface;

class MassLogsListener
{
    protected $unEnroll;
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(EnrollmentRepositoryInterface $unEnroll)
    {
        $this->unEnroll = $unEnroll;
    }

    /**
     * Handle the event.
     *
     * @param  MassLogsEvent  $event
     * @return void
     */
    public function handle(MassLogsEvent $event)
    {
        // foreach($event->log as $one)
        // {
        //     $arr=array();
        //     // DD($model);
        //     if($event->action == 'updated'){
        //         $arr['before']=$one->getOriginal();
        //         // eval('$arr[\'after\']= new '.$model);
        //         $arr['after']=get_class($one)::where('id',$one->id)->get();
        //         Log::create([
        //             'user' => User::find(Auth::id())->username,
        //             'action' => 'updated',
        //             'model' => substr(get_class($one),strripos(get_class($one),'\\')+1),
        //             'data' => serialize($arr),
        //         ]);
        //     }
        //     if($event->action == 'deleted'){
        //         $log = Log::create([
        //             'user' => User::find(Auth::id())->username,
        //             'action' => 'deleted',
        //             'model' => substr(get_class($one),strripos(get_class($one),'\\')+1),
        //             'data' => serialize($one),
        //         ]);

        //         if($log->model == 'Enroll'){
        //             $this->unEnroll->RemoveAllDataRelatedToRemovedChain($one);
        //         }
        //     }
                
        //     //for DB object updated ---> handle error in get original
        //     Log::create([
        //         'user' => User::find(Auth::id())->username,
        //         'action' => 'updated',
        //         'model' => substr(get_class($one),strripos(get_class($one),'\\')+1),
        //         'data' => serialize($one),
        //     ]);
        // }
    }
}
