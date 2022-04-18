<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use DB;
use App\Traits\Auditable;

class Enroll extends Model
{
    use Auditable;

    protected $fillable = ['user_id', 'username', 'course_segment', 'role_id', 'level', 'group' ,'year', 'type', 'segment', 'course'];

    protected $dispatchesEvents = [
        'created' => \App\Events\UserEnrolledEvent::class,
    ];

    public static function IsExist($course,$class, $user_id,$role_id)
    {
        $check = self::where('course', $course)->where('group',$class)->where('role_id',$role_id)->where('user_id', $user_id)->pluck('id')->first();
        return $check;
    }

    protected $hidden = [
        'created_at', 'updated_at'
    ];

    public function user()
    {
        return $this->belongsTo('App\User','user_id','id');
    }
    public function classes()
    {
        return $this->belongsTo('App\Classes','group','id');
    }
    public function courses()
    {
        return $this->belongsTo('App\Course','course','id');
    }
    public function levels()
    {
        return $this->belongsTo('App\Level','level','id');
    }
    public function year()
    {
        return $this->belongsTo('App\AcademicYear','year','id');
    }
    public function type()
    {
        return $this->belongsTo('App\AcademicType','type','id');
    }
    public function Segment()
    {
        return $this->belongsTo('App\Segment','segment','id');
    }

    public function roles()
    {
        return $this->belongsTo('Spatie\Permission\Models\Role', 'role_id', 'id');
    }

    public function users()
    {
        return $this->hasMany('App\User','id' , 'user_id');
    }

    public function SecondaryChain()
    {
        return $this->hasMany('App\SecondaryChain','enroll_id' , 'id');
    }

    public function Lessons()
    {
        return $this->hasManyThrough(Lesson::class, SecondaryChain::class); // m4 4a8ala
    }

    public function topics()
    {
        return $this->belongsToMany('App\Topic' , 'topic_id' , 'id');
    }

    // start function get name and value f attribute
    public static function get_year_name($old, $new)
    {
        $old_count = count($old);
        if ($old_count == 0) {
            $year_id = [intval($new['year'])];
        }else{
            if ($old['year'] == $new['year']) {
                $year_id = [intval($new['year'])];
            }else{
                $year_id = [intval($old['year']), intval($new['year'])];
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
            $type_id = [intval($new['type'])];
        }else{
            if ($old['type'] == $new['type']) {
                $type_id = [intval($new['type'])];
            }else{
                $type_id = [intval($old['type']), intval($new['type'])];
            }
        }
        return $type_id;
    }
    // end function get name and value attribute

    // start function get name and value f attribute
    public static function get_level_name($old, $new)
    {
        $old_count = count($old);
        if ($old_count == 0) {
            $level_id = [intval($new['level'])];
        }else{
            if ($old['level'] == $new['level']) {
                $level_id = [intval($new['level'])];
            }else{
                $level_id = [intval($old['level']), intval($new['level'])];
            }
        }
        return $level_id;
    }
    // end function get name and value attribute

    // start function get name and value f attribute
    public static function get_class_name($old, $new)
    {
        $old_count = count($old);
        if ($old_count == 0) {
            $class_id = [intval($new['group'])];
        }else{
            if ($old['group'] == $new['group']) {
                $class_id = [intval($new['group'])];
            }else{
                $class_id = [intval($old['group']), intval($new['group'])];
            }
        }
        return $class_id;
    }
    // end function get name and value attribute

    // start function get name and value f attribute
    public static function get_segment_name($old, $new)
    {
        $old_count = count($old);
        if ($old_count == 0) {
            $segment_id = [intval($new['segment'])];
        }else{
            if ($old['segment'] == $new['segment']) {
                $segment_id = [intval($new['segment'])];
            }else{
                $segment_id = [intval($old['segment']), intval($new['segment'])];
            }
        }
        return $segment_id;
    }
    // end function get name and value attribute

    // start function get name and value f attribute
    public static function get_course_name($old, $new)
    {
        $old_count = count($old);
        if ($old_count == 0) {
            $course_id = [intval($new['course'])];
        }else{
            if ($old['course'] == $new['course']) {
                $course_id = [intval($new['course'])];
            }else{
                $course_id = [intval($old['course']), intval($new['course'])];
            }
        }
        return $course_id;
    }
    // end function get name and value attribute
}
