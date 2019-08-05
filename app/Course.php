<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    protected $fillable = ['name' , 'category_id'];
    

    public static function findByName($course_name)
    {
        return self::where('name',$course_name)->pluck('id')->first();
    }


    protected $hidden = [
        'created_at', 'updated_at',
    ];
    public function category(){
        return $this->belongsTo('App\Category');
    }

    public  function courseSegments(){
        return $this->hasMany('App\CourseSegment');
    }

    public function activeSegment(){
        return $this->hasMany('App\CourseSegment')->whereIs_active(1);
    }
}