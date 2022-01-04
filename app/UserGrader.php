<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserGrader extends Model
{
    protected $fillable = ['user_id', 'item_type', 'item_id','grade' , 'percentage', 'letter'];
    protected $hidden = [
        'created_at', 'updated_at'
    ];
    protected $guarded = ['created_at','updated_id'];
    
    // protected $dispatchesEvents = [
    //     'created' => \App\Events\GradeCalculatedEvent::class,
    //     'updated' => \App\Events\GradeCalculatedEvent::class,
    // ];

    public function user()
    {
        return $this->belongsTo('App\User', 'user_id', 'id');
    }

    public function category()
    {
        return $this->belongsTo('App\GradeCategory', 'item_id', 'id');//->where('item_type', 'category');
    }

    public function student()
    {
        return $this->belongsTo('App\User', 'user_id', 'id')->whereHas('roles', function($q){
            $q->where('role_id',3);
        });
    }

    public function getGradeAttribute($value)
    {
        $content= json_decode($value);
        if(!is_null($value))
            $content = round($value , 2);
        return $content;
    }

}
