<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class GradeItems extends Model
{
    protected $fillable = ['grade_category','grademin','grademax','calculation','item_no','scale_id','grade_pass','multifactor','plusfactor','aggregationcoef','aggregationcoef2','item_type','item_Entity','hidden'];

    public function GradeCategory()
    {
        return $this->belongsTo('App\GradeCategory', 'grade_category', 'id');
    }
    public function ItemType()
    {
        return $this->belongsTo('App\ItemType', 'item_type', 'id');
    }
    public function scale()
    {
        return $this->belongsTo('App\scale', 'scale_id', 'id');
    }

    public function UserGrade()
    {
        return $this->hasMany('App\UserGrade','grade_item_id','id');
    }
}
