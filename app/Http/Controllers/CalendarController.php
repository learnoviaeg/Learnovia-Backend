<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Announcement;
use Modules\Assigments\Entities\assignment;
use Modules\Assigments\Entities\UserAssigment;
use App\Component;
use App\Enroll;
use App\Lesson;
use Auth;
use DB;

class CalendarController extends Controller
{
    public function calendar (Request $request)
    {

        //Validtaion
        $request->validate([
            'month'=>'nullable|integer'
        ]);

        //get auth user id
        $auth=Auth::user()->id;

        if($request->month == null)
        {
            //get the current month
            $date = \Carbon\Carbon::now()->month;

            //announcement calendar function
            $decodedannounce=CalendarController::announcement_calendar($auth,$date);

            //Components calendar function
            $components=CalendarController::Component_calendar($auth,$date);


        }
        else {

              //announcement calendar function
              $decodedannounce=CalendarController::announcement_calendar($auth,$request->month);

              //Components calendar function
                $components=CalendarController::Component_calendar($auth,$request->month);
        }

        //returning data
        if($components != null && $decodedannounce != null)
        {
            return HelperController::api_response_format(201, ['Announcements'=>$decodedannounce, 'Lessons' => $components]);
        }
        else if ($decodedannounce == null && $components != null )
        {
            return HelperController::api_response_format(201, ['Lessons' => $components]);
        }
        else if ($components == null && $decodedannounce != null)
        {
            return HelperController::api_response_format(201, ['Announcements'=>$decodedannounce]);
        }
        else if ($components == null && $decodedannounce == null)
        {
            return HelperController::api_response_format(201, null,'There is no data for you');
        }

    }

    public function announcement_calendar($auth,$date)
    {

            //Announcements in Calendar
            $allannounce=Announcement::whereMonth('start_date','=', $date)
            ->orderBy('start_date')
            ->get();
            $announcefinal=array();
            $counter=0;
            foreach($allannounce as $announ)
            {
                $announcefinal[$counter]['title'] = $announ->title;
                $announcefinal[$counter]['type'] = 'announcement';
                $announcefinal[$counter]['description'] = $announ->description;
                if($announ->attached_file!=null)
                {
                    $announcefinal[$counter]['attached_file'] = $announ->attached_file;

                }
                $counter++;
            }
            $dataencode=array();
            foreach($announcefinal as $try)
            {
                $dataencode[] = json_encode($try);
            }
            $anounce=array();
            $decodedannounce=array();
            $id=array();
            foreach($dataencode as $encode )
            {
                $anounce[] = DB::table('notifications')->where('notifiable_id', $auth)->where('data',$encode)->pluck('data')->first();
            }

            foreach($anounce as $decode)
            {
                if(isset($decode))
                {
                $decodedannounce[]=json_decode($decode, true);
                }
            }
            $withdatesannounce=collect([]);
            foreach ($decodedannounce as $an)
            {
                $withdatesannounce->push(Announcement::where('title',$an['title'])
                ->where('description',$an['description'])
                ->whereMonth('start_date','=', $date)
                ->orderBy('start_date')
                ->first());
            }
         return  $withdatesannounce;
    }

    public function Component_calendar($auth,$date)
    {

        $CourseSeg=Enroll::where('user_id',$auth)->whereMonth('start_date','=', $date)
        ->orderBy('start_date')
        ->pluck('course_segment');

        $Lessons=array();
        foreach ($CourseSeg as $cour) {
            $checkLesson=Lesson::where('course_segment_id',$cour)->get();
            
            if($checkLesson->isEmpty())
            {
                continue;
            }
            $Lessons[]=$checkLesson;
        }
        
        $comp=Component::where('type',1)->get();
        foreach($Lessons as $less)
        {
            foreach($less as $les)
            {

                foreach($comp as $com)
                {
                    if($com->type == 3)
                        continue;
                    if($com->name=='Quiz')
                    {
                        $les[$com->name]= $les->module($com->module,$com->model)
                        ->whereMonth('start_date','=', $date)
                        ->orderBy('start_date')
                        ->withPivot('start_date')
                        ->get();
                    }
                    else
                    {
                        $les[$com->name]= $les->module($com->module,$com->model)
                        ->whereMonth('start_date','=', $date)
                        ->orderBy('start_date')
                        ->get();
                    }
                }
            }
        }
        return $Lessons;
    }
}
