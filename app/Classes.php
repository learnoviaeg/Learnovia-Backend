<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

class Classes extends Model
{
    protected $fillable = ['name'];
    public $primaryKey = 'id';

    public function classlevel()
    {
        return $this->belongsTo('App\ClassLevel');
    }

    public function Segment_class()
    {
        return $this->belongsToMany('App\SegmentClass', 'ClassLevel', 'class_id','id');
    }

    public static function Validate($data)
    {
        $validator = Validator::make($data, [
            'name' => 'required|string|max:50',
        ]);
        if ($validator->fails()) {
            return $validator->errors();
        }
        return true;
    }
}
