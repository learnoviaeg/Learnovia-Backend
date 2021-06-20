<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ItemDetailsUser extends Model
{
    protected $fillable = [
        'user_id','item_details_id', 'grade', 'Answers_Correction',
    ];

    public function getAnswersCorrectionAttribute()
    {
        $content= json_decode($this->attributes['Answers_Correction']);
        return $content;
    }
}