<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ItemType extends Model
{
    protected $fillable = ['name'];
    public function GradeItems()
    {
        return $this->hasMany('App\GradeItems');
    }
}

