<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ScaleDetails extends Model
{
    protected $fillable = [ 'evaluation' , 'scale_id' , 'grade'];
    protected $hidden = ['created_at' , 'updated_at'];

    public function scale()
    {
        return $this->belongsTo('App\Scale', 'scale_id', 'id');
    }

}
