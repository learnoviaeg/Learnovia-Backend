<?php

namespace Modules\Page\Entities;

use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    protected $fillable = ['title' ,'content','visible'];
}
