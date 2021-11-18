<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserGrader extends Model
{
    protected $fillable = ['user_id', 'item_type', 'item_id','grade'];

    protected $guarded = ['created_at','updated_id'];
    
    public function user()
    {
        return $this->belongsTo('App\User', 'user_id', 'id');
    }

    public function getGradeAttribute($value)
    {
        $content= json_decode($value);
        if(!is_null($value))
            $content = round($value , 2);
        return $content;
    }

}
