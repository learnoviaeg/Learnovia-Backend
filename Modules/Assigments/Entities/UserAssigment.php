<?php

namespace Modules\Assigments\Entities;

use Illuminate\Database\Eloquent\Model;

class UserAssigment extends Model
{
    protected $fillable = ['user_id', 'assignment_id', 'attachment_id', 'submit_date', 'content', 'override', 'status_id', 'feedback', 'grade'];

    public function assignment()
    {
        return $this->belongsTo('Modules\Assigments\Entities\assignment', 'attachment_id', 'id');
    }
    public function status()
    {
        return $this->belongsTo('Modules\Assigments\Entities\status', 'status_id', 'id');
    }
    public function attachment()
    {
        return $this->hasOne('App\attachment', 'attachment_id', 'id');
    }
    public function user()
    {
        return $this->belongsTo('App\User', 'id', 'user_id');
    }
}
