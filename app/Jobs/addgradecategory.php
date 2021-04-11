<?php

namespace App\Jobs;

use App\CourseSegment;
use App\GradeCategory;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class addgradecategory implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $course_segments_id = array();
    public $grade_category = array();

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($course_segments_id, $grade_category)
    {
        $this->course_segments_id = $course_segments_id;
        $this->grade_category = $grade_category;
    }

    public function handle()
    {
        foreach ($this->course_segments_id as $course_segment) {
            $course = CourseSegment::find($course_segment);
            foreach ($this->grade_category as $grade_cat) {
                $parent = null;
                if(isset($grade_cat['parent']))
                    $parent = GradeCategory::where('course_segment_id', $course->id)->where('name', $grade_cat['parent'])->first();
                GradeCategory::create([
                    'name' => $grade_cat['name'],
                    'course_segment_id' => $course_segment,
                    'grademax' => ($grade_cat['type'] == 1) ? $grade_cat['grademax'] : null,
                    'grademin' => ($grade_cat['type'] == 1) ? $grade_cat['grademin'] : null,
                    'type' => $grade_cat['type'],
                    'parent' => ($parent != null) ?  $parent->id: null,
                    'locked' => (isset($grade_cat['locked'])) ? $grade_cat['locked'] : null,
                    'aggregation' => (isset($grade_cat['aggregation'])) ? $grade_cat['aggregation'] : null,
                    'aggregatedOnlyGraded' => (isset($grade_cat['aggregatedOnlyGraded'])) ? $grade_cat['aggregatedOnlyGraded'] : 0,
                    'exclude_flag' => (isset($grade_cat['exclude_flag'])) ? $grade_cat['exclude_flag'] : null,
                    'hidden' => (isset($grade_cat['hidden'])) ? $grade_cat['hidden'] : 0,
                    'weight' => (isset($grade_cat['exclude_flag']) && $grade_cat['exclude_flag'] == 1) ? $grade_cat['weight'] : 0,
                    'id_number' => (isset($course->segmentClasses[0]->classLevel[0]->yearLevels[0]->id)) ? $course->segmentClasses[0]->classLevel[0]->yearLevels[0]->id : null,
                ]);
            }
        }
    }
}
