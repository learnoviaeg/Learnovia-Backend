<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

class Classes extends Model
{
    protected $fillable = ['name'];
    public $primaryKey = 'id';

    public function classes()
    {
        return $this->belongsToMany('App\ClassLevel');
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
