<?php

namespace Modules\Attendance\Entities;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    protected $fillable = ['name', 'type', 'graded', 'allowed_courses','start_date','end_date','year_id','segment_id','type_id', 'allowed_classes', 'allowed_levels'];
    public static $FIRST_TYPE = 1;
    public static $SECOND_TYPE = 2;
    public function session()
    {
        return $this->hasMany('Modules\Attendance\Entities\AttendanceSession', 'attendance_id', 'id');
    }
    public function status()
    {
        return $this->hasMany('Modules\Attendance\Entities\AttendanceStatus', 'attendance_id', 'id');
    }
    public static function FirstTypeRules()
    {
        return [
            'name' => 'required|string',
            'segment' => 'required|integer|exists:segments,id',
            'year' => 'required|integer|exists:academic_years,id',
            'type' => 'required|integer|exists:academic_types,id',
            'graded' => 'required|boolean',
            'grade_items' => 'required',
            'start' => 'required|date',
            'end' => 'required|date',
            'grade_items.min' => 'required|integer',
            'grade_items.max' => 'required|integer',
            'levels' => 'required|array|min:1',
            'levels.*.id' => 'exists:levels,id',
            'levels.*.classes' => 'required|array',
            'levels.*.classes.*' => 'required|exists:classes,id',
            'levels.*.courses' => 'required|array',
            'levels.*.courses.*' => 'required|exists:courses,id',
            'levels.*.grade_category_name' => 'required|string|exists:grade_categories,name',
        ];
    }

    public static function SecondTypeRules($times)
    {
        $array = [
            'name' => 'required|string',
            'segment' => 'required|integer|exists:segments,id',
            'year' => 'required|integer|exists:academic_years,id',
            'type' => 'required|integer|exists:academic_types,id',
            'graded' => 'required|boolean',
            'start' => 'required|date',
            'end' => 'required|date',
            'sessions' => 'required',
            'grade_items' => 'required_if:graded,1',
            'grade_items.min' => 'required_if:graded,1|integer',
            'grade_items.max' => 'required_if:graded,1|integer',
            'levels' => 'required_if:graded,1|array|min:1',
            'levels.*.id' => 'required_if:graded,1|exists:levels,id',
            'levels.*.classes' => 'required_if:graded,1|array',
            'levels.*.classes.*' => 'required|exists:classes,id',
            'levels.*.periods' => 'required_if:graded,1|array',
            'levels.*.periods.*.courses' => 'required|exists:courses,id',
            'levels.*.periods.*.from' => 'required|date',
            'levels.*.periods.*.to' => 'required|date',
            'levels.*.periods.*.grade_category_name' => 'required|string|exists:grade_categories,name',
        ];
        $array['sessions.time'] = 'required|array|size:' . $times;
        $array[ 'sessions.time.*.start'] = 'required|regex:/(\d+\:\d+)/';
        $array[ 'sessions.time.*.end'] = 'required|regex:/(\d+\:\d+)/';
        return $array;
    }

    public static function get_CourseSegments_by_AttendenceID($id)
    {
        $Attendence = Attendance::where('id', $id)->first();
        $Course_Segments = $Attendence->session->pluck('course_segment_id');
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
            case 'saturday';
                return Carbon::SATURDAY;
                break;
            case 'friday';
                return Carbon::FRIDAY;
                break;
        }
    }

    public static function getAllWorkingDays($start, $end)
    {
        $holidays = self::getHolidays();
        foreach ($holidays as $day) {
            $startDate = Carbon::parse($start)->next(self::GetCarbonDay($day));
            $endDate = Carbon::parse($end);

            for ($date = $startDate; $date->lte($endDate); $date->addWeek()) {
                $Allholidays[] = Carbon::parse($date->format('Y-m-d H:i:s'));
            }
        }
        $interval = new \DateInterval('P1D');
        $realEnd = new \DateTime($end);
        $realEnd->add($interval);

        $period = new \DatePeriod(new \DateTime($start), $interval, $realEnd);

        foreach ($period as $date) {

            if (!in_array($date, $Allholidays)) {
                $alldays[] = $date->format('Y-m-d');
            }
        }
        return $alldays;

    }

    public static function getHolidays()
    {
        return ['friday', 'saturday'];
    }
    public static function check_in_array($all,$small){
        if( count(array_intersect($small, $all)) == count($small)){
            return true;
        }
        return false;
    }

    public function getAllowedClassesAttribute($value)
    {
        if(is_null($value))
            return $value;
        $temp = [];
        $value = unserialize($value);
        foreach ($value as $classes){
                $temp[] = $classes;
        }
        return $temp;
    }

    public function getAllowedLevelsAttribute($value)
    {
        if(is_null($value))
            return $value;
        $temp = [];
        $value = unserialize($value);
        foreach ($value as $levels){
            $temp[] = $levels;
        }
        return $temp;
    }

    public function getAllowedCoursesAttribute($value)
    {
        if(is_null($value))
            return $value;
        $temp = [];
        $value = unserialize($value);
        foreach ($value as $courses){
                $temp[] = $courses;
        }
        return $temp;
    }
}
