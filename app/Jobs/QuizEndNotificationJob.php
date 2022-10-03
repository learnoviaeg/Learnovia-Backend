<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Carbon\Carbon;
use App\Repositories\ChainRepositoryInterface;
use Illuminate\Http\Request;
use App\Repositories\NotificationRepoInterface;
use App\User;

class QuizEndNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $quizLesson;
    public $chain;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($quizLesson)
    {
        $this->quizLesson = $quizLesson;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(ChainRepositoryInterface $chain ,NotificationRepoInterface $notification)
    {
        $this->chain = $chain;
        $this->notification = $notification;

        $interval = (new \DateTime($this->quizLesson->due_date))->diff(new \DateTime($this->quizLesson->start_date));
        $difference_between_now_and_due = (new \DateTime($this->quizLesson->due_date))->diff(carbon::now());
        // dd($difference_between_now_and_due);

        if($interval->days == 0 && $interval->h < 1 )
            return ;

        if($interval->days <= 1){
            ///send notification before quiz emds by an hour
            if($difference_between_now_and_due->h != 1)
                return ;
            $notification_date = Carbon::parse($this->quizLesson->due_date)->subHour();
            $resulted_date = Carbon::parse($notification_date);
            
        }


        if($interval->days > 1){
            ///send notification before quiz emds by a day
            $notification_date = Carbon::parse($this->quizLesson->due_date)->subDays(1);
            $resulted_date = Carbon::parse($notification_date);
            if(!$notification_date->isToday())
                return ;

        }

        if(isset($resulted_date) && $this->quizLesson->closing_notification == 0){
            //not restricted
            if($this->quizLesson->quiz->restricted == false ){
                $req = new Request([
                    'courses' => [$this->quizLesson->quiz->course_id],
                ]);
                $students = $this->chain->getEnrollsByManyChain($req)->select('user_id')->where('role_id', 3)->distinct('user_id')->pluck('user_id');  
            }
            //restricted
            if($this->quizLesson->quiz->restricted == true ){
                $students = $this->quizLesson->quiz->CourseItem->courseItemUsers->pluck('user_id');
            }

            $quizLesson = $this->quizLesson;
            $callback = function ($query) use ($quizLesson) {
                $query->where('quiz_lesson_id', $quizLesson->id)->whereNotNull('submit_time');
            };

            $users = User::select('id')->whereIn('id',$students)->whereDoesntHave('userQuiz',$callback)->pluck('id');
            $reqNot=[
                'message' => 'Quiz '.$this->quizLesson->quiz->name.' will be closed soon',
                'item_id' => $this->quizLesson->quiz_id,
                'item_type' => 'Quiz',
                'type' => 'notification',
                'publish_date' => $resulted_date->format('Y-m-d H:i:s'),
                'lesson_id' => $this->quizLesson->lesson_id,
                'course_name' => $this->quizLesson->quiz->course->name,
                'course_id' => $this->quizLesson->quiz->course->id,
            ];

           $this->notification->sendNotify($users,$reqNot);
           $this->quizLesson->update(['closing_notification' => 1]);
        }

    }
}
