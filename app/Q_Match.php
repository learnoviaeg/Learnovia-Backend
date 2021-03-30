<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Q_Match extends Model
{
    protected $fillable=['text','matches','question_id'];

    protected $appends = ['match'];

    public function getMatchAttribute(){
        return collect(json_decode($this->matches));
    }
}
