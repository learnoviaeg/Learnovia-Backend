<?php

namespace App\Repositories;
use Illuminate\Http\Request;

use App\CourseSegment;
use App\AcademicYear;
use App\YearLevel;
use App\Level;
use App\Segment;

class ChainRepository implements ChainRepositoryInterface
{
    public function getCourseSegmentByChain(Request $request){
        $year = AcademicYear::Get_current();
        if ($request->filled('year'))
            $year = AcademicYear::find($request->year);
        if(isset($year)){
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
                if ($request->filled('typical'))
                    $query->where('typical', $request->typical);
            }])->get()->pluck('YearType')[0];

            return $YearTypes->pluck('yearLevel.*')->collapse()->pluck('classLevels.*')
                                                   ->collapse()->pluck('segmentClass.*')
                                                   ->collapse()->pluck('courseSegment.*.id')->collapse();
        }
        return [];
    }

}