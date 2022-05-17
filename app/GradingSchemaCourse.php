<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class GradingSchemaCourse extends Model
{
    protected $fillable = ['course_id','level_id','grading_schema_id'];
}
