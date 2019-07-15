<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Lesson extends Model
{
    protected $fillable = ['name'];
    public function courseSegment(){
        return $this->belongsTo('App\CourseSegment');
    }
}
