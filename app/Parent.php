<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Parents extends Model
{
    protected $fillable = ['parent_id' , 'child_id'];

    protected $hidden = [
        'created_at', 'updated_at'
    ];

    public function parent()
    {
        return $this->belongsTo('App\User','parent_id' , 'id');
    }

    public function child()
    {
        return $this->belongsTo('App\User','child_id' , 'id');
    }
}
