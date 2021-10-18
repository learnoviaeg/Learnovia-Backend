<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Notification extends Model
{
    protected $fillable = ['item_id','item_type','message','created_by','course_id','classes','lesson_id','type','publish_date','link'];

    protected $appends = ['course_name'];

    public function users():BelongsToMany
    {
        return $this->BelongsToMany(User::class)->withPivot('read_at');
    }

    public function course()
    {
        return $this->belongsTo('App\Course');
    }

    public function lesson()
    {
        return $this->belongsTo('App\Lesson');
    }

    public function getCourseNameAttribute()
    {
        return $this->course ? $this->course->name : null;
    }
}
