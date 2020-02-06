<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    protected $fillable = ['name' , 'category_id','mandatory' , 'image' , 'description'];


    public static function findByName($course_name)
    {
        return self::where('name',$course_name)->pluck('id')->first();
    }

    public static function findById($course_id)
    {
        return self::where('id',$course_id)->pluck('id')->first();
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

    public  function courseSegments(){
        return $this->hasMany('App\CourseSegment','course_id','id');
    }

    public function activeSegment(){
        return $this->hasOne('App\CourseSegment')->whereIs_active(1);
    }

    public function attachment()
    {
        if($this->image == null)
            $this->image= 1;
        return $this->hasOne('App\attachment', 'id', 'image');
    }
}
