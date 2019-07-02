<?php

namespace Modules\Page\Entities;

use Illuminate\Database\Eloquent\Model;


class page extends Model
{
    protected $fillable = [
        'name','page_content','attached_file','class_id','visability','segment_id','start_date','due_date'
    ];
}
