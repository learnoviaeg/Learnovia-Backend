<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class grade extends Model
{
    protected $fillable = ['grade_category','grademin','grademax','calculation','item_no','scale_id','grade_pass','multifactor','plusfactor','aggregationcoef','aggregationcoef2'];
}
