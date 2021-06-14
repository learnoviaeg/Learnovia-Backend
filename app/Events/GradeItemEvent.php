<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class GradeItemEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $grade_item;
    public $type;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    // public function __construct($grade_item,$type)
    public function __construct($grade_item)
    {
        // dd($grade_item);
        $this->grade_item=$grade_item;
        // $this->type=$type;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }
}
