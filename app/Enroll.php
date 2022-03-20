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
}
