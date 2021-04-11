<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Announcement;
use Modules\Assigments\Entities\assignment;
use Modules\Assigments\Entities\UserAssigment;
use App\Component;
use App\Enroll;
use App\Event;
use App\Lesson;
use Auth;
use DB;
use App\User;
use Carbon\Carbon;
class CalendarController extends Controller
{
    /**
     * get calender
     *
     * @param  [int] month
     * @return if month [objects] all assignments/quizes/announcements... in this month
     * @return [objects] all assignments/quizes/announcements... in current month
     */
    public function calendar(Request $request)
    {
        $request->validate([
            'month' => 'nullable|integer'
        ]);
        $auth = Auth::user()->id;
        $date = \Carbon\Carbon::now()->month;
        if ($request->filled('month'))
            $date = $request->month;
        $decodedannounce = CalendarController::announcement_calendar($auth, $date);
        $components = CalendarController::Component_calendar($auth, $date);
        $array = ['Announcements' => $decodedannounce, 'Lessons' => $components];
        return HelperController::api_response_format(201, $array);
    }

    /**
     * announce in calendar
     *
     * @param  [int] auth
     * @param  [date] date
     * @return [objects] all announcements with his dates belongs to this user
     */
    public function announcement_calendar($auth, $date)
    {
        //Announcements in Calendar
        $allannounce = Announcement::whereMonth('start_date', '=', $date)
            ->orderBy('start_date')
            ->get();
        $announcefinal = array();
        $counter = 0;
        foreach ($allannounce as $announ) {
            $announcefinal[$counter]['id'] = $announ->id;
            $announcefinal[$counter]['type'] = 'announcement';
            $announcefinal[$counter]['message'] = 'A new announcement will be published';
            $announcefinal[$counter]['publish_date'] = $announ->publish_date;
            $counter++;
        }

        $dataencode = array();
        foreach ($announcefinal as $try)
            $dataencode[] = json_encode($try);

        $anounce = array();
        $decodedannounce = array();
        $id = array();
        foreach ($dataencode as $encode)
            $anounce[] = DB::table('notifications')->where('notifiable_id', $auth)->where('data', $encode)->pluck('data')->first();

        foreach ($anounce as $decode) {
            if (isset($decode))
                $decodedannounce[] = json_decode($decode, true);
        }

        $withdatesannounce = collect([]);
        foreach ($decodedannounce as $an) {
            $withdatesannounce->push(Announcement::where('id', $an['id'])
                ->whereMonth('start_date', '=', $date)
                ->orderBy('start_date')
                ->first());
        }
        return  $withdatesannounce;
    }

    /**
     * components in calendar
     *
     * @param  [int] auth
     * @param  [date] date
     * @return [objects] all components belongs to this user
     */
    public function Component_calendar($auth, $date)
    {
        $CourseSeg = Enroll::where('user_id', $auth)->pluck('course_segment');

        $Lessons = array();
        foreach ($CourseSeg as $cour) {
            $checkLesson = Lesson::where('course_segment_id', $cour)->get();

            if ($checkLesson->isEmpty())
                continue;

            $Lessons[] = $checkLesson;
        }
        $components  = Component::where('active', 1)->where('type', 3)->get();
        $result = [];
        foreach ($components as $component) {
            $result[$component->name] = [];
        }
        $user = User::whereId(Auth::user()->id)->with(['enroll.courseSegment'])->first();
        foreach ($user->enroll as $enroll) {
            if ($enroll->courseSegment != null) {
                foreach ($enroll->courseSegment->lessons as $lesson) {
                    foreach ($components as $component) {
                        $temp = $lesson->module($component->module, $component->model);
                        $temp->whereMonth('start_date', '=', $date)
                        ->orderBy('start_date')
                        ->withPivot('start_date');
                        if ($user->can('site/course/student')) {
                            $temp->where('visible', '=', 1)
                                ->where('publish_date', '<=', Carbon::now());
                        }
                        if(count($temp->get()) == 0)
                            continue;
                        $tempBulk = $temp->get();
                        foreach($tempBulk as $item){
                            $result[$component->name][] = $item;
                        }
                    }
                }
            }
        }
        return $result;
    }

    public function weeklyCalender(Request $request)
    {
        $days = collect();
        $events = collect();
        Carbon::setWeekStartsAt(Carbon::SUNDAY);
        Carbon::setWeekEndsAt(Carbon::SATURDAY);
        $day = Carbon::now()->startOfWeek();
        $last = Carbon::now()->endOfWeek();
        while ($day->format('Y-m-d') != $last->format('Y-m-d')) {
            $days[] = [$day->format('Y-m-d'), $day->format('l')];
            $events[] = Event::where('user_id' , Auth::user()->id)
            ->whereDate('from' ,'<=' , $day->format('Y-m-d'))
            ->WhereDate('to', '>=' , $day->format('Y-m-d'))
            ->get(['name' , 'description' , 'from' , 'to']);
            $day = $day->copy()->addDay();
        }
        $days[] = [$last->format('Y-m-d'), $last->format('l')];
        $events[] = Event::where('user_id' , Auth::user()->id)
        ->whereDate('from' ,'<=' , $last->format('Y-m-d'))
        ->WhereDate('to', '>=' , $last->format('Y-m-d'))
        ->get(['name' , 'description' , 'from' , 'to']);
        return HelperController::api_response_format(201, ['days' => $days , 'events' => $events]);
    }
}
