<?php

namespace App\Events;

use App\GradeItems;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class GradeItem
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    
    public $grade;
    
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(GradeItems $grade)
    {
        $this->$grade=$grade;
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
