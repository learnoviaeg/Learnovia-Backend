<?php

namespace App\Repositories;
use Illuminate\Http\Request;
use App\Announcement;
use App\AnnouncementsChain;
use App\userAnnouncement;
use App\Enroll;
use DB;

class EnrollmentRepository implements EnrollmentRepositoryInterface
{
    public function RemoveAllDataRelatedToRemovedChain($one){

        $user_enrolls = Enroll::where('user_id',$one->user_id)->where('id','!=',$one->id)->count();

        if($user_enrolls == 0){
            userAnnouncement::where('user_id',$one->user_id)->delete();
            DB::table('notifications')->where('notifiable_id', $one->user_id)->delete();    
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

            $notify = DB::table('notifications')->where('notifiable_id', $one->user_id)->get();
            $ids=collect();
            foreach ($notify as $not) {
                $not->data= json_decode($not->data, true);

                if($not->data['type'] == 'announcement' && in_array($not->data['id'],$final_old_announcements)){
                    $ids->push($not->id);
                }

                if($not->data['type'] != 'announcement' && $not->data['course_id'] == $one->course){
                    $ids->push($not->id);
                }
            }

            DB::table('notifications')->whereIn('id', $ids)->delete();
        }

        return 1;
    }
}