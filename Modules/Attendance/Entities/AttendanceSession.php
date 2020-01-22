<?php

namespace Modules\Attendance\Entities;

use App\Enroll;
use App\Http\Controllers\GradeCategoryController;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class AttendanceSession extends Model
{
    protected $fillable = ['attendance_id','taker_id','date','last_time_taken','from','to','duration','course_segment_id'];
    protected $appends = ['attedance_type' , 'no_of_students'];

    public function Attendence()
    {
        return $this->belongsTo('Modules\Attendance\Entities\Attendance', 'attendance_id' , 'id' );
    }
    public function Course_Segment()
    {
        return $this->belongsTo('App\CourseSegment', 'course_segment_id' , 'id' );
    }

    public function logs()
    {
        return $this->hasMany('Modules\Attendance\Entities\AttendanceLog','session_id' ,'id');
    }

    public function getAttedanceTypeAttribute(){
        return $this->Attendence->type;
    }

    public function getNoOfStudentsAttribute(){
        $count = 0 ;
        if($this->course_segment_id != null){
            $count = Enroll::where('course_segment' , $this->course_segment_id)->count();
        }else{
            $request = new Request([
                'year' => $this->Attendence->year_id,
                'segments' => [$this->Attendence->segment_id],
                'type' => [$this->Attendence->type_id],
                'levels' => $this->Attendence->allowed_levels,
                'classes' => $this->Attendence->allowed_classes,
                'courses' => $this->Attendence->allowed_courses
            ]);
            $courseSegments = GradeCategoryController::getCourseSegmentWithArray($request);
            $count = Enroll::wherein('course_segment' , $courseSegments)->count();
        }
        return $count;
    }
}
