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
        return null;
    }
    // end function get name and value attribute

    // start function get name and value f attribute
    public static function get_type_name($old, $new)
    {
        return null;
    }
    // end function get name and value attribute

    // start function get name and value f attribute
    public static function get_level_name($old, $new)
    {
        $old_count = count($old);
        if ($old_count == 0) {
            $item_id = [intval($new['level_id'])];
        }else{
            if ($old['level_id'] == $new['level_id']) {
                $item_id = [intval($new['level_id'])];
            }else{
                $item_id = [intval($old['level_id']), intval($new['level_id'])];
            }
        }
        return $item_id;
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
