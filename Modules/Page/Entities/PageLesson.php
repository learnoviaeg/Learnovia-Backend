<?php

namespace Modules\Page\Entities;

use Illuminate\Database\Eloquent\Model;

class pageLesson extends Model
{
    protected $fillable = ['page_id','lesson_id','visible' , 'publish_date'];
}
