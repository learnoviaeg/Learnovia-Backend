<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Contract extends Model
{
    protected $fillable = [
        'attachment_id'
        ,'start_date'
        ,'end_date'
        ,'numbers_of_users'
        ,'total'
        ,'allowance_period'];

}
