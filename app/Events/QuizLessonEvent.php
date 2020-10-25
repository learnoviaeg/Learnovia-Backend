<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Modules\QuestionBank\Entities\QuizLesson;

class QuizLessonEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    public $quiz_lesson;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(QuizLesson $quiz_lesson)
    {
        $this->quiz_lesson = $quiz_lesson;
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
