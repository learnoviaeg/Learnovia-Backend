<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class GradingSchemaLevel extends Model
{
    protected $fillable = ['level_id','grading_schema_id','segment_id'];

    public function level()
    {
        return $this->belongsTo('App\Level','level_id','id');
    }

    public function segment()
    {
        return $this->belongsTo('App\Segment','segment_id','id');
    }
}
