<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Q_MCQ extends Model
{
    protected $fillable=['text','choices','question_id'];

    protected $appends = ['mcq_choices'];

    public function getMcqChoicesAttribute(){
        return collect(json_decode($this->choices));
    }
}
