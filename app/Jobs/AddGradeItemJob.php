<?php

namespace App\Jobs;

use App\GradeItems;
use App\GradeCategory;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class AddGradeItemJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $grade_cat=array();
    public $items=array();
    public $itemcoll;
    public $coursesegment;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($items,$coursesegment)
    {
        $this->coursesegment=$coursesegment;
        $this->items=$items;
        $this->itemcoll=collect($items);
        $this->grade_cat=$this->itemcoll->pluck('grade_category')->first();
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        foreach($this->coursesegment as $corseseg)
        {
            $cat_id=GradeCategory::where('name',$this->grade_cat)->where('course_segment_id',$corseseg)->get(['id','id_number'])->first();
            if(!is_null($cat_id))
            {
                foreach($this->items as $item)
                {
                    $x = GradeItems::firstOrCreate([
                        'grade_category' => $cat_id->id,
                        'id_number' => $cat_id->id_number,
                        'grademin' => $item['grademin'],
                        'grademax' => $item['grademax'],
                        'grade_pass' => $item['grade_pass'],
                        'name' => (isset($item['name'])) ? $item['name'] : 'Grade Item',
                        'override' => (isset($item['override'])) ? $item['override'] : 0,
                        'item_Entity' => (isset($item['item_Entity'])) ? $item['item_Entity'] : null,
                        'item_type' => (isset($item['item_type'])) ? $item['item_type'] : null,
                        'aggregationcoef2' => (isset($item['aggregationcoef2'])) ? $item['aggregationcoef2'] : null,
                        'aggregationcoef' => (isset($item['aggregationcoef'])) ? $item['aggregationcoef'] : null,
                        'plusfactor' => (isset($item['plusfactor'])) ? $item['plusfactor'] : 1,
                        'multifactor' => (isset($item['multifactor'])) ? $item['multifactor'] : 1,
                        'calculation' => (isset($item['calculation'])) ? $item['calculation'] : null,
                        'hidden' => (isset($item['hidden'])) ? $item['hidden'] : 0,
                        'item_no' => (isset($item['item_no'])) ? $item['item_no']: null,
                        'scale_id' => (isset($item['scale_id'])) ? $item['scale_id']: null,
                    ]);
                }
            }
            
        }
    }
}
