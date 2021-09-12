<?php

namespace App\Listeners;

use App\Events\UserEnrolledEvent;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\User;
use App\Enroll;
use App\UserGrader;
use App\GradeCategory;
use App\GradeItems;

class AddUserGradersListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  UserEnrolledEvent  $event
     * @return void
     */
    public function handle(UserEnrolledEvent $event)
    {
        if($event->enroll->role_id == 3){
            foreach(GradeCategory::where('course_id' , $event->enroll->course)->cursor() as $grade_cat){
                UserGrader::firstOrCreate(
                    ['item_id' =>  $grade_cat->id , 'item_type' => 'category', 'user_id' => $event->enroll->user_id],
                    ['grade' => null]
                );
                foreach(GradeItems::where('grade_category_id' , $grade_cat->id)->cursor() as $grade_item){
                    UserGrader::firstOrCreate(
                        ['item_id' =>  $grade_item->id , 'item_type' => 'item', 'user_id' => $event->enroll->user_id],
                        ['grade' => null]
                    );
                }
            }
        }
    }
}

