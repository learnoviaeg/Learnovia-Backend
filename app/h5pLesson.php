<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class h5pLesson extends Model
{
      protected $fillable = ['content_id',
      'lesson_id',
      'visible',
      'publish_date' ,
       'start_date' ,
        'due_date',
        'user_id'
    ];


}
