<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class LetterDetails extends Model
{
    protected $fillable = ['lower_boundary' , 'higher_boundary', 'evaluation' , 'letter_id'];
    protected $hidden = ['created_at' , 'updated_at'];

    public function letter()
    {
        return $this->belongsTo('App\Letter', 'letter_id', 'id');
    }
}
