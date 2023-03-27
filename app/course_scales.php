<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class course_scales extends Model
{
    protected $fillable = [ 'course_id' , 'scale_id'];
    protected $hidden = ['created_at' , 'updated_at'];

    public function Course()
    {
        return $this->belongsTo('App\Course', 'course_id', 'id');
    }

    public function Scale()
    {
        return $this->belongsTo('App\scale', 'scale_id', 'id');
    }
}
