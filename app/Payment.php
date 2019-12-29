<?php

namespace App;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'amount'
        ,'date'
        ,'note'
        ,'contract_id'
        ,'status_id'
        ,'child_id'
    ];
    protected $appends = ['due_date'];

    public function getDueDateAttribute()
    {
       $allowance_period=  Contract::find($this->contract_id)->allowance_period;
        $newDate = Carbon::parse($this->date)->addDays($allowance_period)->format('Y-m-d');
        return  $newDate ;
    }

    public function status()
    {
        return $this->belongsTo('App\Status', 'status_id', 'id');
    }
}
