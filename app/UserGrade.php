<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserGrade extends Model
{
    protected $fillable = ['grade_item_id','user_id','raw_grade','raw_grade_max','raw_grade_min'
    ,'raw_scale_id','final_grade','hidden',
    'locked','feedback','letter_id'];
public function GradeItems()
{
    return $this->belongsTo('App\GradeItems', 'grade_item_id', 'id');
}
public function user()
{
    return $this->belongsTo('App\User', 'user_id', 'id');
}
public function scale()
{
    return $this->belongsTo('App\scale', 'raw_scale_id', 'id');
}
public function Letter()
{
    return $this->belongsTo('App\Letter', 'letter_id', 'id');
}
}

