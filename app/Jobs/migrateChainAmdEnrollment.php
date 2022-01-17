<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Course;
use App\Segment;
use App\GradeCategory;
use App\Lesson;
use App\Enroll;
use Modules\QuestionBank\Entities\QuestionsCategory;

class migrateChainAmdEnrollment implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $segment_id;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($segment_id)
    {
        $this->segment_id=$segment_id;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // dd($this->segment_id);
        $newSegment=Segment::find($this->segment_id);
        $type=$newSegment->academic_type_id;
        $oldSegment=Segment::Get_current_by_one_type($type);
        $courses=Course::where('segment_id',$oldSegment)->get();

        foreach($courses as $course)
        {
            if(count(Course::where('segment_id',$newSegment->id)->where('short_name',$course->short_name . "_" .$newSegment->name)->first()) > 0)
                continue;
                
            $coco=Course::firstOrCreate([
                'name' => $course->name. "_" .$newSegment->name,
                'short_name' => $course->short_name . "_" .$newSegment->name,
                'image' => $course->image,
                'category_id' => $course->category,
                'description' => $course->description,
                'mandatory' => $course->mandatory,
                'level_id' => $course->level_id,
                'is_template' => $course->is_template,
                'classes' => json_encode($course->classes),
                'segment_id' => $newSegment->id,
                'letter_id' => $course->letter_id
            ]);

            for ($i = 1; $i <= 4; $i++) {
                $lesson=lesson::firstOrCreate([
                    'name' => 'Lesson ' . $i,
                    'index' => $i,
                    'shared_lesson' => 1,
                    'course_id' => $coco->id,
                    'shared_classes' => json_encode($course->classes),
                ]);
            }

            //Creating defult question category
            $quest_cat = QuestionsCategory::firstOrCreate([
                'name' => $coco->name . ' Category',
                'course_id' => $coco->id,
            ]);

            $gradeCat = GradeCategory::firstOrCreate([
                'name' => $coco->name . ' Total',
                'course_id' => $coco->id,
                'calculation_type' => json_encode(['Natural']),
            ]);

            $enrolls=Enroll::where('course',$course->id)->whereIn('segment',$oldSegment->toArray())->where('type',$type)->get()->unique();
            foreach($enrolls as $enroll)
            {
                $f=Enroll::firstOrCreate([
                    'user_id' => $enroll->user_id,
                    'role_id'=> $enroll->role_id,
                    'year' => $enroll->year,
                    'type' => $type,
                    'level' => $enroll->level,
                    'group' => $enroll->group,
                    'segment' => $newSegment->id,
                    'course' => $coco->id
                ]);
            }
        }
    }
}
