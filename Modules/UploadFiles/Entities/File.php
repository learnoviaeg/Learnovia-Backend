<?php

namespace Modules\UploadFiles\Entities;

use Illuminate\Database\Eloquent\Model;

class file extends Model
{
    protected $fillable = [];
    protected $hidden = ['updated_at','created_at'];
}
