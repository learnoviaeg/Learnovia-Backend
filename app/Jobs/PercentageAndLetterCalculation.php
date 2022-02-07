<?php
 
namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Events\GradeCalculatedEvent;
use App\Course;

class PercentageAndLetterCalculation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void  
     */
    public $course; 

    public function __construct(Course $course)
    {
        $this->course = $course;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        foreach($this->course->gradeCategory as $cat)
        {
            foreach($cat->userGrades as $user_grader){
                if($cat->max != null && $cat->max > 0){
                    $percentage = ($user_grader->grade / $cat->max) * 100;

                    $user_grader->update([
                        'percentage' => $percentage,
                    ]);
                    event(new GradeCalculatedEvent($user_grader));

                }           
            }
        }  
    }
}
