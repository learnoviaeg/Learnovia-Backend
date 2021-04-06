<?php

namespace App\Observers;

use App\Material;
use App\Repositories\RepportsRepositoryInterface;

class MaterialsObserver
{
    protected $report;

    public function __construct(RepportsRepositoryInterface $report)
    {
        $this->report = $report;
    }
    /**
     * Handle the material "created" event.
     *
     * @param  \App\Material  $material
     * @return void
     */
    public function created(Material $material)
    {
        //
    }

    /**
     * Handle the material "updated" event.
     *
     * @param  \App\Material  $material
     * @return void
     */
    public function updated(Material $material)
    {
        if($material->isDirty('seen_number')){
            $this->report->calculate_course_progress($material->course_id);
        }
    }

    /**
     * Handle the material "deleted" event.
     *
     * @param  \App\Material  $material
     * @return void
     */
    public function deleted(Material $material)
    {
        $this->report->calculate_course_progress($material->course_id);
    }

    /**
     * Handle the material "restored" event.
     *
     * @param  \App\Material  $material
     * @return void
     */
    public function restored(Material $material)
    {
        //
    }

    /**
     * Handle the material "force deleted" event.
     *
     * @param  \App\Material  $material
     * @return void
     */
    public function forceDeleted(Material $material)
    {
        //
    }
}
