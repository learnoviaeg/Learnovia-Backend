<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\GradeItems;
class scale extends Model
{
    protected $fillable = ['name' , 'formate', 'course_segment'];
    protected $appends = ['allow'];
    public function GradeItems()
    {
        return $this->hasMany('App\GradeItems');
    }
    public function UserGrade()
    {
        return $this->hasMany('App\UserGrade');
    }

    public function getAllowAttribute(){
        if(GradeItems::where('scale_id' , $this->id)->first() != null)
            return false;
        return true;
    }
}
