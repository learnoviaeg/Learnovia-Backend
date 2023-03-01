<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

use App\Course;
use App\SecondaryChain;
use App\Lesson;
use App\Events\LessonCreatedEvent;

class CourseTemplateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $request;
    public $tries = 5;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    
    public function __construct($request)
    {
        $this->request=$request;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // dd($this->request['courses']);
        foreach($this->request['courses'] as $course){
            $shared_ids = [];
            $classes_of_course = Course::find($course);
            if($this->request['old_lessons'] == 0){
                $old_lessons = Lesson::where('course_id', $course);
                // $secondary_chains = SecondaryChain::whereIn('lesson_id',$old_lessons->get())->where('course_id',$course)->get()->delete();
                $old_ids =  $old_lessons->pluck('id'); 
            }
            foreach ($classes_of_course->classes as $key => $class) {
                if($this->request['old_lessons'] == 0) 
                    $secondary_chains = SecondaryChain::where('group_id',$class)->whereIn('lesson_id',$old_ids)->where('course_id',$course)->delete();                             
                
                $lessonsPerGroup = SecondaryChain::select('lesson_id')->where('group_id',$class)->where('course_id',$this->request['template_id'])->distinct('lesson_id')->pluck('lesson_id');
                // dd($lessonsPerGroup);
                $new_lessons = Lesson::whereIn('id', $lessonsPerGroup)->get();
                foreach($new_lessons as $lesson){
                    if(($key == 0 &&  $this->request['old_lessons'] == 1) || ($key != 0 &&  $this->request['old_lessons'] == 1 && json_decode($lesson->getOriginal('shared_classes')) == [$class] )){
                        $id = lesson::create([
                            'name' => $lesson->name,
                            'index' => $lesson->index,
                            'shared_lesson' => $lesson->shared_lesson,
                            'course_id' => $course,
                            'shared_classes' => $lesson->getOriginal('shared_classes'),
                            'description' => $lesson->description,
                        ]);
                        $shared_ids[] = $id->id;
                    }else{
                        $id = lesson::firstOrCreate([
                            'name' => $lesson->name,
                            'index' => $lesson->index,
                            'shared_lesson' => $lesson->shared_lesson,
                            'course_id' => $course,
                            'shared_classes' => $lesson->getOriginal('shared_classes'),
                            'description' => $lesson->description,
                        ]);
                        event(new LessonCreatedEvent(Lesson::find($id->id)));
                        $shared_ids[] = $id->id;
                    }
                }
            }

            if($this->request['old_lessons'] == 0){
                Lesson::whereIn('id',$old_ids)->whereNotIn('id',$shared_ids)->delete();
            }
        }
    }
}
