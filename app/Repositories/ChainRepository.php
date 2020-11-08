<?php

namespace App\Repositories;
use Illuminate\Http\Request;

use App\CourseSegment;
use App\AcademicYear;
use App\YearLevel;
use App\Level;
use App\Enroll;
use App\Segment;

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
                throw new \Exception('There is no active segment in this type'.$request->type);
            $segment_id = Segment::Get_current($request->type)->id;
            if ($request->filled('segment'))
                $segment_id = $request->segment;
            $enrolls=$enrolls->where('segment_id', $segment_id);
            }        
        if ($request->filled('level'))
            $enrolls=$enrolls->where('level', $request->level);
        if ($request->filled('class'))
            $enrolls=$enrolls->where('class', $request->class);
        if ($request->filled('courses'))
            $enrolls=$enrolls->whereIn('course', $request->courses);

    return $enrolls;

    
    }

}