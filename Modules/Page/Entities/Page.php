<?php

namespace Modules\Page\Entities;

use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    protected $fillable = ['title' ,'content','visible'];
    public function Lesson()
{
    return $this->belongsToMany('App\Lesson', 'page_lessons', 'page_id', 'lesson_id');
}
}
