<?php

namespace Modules\Attendance\Jobs;
use Illuminate\Http\Request;
use App\GradeCategory;
use App\Http\Controllers\GradeCategoryController;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Modules\Attendance\Entities\Attendance;
use Nwidart\Modules\Collection;

class AttendanceGradeItems implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $request,$type,$grade_category;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($request,$type,$grade_category)
    {
        $this->request =$request;
        $this->type=$type;
        $this->grade_category=$grade_category;
    }


    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if ($this->type == Attendance::$FIRST_TYPE) {
            foreach ($this->request['levels'] as $level) {
                $request['type'] = [$this->request['type']];
                $request['classes'] = $level['classes'];
                $request['courses'] = $level['courses'];
                $req = new Request([
                    'year' => $this->request['year'],
                    'segments' => [$this->request['segment']],
                    'type' => $request['type'],
                    'levels' => [$level['id']],
                    'classes' => $level['classes'],
                    'courses' => $level['courses']
                ]);
                $course_segments = GradeCategoryController::getCourseSegmentWithArray($req);
                $gradeCategories = GradeCategory::where('name', $level['grade_category_name'])->whereIn('course_segment_id', $course_segments)->get();
                foreach ($gradeCategories as $gradeCategory) {
                    $gradeCategory->GradeItems()->create(['name' => 'Attendance','grademin' => $this->request['grade_items']['min'], 'grademax' => $this->request['grade_items']['min'] , 'weight' => 0]);
                }
            }
        }
        if ($this->type == Attendance::$SECOND_TYPE) {
            foreach ($this->grade_category as $gradeCategory) {
                 $gradeCategory->GradeItems()->create(['name' => 'Attendance', 'grademin' => $this->request['grade_items']['min'], 'grademax' => $this->request['grade_items']['min'] , 'weight' => 0]);
            }
        }
    }
}
