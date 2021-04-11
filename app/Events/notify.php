<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class notify implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    public $user_id;
    public $message;
    public $title;
    public $type;
    public $course_id;
    public $class_id;
    public $lesson_id;
    public $publish_date;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($user_id, $message, $publish_date, $title, $type, $course_id, $class_id, $lesson_id)
    {
        $this->user_id=$user_id;
        $this->message=$message;
        $this->title=$title;
        $this->type=$type;
        $this->publish_date=$publish_date;
        $this->course_id=$course_id;
        $this->class_id=$class_id;
        $this->lesson_id=$lesson_id;
    }
    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new Channel(''.$this->user_id);
    }
}