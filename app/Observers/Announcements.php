<?php

namespace App\Observers;

use App\Announcement;
use App\Timeline;
use Carbon\Carbon;

class Announcements
{
    /**
     * Handle the announcement "created" event.
     *
     * @param  \App\Announcement  $announcement
     * @return void
     */
    public function created(Announcement $announcement)
    {
        if(isset($announcement->start_date) && isset($announcement->due_date)){
            Timeline::firstOrCreate([
                'item_id' => $announcement->id,
                'name' => $announcement->title,
                'start_date' => $announcement->start_date,
                'due_date' => $announcement->due_date,
                'publish_date' => isset($announcement->publish_date)? $announcement->publish_date : Carbon::now(),
                'type' => 'announcement',  
                'visible' => 1
            ]);
        }
    }

    /**
     * Handle the announcement "updated" event.
     *
     * @param  \App\Announcement  $announcement
     * @return void
     */
    public function updated(Announcement $announcement)
    {
        $forLogs=Timeline::where('item_id',$announcement->id)->where('type' , 'announcement')->first();
        if(isset($forLogs)){
            $forLogs->update([
                'item_id' => $announcement->id,
                'name' => $announcement->title,
                'start_date' => $announcement->start_date,
                'due_date' => $announcement->due_date,
                'publish_date' => isset($announcement->publish_date)? $announcement->publish_date : Carbon::now(),
                'type' => 'announcement',
            ]);
        }
    }

    /**
     * Handle the announcement "deleted" event.
     *
     * @param  \App\Announcement  $announcement
     * @return void
     */
    public function deleted(Announcement $announcement)
    {
        Timeline::where('item_id',$announcement->id)->where('type','announcement')->delete();
    }

    /**
     * Handle the announcement "restored" event.
     *
     * @param  \App\Announcement  $announcement
     * @return void
     */
    public function restored(Announcement $announcement)
    {
        //
    }

    /**
     * Handle the announcement "force deleted" event.
     *
     * @param  \App\Announcement  $announcement
     * @return void
     */
    public function forceDeleted(Announcement $announcement)
    {
        //
    }
}
