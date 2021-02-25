<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Q_MCQ_Choices extends Model
{
    protected $fillable=['is_true','content','q_mcq_id'];

    // public function q_mcq()
    // {
    //     return $this->belongsTo('App\Q_MCQ','q_mcq_id','id');
    // }
    
}
