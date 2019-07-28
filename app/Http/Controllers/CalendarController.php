<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Announcement;
use Modules\Assigments\Entities\assignment;
use Modules\Assigments\Entities\UserAssigment;
use App\Component;
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

        //check if component exist
        $assignment= Component::where('name','Assigments')->pluck('id')->first();

        if($request->month == null)
        {
            //get the current month
            $date = \Carbon\Carbon::now()->month;

            //announcement calendar function
            $decodedannounce=CalendarController::announcement_calendar($auth,$date);

            //Assignment calendar function
            if($assignment != null)
            {
            $assign=CalendarController::assignment_calendar($auth,$date);
            }

        }
        else {

              //announcement calendar function
              $decodedannounce=CalendarController::announcement_calendar($auth,$request->month);

              //Assignment calendar function
              if($assignment != null)
              {
                $assign=CalendarController::assignment_calendar($auth,$request->month);
              }
        }

        //returning data
        if($assignment != null)
        {

         return HelperController::api_response_format(201, ['Announcements'=>$decodedannounce, 'Assignments' => $assign]);

        }
        return HelperController::api_response_format(201, ['Announcements'=>$decodedannounce]);
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
            foreach($dataencode as $encode )
            {
                $anounce[] = DB::table('notifications')->where('notifiable_id', $auth)->where('type','App\Notifications\Announcment')->where('data',$encode)->pluck('data')->first();
            }
            foreach($anounce as $decode)
            {
                if(isset($decode))
                {
                $decodedannounce[]=json_decode($decode, true);
                }
            }

         return  $decodedannounce;
    }

    public function assignment_calendar($auth,$date)
    {

        //Assignment in Calendar
        $Assignment_id = UserAssigment::where('user_id',$auth)->pluck('assignment_id');

        $assign= assignment::where('id',$Assignment_id)->whereMonth('start_date','=', $date)
        ->orderBy('start_date')
        ->get('name');

        return $assign;
    }
}
