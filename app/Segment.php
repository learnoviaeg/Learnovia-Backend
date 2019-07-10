<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Http\Controllers\HelperController;

class Segment extends Model
{
    protected $fillable = ['name'];

    public function Segment_class(){
        return $this->belongsToMany('App\ClassLevel', 'segment_classes','class_level_id','segment_id');
    }
    public static function Get_Courses_with_specific_segment( $id)
    {
        $cat = SegmentClass::where('segment_id',$id)->get(['id'])->toArray();
        $Course_Segment= CourseSegment::whereIN('segment_class_id',$cat)->get(['course_id'])->toArray();
        $Courses= Course::whereIN('id',$Course_Segment)->get();
        return HelperController::api_response_format(200,$Courses);
    }
}
