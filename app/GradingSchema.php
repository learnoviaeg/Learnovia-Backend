<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class GradingSchema extends Model
{
    protected $table = 'grading_schema';
    protected $fillable = ['name','description','is_drafted'];

    public function gradeCategoryParents(){
        return $this->hasMany('App\GradeCategory', 'grading_schema_id' , 'id')->where('parent',null);
    }
}
