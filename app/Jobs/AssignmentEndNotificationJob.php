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

class AssignmentEndNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $assignmentLesson;
    public $chain;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($assignmentLesson)
    {
        $this->assignmentLesson = $assignmentLesson;
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

        $interval = (new \DateTime($this->assignmentLesson->due_date))->diff(new \DateTime($this->assignmentLesson->start_date));
        $difference_between_now_and_due = (new \DateTime($this->assignmentLesson->due_date))->diff(carbon::now());
        // dd($difference_between_now_and_due);
    
        if($interval->days == 0 && $interval->h < 1 )
            return ;

        if($interval->days < 1 && $interval->h > 1 ){
            if($difference_between_now_and_due->h != 1)
                return ;
            ///send notification before assignment emds by an hour
            $notification_date = Carbon::parse($this->assignmentLesson->due_date)->subHour();
            $resulted_date = Carbon::parse($notification_date);
            
        }

        if($interval->days >= 1){
            ///send notification before assignment emds by a day
            $notification_date = Carbon::parse($this->assignmentLesson->due_date)->subDays(1);
            $resulted_date = Carbon::parse($notification_date);
            if(!$notification_date->isToday())
                return ;

        }

        if(isset($resulted_date) && $this->assignmentLesson->closing_notification == 0){
            //not restricted
            if($this->assignmentLesson->Assignment[0]->restricted == false ){
                $req = new Request([
                    'courses' => [$this->assignmentLesson->Lesson->course_id],
                ]);
                $students = $this->chain->getEnrollsByManyChain($req)->select('user_id')->where('role_id', 3)->distinct('user_id')->pluck('user_id');  
            }
            //restricted
            if($this->assignmentLesson->Assignment[0]->restricted == true ){
                $students = $this->assignmentLesson->Assignment[0]->CourseItem->courseItemUsers->pluck('user_id');
            }

            $assignmentLesson = $this->assignmentLesson;
            $callback = function ($query) use ($assignmentLesson) {
                $query->where('assignment_lesson_id', $assignmentLesson->id)->whereNotNull('submit_date');
            };
            $users = User::select('id')->whereIn('id',$students)->whereDoesntHave('userAssignment',$callback)->pluck('id');
            $reqNot=[
                'message' => 'Assignment '.$this->assignmentLesson->Assignment[0]->name.' will be closed soon',
                'item_id' => $this->assignmentLesson->assignment_id,
                'item_type' => 'Assignment',
                'type' => 'notification',
                'publish_date' => $resulted_date->format('Y-m-d H:i:s'),
                'lesson_id' => $this->assignmentLesson->lesson_id,
                'course_name' => $this->assignmentLesson->Lesson->course->name,
                'course_id' => $this->assignmentLesson->Lesson->course_id,
            ];

           $this->notification->sendNotify($users,$reqNot);
           $this->assignmentLesson->update(['closing_notification' => 1]);
        }

    }
}
