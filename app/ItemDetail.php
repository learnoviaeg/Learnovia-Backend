<?php

namespace App;

use Modules\QuestionBank\Entities\Questions;
use Illuminate\Database\Eloquent\Model;

class ItemDetail extends Model
{
    protected $fillable = [
        'parent_item_id','item_id', 'weight_details', 'type',
    ];

    public function getWeightDetailsAttribute()
    {
        $content= json_decode($this->attributes['weight_details']);

        // if($this->attributes['question_type_id'] == 3){
        //     $content= json_decode($this->attributes['weight_details'],true);
        // }

        $question_type=Questions::whereId($this->attributes['item_id'])->pluck('question_type_id')->first();
        
        if($question_type == 1){
            if($content->is_true == 1)
                $content->is_true=True;
        
            else
                $content->is_true=False;

            if($content->and_why == 1)
                $content->and_why=True;
        
            else
                $content->and_why=False;
        }
        return $content;
    }
}
