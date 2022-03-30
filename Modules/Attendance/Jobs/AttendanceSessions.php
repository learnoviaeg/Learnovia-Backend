<?php

namespace Modules\Attendance\Jobs;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Modules\Attendance\Entities\Attendance;
use Modules\Attendance\Entities\AttendanceSession;
use Modules\Attendance\Entities\AttendanceStatus;

class AttendanceSessions implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $request;
    public $user_id;
    public $course_segments;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($request, $user_id, $course_segment)
    {
        $this->request = $request;
        $this->user_id = $user_id;
        $this->course_segments = $course_segment;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $alldays = [];
        foreach ($this->request['days'] as $day) {
            $startDate = Carbon::parse(Carbon::now())->next(Attendance::GetCarbonDay($day));
            $endDate = Carbon::parse($this->request['end_date']);

            for ($date = $startDate; $date->lte($endDate); $date->addWeek()) {
                $alldays[] = $date->format('Y-m-d');
            }
        }

        switch ($this->request['attendance_type']) {
            case 1 :
                foreach ($this->course_segments as $courseSegment) {
                    foreach ($alldays as $day) {
                            $AttendanceSessions[] = AttendanceSession::create(['attendance_id' => $this->request['attendance_id'],
                            'taker_id' => $this->user_id,
                            'date' => $day,
                            'course_segment_id' => $courseSegment
                        ]);
                    }
                }
                break;
            case 2:
                foreach ($alldays as $day) {
                    for ($i = 1; $i <= $this->request['times']; $i++) {
                        $AttendanceSessions[] = AttendanceSession::create(['attendance_id' => $this->request['attendance_id'],
                            'taker_id' => $this->user_id,
                            'date' => $day,
                            'course_segment_id' => null
                        ]);
                    }
                }
                break;
        }
    }
}