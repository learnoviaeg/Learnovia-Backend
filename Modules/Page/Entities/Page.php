<?php

namespace Modules\Page\Entities;

use Illuminate\Database\Eloquent\Model;

class page extends Model
{
    protected $fillable = ['title', 'content', 'visible'];
    public function Lesson()
    {
        return $this->belongsToMany('App\Lesson', 'page_lessons', 'page_id', 'lesson_id');
    }

    public function pageLesson()
    {
        return $this->hasMany('Modules\Page\Entities\pageLesson');
    }

    public function getContent($value)
    {
        return $value->getOriginal();
    }
}
