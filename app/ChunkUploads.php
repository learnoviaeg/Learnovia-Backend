<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ChunkUploads extends Model
{
    protected $fillable = ['name','data','path', 'uploaded' , 'type'];
    protected $hidden = ['created_at','updated_at'];

  public function getPathAttribute() {
      return url(Storage::url($this->attributes['path']));
    }

}
