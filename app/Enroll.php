<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use DB;

class Enroll extends Model
{
    protected $fillable = ['user_id', 'username', 'course_segment', 'role_id', 'level', 'group' ,'year', 'type', 'segment', 'course'];

    protected $dispatchesEvents = [
        'created' => \App\Events\UserEnrolledEvent::class,
    ];

    public static function getroleid($user_id, $course_segment)
    {
        return self::where('user_id', $user_id)->where('course_segment', $course_segment)->pluck('role_id')->first();
    }

    public static function IsExist($course,$class, $user_id,$role_id)
    {
        $check = self::where('course', $course)->where('group',$class)->where('role_id',$role_id)->where('user_id', $user_id)->pluck('id')->first();
        return $check;
    }

    public static function FindUserbyID($user_id, $course_segment)
    {
        $check = self::where('user_id', $user_id)->where('course_segment', $course_segment);
        return $check;
    }

    public static function GetCourseSegment($user_id)
    {
        $check = self::where('user_id', $user_id)->pluck('course_segment');
        return $check;
    }

    public static function GetUsers_id($course_seg_id){
        $check = self::where('course_segment', $course_seg_id)->where('role_id',3)->pluck('user_id');
        return $check;
    }

    protected $hidden = [
        'created_at', 'updated_at'
    ];

    public function courseSegment()
    {
        return $this->belongsTo('App\CourseSegment', 'course_segment');
    }

    public static function Get_User_ID($Course_segment_id)
    {
        $check = self::where('course_segment', $Course_segment_id)->pluck('user_id');
        return $check;
    }

    public function user()
    {
        return $this->belongsTo('App\User','user_id','id');
    }
    public function classes()
    {
        return $this->belongsTo('App\Classes','class','id');
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
        return $this->belongsTo('App\Classes','year','id');
    }
    public function type()
    {
        return $this->belongsTo('App\Course','type','id');
    }
    public function segment()
    {
        return $this->belongsTo('App\Level','segment','id');
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
        return $this->hasManyThrough(Lesson::class, SecondaryChain::class);
    }
}
