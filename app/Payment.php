<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = ['amount'
        ,'date'
        ,'due_date'
        ,'note'
        ,'contract_id'
        ,'status_id'
        ,'child_id'
    ];
}
