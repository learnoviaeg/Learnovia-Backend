<?php

namespace Modules\Bigbluebutton\Entities;

use Illuminate\Database\Eloquent\Model;

class BigbluebuttonModel extends Model
{
    protected $fillable = ['name','class_id','course_id','attendee_password','moderator_password','duration'];
}
