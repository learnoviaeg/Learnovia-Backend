<?php

namespace Modules\Attendance\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Modules\Attendance\Entities\Attendance;
use Modules\Attendance\Entities\AttendanceSession;

class AttendanceSessionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
     public $request, $attendance_id, $course_segments, $periods,$user_id;

    public function __construct($request, $attendance_id, $course_segments, $periods,$user_id)
    {
        $this->request=$request;
        $this->course_segments=$course_segments;
        $this->attendance_id=$attendance_id;
        $this->periods=$periods;
        $this->user_id=$user_id;
    }
    public function handle()
    {
        foreach ($this->course_segments as $course_segment) {
            $alldays = Attendance::getAllWorkingDays($this->request['start'], $this->request['end']);
            $FromTodays = Attendance::getAllWorkingDays($this->periods['from'], $this->periods['to']);
            if (Attendance::check_in_array($alldays, $FromTodays)) {
                foreach ($FromTodays as $day) {
                    for ($i = 0; $i < $this->request['sessions']['times']; $i++) {
                        AttendanceSession::create([
                            'attendance_id' => $this->attendance_id,
                            'taker_id' => $this->user_id,
                            'date' => $day,
                            'course_segment_id' => $course_segment,
                            'from' => $this->request['sessions']['time'][$i]['start'],
                            'to' => $this->request['sessions']['time'][$i]['end']
                        ]);
                    }
                }
            }
        }
        }
    }
