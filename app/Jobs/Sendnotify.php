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
    public $course_id;
    public $class_id;
    public $lesson_id;
    public $publish_date;
    public $touserid;
    public $users;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($touserid, $message, $publish_date, $title, $type, $course_id, $class_id, $lesson_id)
    {
        $this->touserid=$touserid;
        $this->message=$message;
        $this->title=$title;
        $this->type=$type;
        $this->publish_date=$publish_date;
        $this->course_id=$course_id;
        $this->class_id=$class_id;
        $this->lesson_id=$lesson_id;
        $this->touserid;
        $this->users = [];
        foreach($this->touserid as $index => $user){
            if($user == null)
                continue;
            $this->users[] = $user->id;
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
            if($u != null)
                event(new \App\Events\notify($u->id, $this->message, $this->publish_date, $this->title, $this->type, $this->course_id,
                 $this->class_id, $this->lesson_id));
        }
    }
}
