<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Log;
use Illuminate\Http\Request;
use Auth;
use App\User;

class createdLogsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $req;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($request)
    {
        $this->req = $request;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // addednew
        // $namespace = '\\App\\'; 
        // $entity = substr(get_class($this->req),strripos(get_class($this->req),'\\')+1);
        // $model = $namespace . $entity;
        // $year_get_value = $model::get_year_name($this->req, null);
        // addednew

        $user = User::find(Auth::id());
        $log  = Log::create([
            'user'       => isset($user) ? $user->username : 'installer',
            'action'     => 'created',
            'model'      => substr(get_class($this->req),strripos(get_class($this->req),'\\')+1),
            'data'       => serialize($this->req),
            // addednew
            'model_id'   => $this->req->id,
            'user_id'    => isset($user) ? $user->id : 0,
            'year_id'    => null, 
            'type_id'    => null, 
            'level_id'   => null, 
            'class_id'   => null, 
            'segment_id' => null, 
            'course_id'  => null,
            // addednew
        ]);
    }
}
