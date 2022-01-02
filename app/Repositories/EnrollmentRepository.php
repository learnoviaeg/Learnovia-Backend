<?php

namespace App\Repositories;
use Illuminate\Http\Request;
use App\Announcement;
use App\AnnouncementsChain;
use App\userAnnouncement;
use App\Enroll;
use App\User;
use DB;

class EnrollmentRepository implements EnrollmentRepositoryInterface
{
    public function RemoveAllDataRelatedToRemovedChain($one){

        $user_enrolls = Enroll::where('user_id',$one->user_id)->where('id','!=',$one->id)->count();
        $user = User::find($one->user_id);

        if($user_enrolls == 0){
            userAnnouncement::where('user_id',$one->user_id)->delete();
            $notificationIDs = $user->notifications()->pluck('notifications.id');
            $user->notifications()->detach($notificationIDs);
        }

        if($user_enrolls != 0){

            $enrolls =  Enroll::where('user_id',$one->user_id)->where('id','!=',$one->id)->get();

            $user_old_announcements = Announcement::whereNotIn('year_id',$enrolls->pluck('year')->filter())
                                                    ->orWhereNotIn('type_id',$enrolls->pluck('type')->filter())
                                                    ->orWhereNotIn('level_id',$enrolls->pluck('level')->filter())
                                                    ->orWhereNotIn('class_id',$enrolls->pluck('class')->filter())
                                                    ->orWhereNotIn('segment_id',$enrolls->pluck('segment')->filter())
                                                    ->orWhereNotIn('course_id',$enrolls->pluck('course')->filter())
                                                    ->pluck('id');

            $user_old_announcements1 = AnnouncementsChain::whereNotIn('year',$enrolls->pluck('year')->filter())
                                                    ->orWhereNotIn('type',$enrolls->pluck('type')->filter())
                                                    ->orWhereNotIn('level',$enrolls->pluck('level')->filter())
                                                    ->orWhereNotIn('class',$enrolls->pluck('class')->filter())
                                                    ->orWhereNotIn('segment',$enrolls->pluck('segment')->filter())
                                                    ->orWhereNotIn('course',$enrolls->pluck('course')->filter())
                                                    ->pluck('announcement_id');

            $final_old_announcements = array_merge($user_old_announcements->toArray(),$user_old_announcements1->toArray());
            
            userAnnouncement::where('user_id',$one->user_id)->whereIn('announcement_id',$final_old_announcements)->delete();

            $announcemnets = $user->notifications()->where('type','announcement')->whereIn('item_id',$final_old_announcements)->pluck('notifications.id');
            $notifications = $user->notifications()->where('type','notification')->where('course_id',$one->course)->pluck('notifications.id');
            $notificationIds = $announcemnets->merge($notifications);
            $user->notifications()->detach($notificationIds);

        }
        
        return 1;
    }
}