<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Log extends Model
{
    protected $fillable = [
    	'user','action','model','data', 'model_id', 'user_id', 
    	'year_id', 'type_id', 'level_id', 'class_id', 'segment_id', 'course_id'];

       protected $casts = [
        'year_id'    => 'array',
        'type_id'    => 'array',
        'level_id'   => 'array',
        'class_id'   => 'array',
        'segment_id' => 'array', 
        'course_id'  => 'array',
    ];

    public function user()
    {
        return $this->belongsTo('App\User','user','username');
    }

    public function users()
    {
        return $this->belongsTo('App\User','user','username');
    }
}
