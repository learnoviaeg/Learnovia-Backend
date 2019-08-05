<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Segment extends Model
{
    protected $fillable = ['name'];

    public function Segment_class(){
        return $this->hasMany('App\SegmentClass','segment_id','id');
    }
    protected $hidden = [
        'created_at','updated_at'
    ];
}
