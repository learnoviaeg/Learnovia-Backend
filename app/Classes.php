<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Attendance\Entities\AttendanceSession;

class Classes extends Model
{
    use SoftDeletes;

    protected $fillable = ['name','level_id','type'];
    public $primaryKey = 'id';

    protected $hidden = [
       'created_at','updated_at'
    ];
    // public function classlevel()
    // {
    //     return $this->hasMany('App\ClassLevel' ,  'class_id', 'id');
    // }

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
}
