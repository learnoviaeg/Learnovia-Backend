<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class BloomCategory extends Model
{
    protected $guarded = [];

    public function questions()
    {
        return $this->hasMany('Modules\QuestionBank\Entities\Questions','complexity','id');
    }
}
