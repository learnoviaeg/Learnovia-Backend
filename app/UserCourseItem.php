<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserCourseItem extends Model
{
    protected $fillable = ['user_id', 'course_item_id', 'can_view'];

    public function courseItem(){
        return $this->belongsTo('App\CourseItem', 'course_item_id', 'id');
    }

    public function user(){
        return $this->belongsTo('App\User', 'user_id', 'id');
    }

}
