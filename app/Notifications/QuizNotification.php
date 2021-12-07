<?php

namespace App\Notifications;

use App\Enroll;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Modules\QuestionBank\Entities\QuizLesson;

class QuizNotification extends SendNotification
{
    public $quizLesson, $message, $lesson, $users;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(QuizLesson $quizLesson,$message)
    {
        $this->quizLesson = $quizLesson;
        $this->message = $message;
        $this->lesson = $this->quizLesson->lesson;
        $this->users = Enroll::whereIn('group',$this->lesson->shared_classes->pluck('id'))
                        ->where('course',$this->lesson->course_id)
                        ->where('user_id','!=',Auth::user()->id)
                        ->where('role_id','!=', 1 )->select('user_id')->distinct()->pluck('user_id')->toArray();
    }

    public function setUsers(array $users){
        $this->users = $users;
    }

    public function send(){

        $publish_date = $this->quizLesson->publish_date;
        if(Carbon::parse($publish_date)->isPast()){
            $publish_date = Carbon::now();
        }

        //Start preparing notifications object
        $notification = [
            'type' => 'notification',
            'item_id' => $this->quizLesson->quiz_id,
            'item_type' => 'quiz',
            'message' => $this->message,
            'publish_date' => $publish_date,
            'created_by' => $this->quizLesson->quiz->created_by,
            'lesson_id' => $this->lesson->id,
            'course_id' => $this->lesson->course_id,
            'classes' => json_encode($this->lesson->shared_classes->pluck('id')),
        ];

        //assign notification to given users
        $createdNotification = $this->toDatabase($notification,$this->users);
        
        //firebase Notifications
        $this->toFirebase($createdNotification);
    }
}
