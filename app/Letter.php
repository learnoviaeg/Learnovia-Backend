<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
class Letter extends Model
{
    protected $fillable = ['name' , 'chain'];
    protected $hidden = ['created_at' , 'updated_at'];

    public function course()
    {
        return $this->hasMany('App\Course', 'letter_id', 'id');
    }

    public function getChainAttribute($value)
    {   if($value != null){
            $content= json_decode($value);
            return $content;
        }
        return $value;
    }

    public function details()
    {
        return $this->hasMany('App\LetterDetails', 'letter_id' , 'id');
    }
}
   