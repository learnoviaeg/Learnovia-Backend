<?php

namespace Modules\UploadFiles\Entities;

use Illuminate\Database\Eloquent\Model;

class media extends Model
{
    protected $fillable = ['id','name','course_segment_id','media_id'];
    protected $hidden = ['updated_at','created_at','user_id'];

    public function MediaCourseSegment()
    {
        return $this->belongsTo('Modules\UploadFiles\Entities\MediaCourseSegment', 'id', 'media_id');
    }

    public function user()
    {
        return $this->belongsTo('App\User', 'user_id', 'id');
    }

    public static function generateId() {
        $number = uniqid();

        // call the same function if the barcode exists already
        if (media::idExists($number)) {
            return media::generateId();
        }

        // otherwise, it's valid and can be used
        return $number;
    }

    public static function idExists($number) {
        // query the database and return a boolean
        // for instance, it might look like this in Laravel
        return media::whereId($number)->exists();
    }

}
