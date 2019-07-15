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

    public function category(){
        return $this->belongsTo('App\Category');
    }

    public function courseSegments(){
        return $this->hasMany('App\CourseSegment');
    }
}