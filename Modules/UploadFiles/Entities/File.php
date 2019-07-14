<?php

namespace Modules\UploadFiles\Entities;

use Illuminate\Database\Eloquent\Model;

class file extends Model
{
    protected $fillable = [];
    protected $hidden = ['updated_at','created_at','user_id'];

    public function FileCourseSegment()
    {
        return $this->belongsTo('Modules\UploadFiles\Entities\FileCourseSegment', 'id', 'file_id');
    }

    public function user()
    {
        return $this->belongsTo('App\User', 'user_id', 'id');
    }

    public static function generateId() {
        $number = uniqid();

        // call the same function if the barcode exists already
        if (file::idExists($number)) {
            return file::generateId();
        }

        // otherwise, it's valid and can be used
        return $number;
    }

    public static function idExists($number) {
        // query the database and return a boolean
        // for instance, it might look like this in Laravel
        return file::whereId($number)->exists();
    }
}
