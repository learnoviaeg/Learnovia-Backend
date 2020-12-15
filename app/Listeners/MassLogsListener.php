<?php

namespace App\Listeners;

use App\Events\MassLogsEvent;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Log;
use App\User;
use App\Announcement;
use App\AnnouncementsChain;
use App\userAnnouncement;
use DB;
use App\Enroll;

class MassLogsListener
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
     * @param  MassLogsEvent  $event
     * @return void
     */
    public function handle(MassLogsEvent $event)
    {
        foreach($event->log as $one)
        {
            $arr=array();
            // DD($model);
            if($event->action == 'updated'){
                $arr['before']=$one->getOriginal();
                // eval('$arr[\'after\']= new '.$model);
                $arr['after']=get_class($one)::where('id',$one->id)->get();
                Log::create([
                    'user' => User::find(Auth::id())->username,
                    'action' => 'updated',
                    'model' => substr(get_class($one),strripos(get_class($one),'\\')+1),
                    'data' => serialize($arr),
                ]);
            }
            if($event->action == 'deleted'){
                $log = Log::create([
                    'user' => User::find(Auth::id())->username,
                    'action' => 'deleted',
                    'model' => substr(get_class($one),strripos(get_class($one),'\\')+1),
                    'data' => serialize($one),
                ]);

                if($log->model == 'Enroll'){

                    $user_old_announcements = Announcement::where('year_id',$one->year)
                                                            ->where('type_id',$one->type)
                                                            ->where('level_id',$one->level)
                                                            ->where('class_id',$one->class)
                                                            ->where('segment_id',$one->segment)
                                                            ->where('course_id',$one->course)
                                                            ->pluck('id');

                    $user_old_announcements1 = AnnouncementsChain::where('year',$one->year)
                                                            ->where('type',$one->type)
                                                            ->where('level',$one->level)
                                                            ->where('class',$one->class)
                                                            ->where('segment',$one->segment)
                                                            ->where('course',$one->course)
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

                    $user_enrolls = Enroll::where('user_id',$one->user_id)->where('id','!=',$one->id)->count();
                    if($user_enrolls == 0){
                        userAnnouncement::where('user_id',$one->user_id)->delete();
                        DB::table('notifications')->where('notifiable_id', $one->user_id)->delete();    
                    }
                }
            }
                
            //for DB object updated ---> handle error in get original
            Log::create([
                'user' => User::find(Auth::id())->username,
                'action' => 'updated',
                'model' => substr(get_class($one),strripos(get_class($one),'\\')+1),
                'data' => serialize($one),
            ]);
        }
    }
}
