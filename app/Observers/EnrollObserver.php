<?php

namespace App\Observers;

use App\CourseSegment;
use App\Enroll;
use App\Http\Controllers\HelperController;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Log;
use App\User;
use PhpParser\Node\Stmt\Continue_;
use App\Announcement;
use App\AnnouncementsChain;
use App\userAnnouncement;

class EnrollObserver
{
    public function created(Enroll $enroll)
    {
        if ($enroll->courseSegment->courses[0]->mandatory == 1) {
            $user = User::find($enroll->user_id);
            $user->update([
                'class_id' => $enroll->courseSegment->segmentClasses[0]->classLevel[0]->class_id,
                'level' => $enroll->courseSegment->segmentClasses[0]->classLevel[0]->yearLevels[0]->level_id,
                'type' => $enroll->courseSegment->segmentClasses[0]->classLevel[0]->yearLevels[0]->yearType[0]->academic_type_id
            ]);
        }

        $log=Log::create([
            'user' => User::find(Auth::id())->username,
            'action' => 'created',
            'model' => 'Enroll',
            'data' => serialize($enroll),
        ]);
    }

    /**
     * Handle the user "updated" event.
     *
     * @return void
     */
    public function updated(Enroll $req)
    {
        $arr=array();
        $arr['before']=$req->getOriginal();
        $arr['after']=$req;

        Log::create([
            'user' => User::find(Auth::id())->username,
            'action' => 'updated',
            'model' => 'Enroll',
            'data' => serialize($arr),
        ]);
    }

    /**
     * Handle the user "deleted" event.
     *
     * @return void
     */
    public function deleted(Enroll $req)
    {
        $log=Log::create([
            'user' => User::find(Auth::id())->username,
            'action' => 'deleted',
            'model' => 'Enroll',
            'data' => serialize($req),
        ]);

        $user_enrolls = Enroll::where('user_id',$req->user_id)->get();

        if(count($user_enrolls) > 1){

            $user_old_announcements = Announcement::where('year_id',$req->year)
                                                    ->where('type_id',$req->type)
                                                    ->where('level_id',$req->level)
                                                    ->where('class_id',$req->class)
                                                    ->where('segment_id',$req->segment)
                                                    ->where('course_id',$req->course)
                                                    ->pluck('id');

            $user_old_announcements1 = AnnouncementsChain::where('year',$req->year)
                                                    ->where('type',$req->type)
                                                    ->where('level',$req->level)
                                                    ->where('class',$req->class)
                                                    ->where('segment',$req->segment)
                                                    ->where('course',$req->course)
                                                    ->pluck('announcement_id');

            $final_old_announcements = array_merge($user_old_announcements->toArray(),$user_old_announcements1->toArray());

            userAnnouncement::where('user_id',$req->user_id)->whereIn('announcement_id',$final_old_announcements)->delete();

            $notify = DB::table('notifications')->where('notifiable_id', $req->user_id)->get();

            foreach ($notify as $not) {
                $not->data= json_decode($not->data, true);
                if($not->data['type'] == 'announcement' && in_array($not->data['id'],$final_old_announcements->toArray())){
                    $not->delete();
                }

                if($not->data['type'] != 'announcement' && $not->data['course_id'] == $req->course){
                    $not->delete();
                }
            }
        }

        if(count($user_enrolls) <= 1){
            userAnnouncement::where('user_id',$req->user_id)->delete();
            $notify = DB::table('notifications')->where('notifiable_id', $req->user_id)->delete();    
        }

    }

    /**
     * Handle the user "restored" event.
     *
     * @return void
     */
    public function restored(Enroll $req)
    {
        //
    }

    /**
     * Handle the user "force deleted" event.
     *
     * @return void
     */
    public function forceDeleted(Enroll $req)
    {
        //
    }
}
