<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    protected $fillable = [
        'title',
        'description',
        'attached_file',
        'start_date',
        'due_date',
        'assign',
        'class_id',
        'level_id',
        'course_id',
        'year_id',
        'type_id',
        'segment_id',
        'publish_date',
    ];
    public function attachment()
    {
        return $this->hasOne('App\attachment', 'id', 'attached_file');
    }
}
