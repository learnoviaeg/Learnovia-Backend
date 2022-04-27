<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Attendance\Entities\AttendanceSession;
use App\Traits\Auditable;

class Classes extends Model
{
    use Auditable;
    use SoftDeletes;

    protected $fillable = ['name','level_id','type'];
    public $primaryKey = 'id';

    protected $hidden = [
       'created_at','updated_at'
    ];

    public function level()
    {
        return $this->hasOne('App\Level' , 'id', 'level_id');
    }

    // public function Segment_class()
    // {
    //     return $this->hasMany('App\SegmentClass', 'class_level_id','id');
    // }

    public function sessions()
    {
        return $this->hasMany('Modules\Attendance\Entities\AttendanceSession','class_id','id');
    }

    public function timeline()
    {
        return $this->hasMany('App\Timeline','class_id','id');
    }

    public static function Validate($data)
    {
        $validator = Validator::make($data, [
            'name' => 'required|string|max:50',
        ]);
        if ($validator->fails()) {
            return $validator->errors();
        }
        return true;
    }

    // start function get name and value f attribute
    public static function get_year_name($old, $new)
    {
        $level_id           = intval($new['level_id']);
        $academic_type_id   = Level::where('id', $level_id)->first()->academic_type_id;
        $academic_year_id[] = AcademicType::where('id', $academic_type_id)->first()->academic_year_id;
        return $academic_year_id;
    }
    // end function get name and value attribute

    // start function get name and value f attribute
    public static function get_type_name($old, $new)
    {
        $level_id           = intval($new['level_id']);
        $academic_type_id[] = Level::where('id', $level_id)->first()->academic_type_id;
        return $academic_type_id;
    }
    // end function get name and value attribute

    // start function get name and value f attribute
    public static function get_level_name($old, $new)
    {
        $level_id = [intval($new['level_id'])];
        return $level_id;
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
