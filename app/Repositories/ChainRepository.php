<?php

namespace App\Repositories;
use Illuminate\Http\Request;

use App\CourseSegment;
use App\AcademicYear;
use App\YearLevel;
use App\Level;
use App\Enroll;
use App\Segment;
use App\LastAction;

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

        if($request->filled('types'))
            $enrolls->whereIn('type', $request->types);
        
        $active_segments = collect();
        $request->filled('segments') ? $active_segments = $request->segments : [] ;

        //request didn't has segments
        if(count($active_segments) == 0){

            $types = $enrolls->pluck('type')->unique()->values();
            $active_segments = Segment::whereIn('academic_type_id',$types)->where('current',1)->pluck('id');
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

        return $enrolls;    
    }

}