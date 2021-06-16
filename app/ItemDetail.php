<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ItemDetail extends Model
{
    protected $fillable = [
        'parent_item_id','item_id', 'weight_details', 'type',
    ];
}
