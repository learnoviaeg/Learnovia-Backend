<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class GradeItems extends Model
{
    protected $fillable = ['grade_category','grademin','grademax','calculation','item_no','scale_id','grade_pass','multifactor',
        'plusfactor','aggregationcoef','aggregationcoef2','item_type','name','item_Entity','hidden','override','id_number'];

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

    public function weight(){
        if($this->attributes['weight'] != 0)
            return $this->attributes['weight'];
        return round(($this->grademax * $this->GradeCategory->percentage()) / $this->GradeCategory->total() , 3);
    }
}
