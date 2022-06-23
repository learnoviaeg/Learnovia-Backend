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

    public function levels()
    {
        return $this->belongsToMany('App\Level', 'grading_schema_levels','grading_schema_id','level_id');
    }

    public function courses()
    {
        return $this->belongsToMany('App\Course', 'grading_schema_courses', 'grading_schema_id', 'course_id');
    }

    public function GradingSchemaLevel()
    {
        return $this->belongsTo('App\GradingSchemaLevel','id','grading_schema_id');
    }

    public function scales(){
        return $this->belongsToMany('App\scale', 'grading_schema_scales', 'grading_schema_id', 'scale_id');
    }
}
