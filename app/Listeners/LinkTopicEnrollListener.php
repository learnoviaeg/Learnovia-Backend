<?php

namespace App\Listeners;

use App\Events\TopicCreatedEvent;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Repositories\ChainRepositoryInterface;
use Illuminate\Http\Request;

use App\Enroll;
use App\Topic;
use App\EnrollTopic;
use App\CourseSegment;
use App\AcademicYear;
use App\YearLevel;
use App\Level;
use App\Segment;
use App\LastAction;
use App\AcademicYearType;
use Carbon\carbon;



class LinkTopicEnrollListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(ChainRepositoryInterface $chain)
    {
        $this->chain = $chain;
    }

    /**
     * Handle the event.
     *
     * @param  TopicCreatedEvent  $event
     * @return void
     */
    public function handle(TopicCreatedEvent $event)
    {
    //    $request = new Request();

       $topic = new Topic();
       $topic =  $event->topic;

       $this->chain->getEnrollsTopic($topic);

    //  print_r($enrolls);
    
    















     

    // //    print_r($request);
    // //    die('die');


    //   $crrent_year = AcademicYear::Get_current();
    //   //  $years = isset($crrent_year) ? [$crrent_year->id] : [];
   
    //     //if($request->filled('years'))
    //         //$years = $request->years;

    //     // if(count($years) == 0){
    //     //     throw new \Exception('There is no active year');
    //     // }

    //     $enrolls =  Enroll::all()->where('year', $crrent_year->id);

        
        
    //     if(count($enrolls->pluck('year'))==0)
    //         throw new \Exception('Please enroll some users in any course of this year');

    //    // if($request->filled('types'))
    //    $enrolls->whereIn('type', $request->types);

    //    print_r($enrolls);
    //    die('die');

      
        
    //     $types = $enrolls->pluck('type')->unique()->values();

    //     $active_segments = Segment::Get_current_by_many_types($types);
        

    //     if($request->filled('period')){
            
    //         if($request->period == 'no_segment')
    //             $active_segments = Segment::whereIn('academic_type_id', $types)->pluck('id'); 

    //         if($request->period == 'past')
    //             $active_segments = Segment::whereIn('academic_type_id', $types)->where("end_date", '<' ,Carbon::now())->where("start_date", '<' ,Carbon::now())->pluck('id');
           
    //         if($request->period == 'future')
    //             $active_segments = Segment::whereIn('academic_type_id', $types)->where("end_date", '>' ,Carbon::now())->where("start_date", '>' ,Carbon::now())->pluck('id');
    //     }

    //     if($request->filled('segments')){
    //           $active_segments = $request->segments ;
    //     }
    //     if(count($active_segments) == 0)
    //         throw new \Exception('There is no active segment in those types'.$request->type);

    //     $enrolls->whereIn('segment', $active_segments);

    //     if($request->filled('levels'))
    //         $enrolls->whereIn('level', $request->levels);

    //     if($request->filled('classes'))
    //         $enrolls->whereIn('group', $request->classes);

    //     if($request->filled('courses'))
    //         $enrolls->whereIn('course', $request->courses);

    //     if($request->has('user_id'))
    //     {
    //         if(!$request->user()->can('site/show-all-courses'))
    //             $enrolls->where('user_id',Auth::id());

    //         $enrolls->where('user_id',$request->user_id);
    //     }



    //  $this->chain->getEnrollsByManyChain($request);
   
       //print_r($request);


    //    $chain_request = new Request ([
    //     'year' => $event->topic->filter->years,
    //     'type' => isset($event->topic->filter->types) ? $event->topic->filter->types[0] : null,
    //     'level' => isset($chain['level']) ? $chain['level'] : null,
    //     'class' => isset($chain['class']) ? $chain['class'] : null,
    //     'segment' => isset($chain['segment']) ? $chain['segment'] : null,
    //     'courses' => isset($chain['course']) ? [$chain['course']] : null,
    // ]);
       

     // echo $this->chain->getEnrollsByManyChain($request);
















    }
}
