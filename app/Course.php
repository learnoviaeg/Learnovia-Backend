<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Modules\Attendance\Entities\AttendanceSession;


class Course extends Model
{
    protected $fillable = ['name' , 'category_id','mandatory' , 'image' , 'description','short_name','progress','level_id','segment_id',
    'is_template','classes'];

    protected $dispatchesEvents = [
        'created' => \App\Events\CourseCreatedEvent::class,
    ];

    public function level()
    {
        return $this->belongsTo('App\Level','level_id','id');
    }

    public static function findByName($course_name)
    {
        return self::where('name',$course_name)->pluck('id')->first();
    }

    public static function findById($course_id)
    {
        return self::where('id',$course_id)->pluck('id')->first();
    }

    public function optionalCourses()
    {
        return self::whereMandatory(0);
    }

    public function letter()
    {
        return $this->hasMany('App\Letter');
    }
    protected $hidden = [
        'created_at', 'updated_at',
    ];
    public function category(){
        return $this->belongsTo('App\Category');
    }

    // public  function courseSegments(){
    //     return $this->hasMany('App\CourseSegment');
    // }

    // public function activeSegment(){
    //     return $this->hasOne('App\CourseSegment')->whereIs_active(1);
    // }

    public function attachment()
    {
        return $this->hasOne('App\attachment', 'id', 'image');
    }
    
    public function sessions()
    {
        return $this->hasMany('Modules\Attendance\Entities\AttendanceSession','course_id','id');
    }

    public function timeline()
    {
        return $this->hasMany('App\Timeline','course_id','id');
    }

    public function getMandatoryAttribute()
    {
        $content=false;
        if($this->attributes['mandatory']==1)
            $content = true;
        return $content;
    }

    public function getImageAttribute()
    {
        if($this->attributes['image'] !=null){
            $attachment=attachment::find($this->attributes['image']);
            return $attachment->path;
        }
    }

    public function getClassesAttribute($value)
    {   if($value != null){
            $content= json_decode($value);
            return $content;
        }
        return $value;
    }

    public function gradeCategory()
    {
        return $this->hasMany('App\GradeCategory','course_id','id');
    }
}
