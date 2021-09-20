<?php

namespace App\Repositories;
use Illuminate\Http\Request;

use App\CourseSegment;
use App\AcademicYear;
use App\YearLevel;
use App\Level;
use App\Enroll;
use App\Topic;
use App\Segment;
use App\LastAction;
use App\AcademicYearType;
use Carbon\carbon;
use Auth;


class ChainRepository implements ChainRepositoryInterface
{
    public function getCourseSegmentByChain(Request $request){
        $year = AcademicYear::Get_current();
        if ($request->filled('year'))
            $year = AcademicYear::find($request->year);
        if (!isset($year)){
            throw new \Exception('There is no active year');
        }
        $enrolls =  Enroll::where('year', $year->id);
        if ($request->filled('type'))
            {            
            $enrolls=$enrolls->where('type', $request->type);
            $segment_id = Segment::Get_current($request->type);
            if(!isset($segment_id))
                throw new \Exception('There is no active segment in this type '.$request->type);
            $segment_id = Segment::Get_current($request->type)->id;
            if ($request->filled('segment'))
                $segment_id = $request->segment;
            $enrolls=$enrolls->where('segment', $segment_id);
            }        
        if ($request->filled('level'))
            $enrolls=$enrolls->where('level', $request->level);
        if ($request->filled('class'))
            $enrolls=$enrolls->where('class', $request->class);
        if ($request->filled('courses')){
            foreach($request->courses as $course_id){
                LastAction::lastActionInCourse($course_id);
            }
            $enrolls=$enrolls->whereIn('course', $request->courses);
        }

    return $enrolls;

    
    }

    public function getCourseSegmentByManyChain(Request $request){

        $crrent_year = AcademicYear::Get_current();
        $years = isset($crrent_year) ? [$crrent_year->id] : [];
        if($request->filled('years'))
            $years = $request->years;

        if(count($years) == 0){
            throw new \Exception('There is no active year');
        }

        $enrolls =  Enroll::whereIn('year', $years);

        if(count($enrolls->pluck('year'))==0)
            throw new \Exception('Please enroll some users in any course of this year');

        if($request->filled('types'))
            $enrolls->whereIn('type', $request->types);
        
        $types = $enrolls->pluck('type')->unique()->values();
        $active_segments = Segment::whereIn('academic_type_id',$types)->where('current',1)->pluck('id');
        if($request->filled('segments')){
              $active_segments = $request->segments ;
        }
        if(count($active_segments) == 0)
            throw new \Exception('There is no active segment in those types'.$request->type);

        $enrolls->whereIn('segment', $active_segments);

        if($request->filled('levels'))
            $enrolls->whereIn('level', $request->levels);

        if($request->filled('classes'))
            $enrolls->whereIn('class', $request->classes);

        if($request->filled('courses'))
            $enrolls->whereIn('course', $request->courses);

        if($request->has('user_id'))
        {
            if(!$request->user()->can('site/show-all-courses'))
                $enrolls->where('user_id',Auth::id());

            $enrolls->where('user_id',$request->user_id);
        }

        return $enrolls;    
    }

    public function getAllByChainRelation($request){

        $year = AcademicYear::Get_current();

        if ($request->filled('year'))
            $year = AcademicYear::find($request->year);

        if (!isset($year))
            throw new \Exception('There is no active year');

        $YearTypes = $year->where('id', $year->id)->with(['YearType' => function ($query) use ($request) {

            if ($request->filled('type'))
                $query->where('academic_type_id', $request->type);

        }, 'YearType.yearLevel' => function ($query) use ($request) {

            if ($request->filled('level'))
                $query->where('level_id', $request->level);

        }, 'YearType.yearLevel.classLevels' => function ($query) use ($request) {

            if ($request->filled('class'))
                $query->where('class_id', $request->class);

        }, 'YearType.yearLevel.classLevels.segmentClass' => function ($query) use ($request) {

            if ($request->filled('type')) {

                $segment_id = Segment::Get_current($request->type);

                if(isset($segment_id))
                    $segment_id = Segment::Get_current($request->type)->id;

                if ($request->filled('segment'))
                    $segment_id = $request->segment;

                $query->where('segment_id', $segment_id);
            }

        }, 'YearType.yearLevel.classLevels.segmentClass.courseSegment' => function ($query)  use ($request) {

            if ($request->filled('courses'))
                $query->whereIn('course_id', $request->courses);

        }, 'YearType.yearLevel.classLevels.segmentClass.courseSegment.courses.attachment'])->get()->pluck('YearType.*.yearLevel.*.classLevels.*.segmentClass.*.courseSegment.*')[0];

        return $YearTypes;

    }

    public function getEnrollsByChain(Request $request){
        $year = AcademicYear::Get_current();
        if ($request->filled('year'))
            $year = AcademicYear::find($request->year);
        if (!isset($year)){
            throw new \Exception('There is no active year');
        }
        $enrolls =  Enroll::where('year', $year->id);
        if ($request->filled('type'))
            {            
            $enrolls=$enrolls->where('type', $request->type);
            $segment_id = Segment::Get_current_by_one_type($request->type);
            if(!isset($segment_id))
                throw new \Exception('There is no active segment in this type '.$request->type);
            // $segment_id = Segment::Get_current($request->type)->id;
            if ($request->filled('segment'))
                $segment_id = $request->segment;
            $enrolls=$enrolls->where('segment', $segment_id);
            }
        if ($request->filled('level'))
            $enrolls=$enrolls->where('level', $request->level);
        if ($request->filled('class'))
            $enrolls=$enrolls->where('group', $request->class);
        if ($request->filled('courses')){
            foreach($request->courses as $course_id){
                LastAction::lastActionInCourse($course_id);
            }
            $enrolls=$enrolls->whereIn('course', $request->courses);
        }

    return $enrolls;
    }


    public function getEnrollsByManyChain(Request $request){

        $crrent_year = AcademicYear::Get_current();
        $years = isset($crrent_year) ? [$crrent_year->id] : [];
        if($request->filled('years'))
            $years = $request->years;


        if(count($years) == 0){
            throw new \Exception('There is no active year');
        }

        $enrolls =  Enroll::whereIn('year', $years);
        //dd($enrolls->get());

        if(count($enrolls->pluck('year'))==0)
            throw new \Exception('Please enroll some users in any course of this year');

        if($request->filled('types'))
            $enrolls->whereIn('type', $request->types);
        
        $types = $enrolls->pluck('type')->unique()->values();

        $active_segments = Segment::Get_current_by_many_types($types);
        

        if($request->filled('period')){
            
            if($request->period == 'no_segment')
                $active_segments = Segment::whereIn('academic_type_id', $types)->pluck('id'); 

            if($request->period == 'past')
                $active_segments = Segment::whereIn('academic_type_id', $types)->where("end_date", '<' ,Carbon::now())->where("start_date", '<' ,Carbon::now())->pluck('id');
           
            if($request->period == 'future')
                $active_segments = Segment::whereIn('academic_type_id', $types)->where("end_date", '>' ,Carbon::now())->where("start_date", '>' ,Carbon::now())->pluck('id');
        }

        if($request->filled('segments')){
              $active_segments = $request->segments ;
        }
        if(count($active_segments) == 0)
            throw new \Exception('There is no active segment in those types'.$request->type);

        $enrolls->whereIn('segment', $active_segments);

        if($request->filled('levels'))
            $enrolls->whereIn('level', $request->levels);

        if($request->filled('classes'))
            $enrolls->whereIn('group', $request->classes);

        if($request->filled('courses'))
            $enrolls->whereIn('course', $request->courses);

        if($request->has('user_id'))
        {
            if(!$request->user()->can('site/show-all-courses'))
                $enrolls->where('user_id',Auth::id());

            $enrolls->where('user_id',$request->user_id);
        }

        return $enrolls;    
    }



}