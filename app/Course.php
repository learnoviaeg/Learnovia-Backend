<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Modules\Attendance\Entities\AttendanceSession;


class Course extends Model
{
    protected $fillable = ['name' , 'category_id','mandatory' , 'image' , 'description','short_name','progress','level_id','segment_id',
    'is_template','classes', 'letter_id'];

    protected $dispatchesEvents = [
        'created' => \App\Events\CourseCreatedEvent::class,
    ];

    public function level()
    {
        // $query->whereHas('courses'function($query2){
        //     $query->whereIn($query->courses->pluck('id'))
        // })
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

    protected $hidden = [
        'created_at', 'updated_at',
    ];
    public function category(){
        return $this->belongsTo('App\Category');
    }

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

    public function materials()
    {
        return $this->hasMany('App\Material','course_id','id');
    }
 
    public function letter()
    {
        return $this->belongsTo('App\Letter', 'letter_id', 'id');
    }

    public function Scale()
    {
        return $this->hasMany('App\course_scales','course_id','id');
    }
}
