<?php

namespace Modules\Attendance\Entities;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    protected $fillable = ['name','type','grade'];

    public function session()
    {
        return $this->hasMany('Modules\Attendance\Entities\AttendanceSession','attendance_id' ,'id');
    }
    public function status()
    {
        return $this->hasMany('Modules\Attendance\Entities\AttendanceStatus','attendance_id' ,'id');
    }
    
    public static function get_CourseSegments_by_AttendenceID($id){
        $Attendence=Attendance::where('id',$id)->first();
        $Course_Segments=$Attendence->session->pluck('course_segment_id');
        $unique_CourseSeg = $Course_Segments->unique(); 
        return $unique_CourseSeg;    
    }
    public static function GetCarbonDay($day)
    {
        switch ($day) {
            case 'sunday';
                return Carbon::SUNDAY;
                break;
            case 'monday';
                return Carbon::MONDAY;
                break;
            case 'tuesday';
                return Carbon::TUESDAY;
                break;
            case 'wednesday';
                return Carbon::WEDNESDAY;
                break;
            case 'thursday';
                return Carbon::THURSDAY;
                break;
        }
    }
}
