<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Q_T_F extends Model
{
    protected $fillable=['is_true','text','and_why','question_id'];
}
