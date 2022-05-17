<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class GradingSchema extends Model
{
    protected $table = 'grading_schema';
    protected $fillable = ['name'];
}
