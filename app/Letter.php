<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\CourseSegment;
class Letter extends Model
{
    protected $fillable = ['name' , 'formate'];

    protected $appends = ['allow'];

    public function UserGrade()
    {
        return $this->hasMany('App\UserGrade');
    }

    public function course()
    {
        return $this->belongsToMany('App\Course', 'course_id', 'id');
    }

    public function getAllowAttribute(){
        if(CourseSegment::where('letter_id' , $this->id)->first() != null)
            return false;
        return true;
    }
}
