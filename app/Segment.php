<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;
use App\Traits\Auditable;

class Segment extends Model
{
    use Auditable;
    use SoftDeletes;

    protected $fillable = ['name','academic_type_id','academic_year_id','start_date','end_date'];

    // public function Segment_class(){
    //     return $this->belongsToMany('App\ClassLevel', 'segment_classes','segment_id','class_level_id');
    // }

    public function academicType()
    {
        return $this->belongsTo('App\AcademicType', 'academic_type_id', 'id','start_date','end_date');
    }

    public function academicYear()
    {
        return $this->belongsTo('App\AcademicYear', 'academic_year_id', 'id');
    }

    // public static function Get_current($type)
    // {
    //     $segment = self::where('academic_type_id', $type)->where("end_date", '>' ,Carbon::now())->where("start_date", '<=' ,Carbon::now())->first();
    //     return $segment;
    // }
    public static function Get_current_by_many_types($types)
    {
        $segment = self::whereIn('academic_type_id', $types)->where("end_date", '>' ,Carbon::now())->where("start_date", '<=' ,Carbon::now())->pluck('id');
        return $segment;
    }

    public static function Get_current_by_one_type($type)
    {
        $segment = self::where('academic_type_id', $type)->where("end_date", '>' ,Carbon::now())->where("start_date", '<=' ,Carbon::now())->pluck('id');
        return $segment;
    }

    protected $hidden = [
        'created_at','updated_at'
    ];

    // start function get name and value f attribute
    public static function get_year_name($old, $new)
    {
        $old_count = count($old);
        if ($old_count == 0) {
            $year_id = [intval($new['academic_year_id'])];
        }else{
            if ($old['academic_year_id'] == $new['academic_year_id']) {
                $year_id = [intval($new['academic_year_id'])];
            }else{
                $year_id = [intval($old['academic_year_id']), intval($new['academic_year_id'])];
            }
        }
        return $year_id;
    }
    // end function get name and value attribute

    // start function get name and value f attribute
    public static function get_type_name($old, $new)
    {
        $old_count = count($old);
        if ($old_count == 0) {
            $type_id = [intval($new['academic_type_id'])];
        }else{
            if ($old['academic_type_id'] == $new['academic_type_id']) {
                $type_id = [intval($new['academic_type_id'])];
            }else{
                $type_id = [intval($old['academic_type_id']), intval($new['academic_type_id'])];
            }
        }
        return $type_id;
    }
    // end function get name and value attribute

    // start function get name and value f attribute
    public static function get_level_name($old, $new)
    {
        return null;
    }
    // end function get name and value attribute

    // start function get name and value f attribute
    public static function get_class_name($old, $new)
    {
        return null;
    }
    // end function get name and value attribute

    // start function get name and value f attribute
    public static function get_segment_name($old, $new)
    {
        return null;
    }
    // end function get name and value attribute

    // start function get name and value f attribute
    public static function get_course_name($old, $new)
    {
        return null;
    }
    // end function get name and value attribute
}
