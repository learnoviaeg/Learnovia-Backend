<?php

namespace Modules\Assigments\Entities;

use Illuminate\Database\Eloquent\Model;

class assigment extends Model
{
    protected $fillable = ["name","file","description","has_grade","submit_option","lesson_id","visible","end_date","start_date","user_id"];
}
