<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\GradeItems;
class scale extends Model
{
    protected $fillable = ['name' , 'chain'];
    protected $hidden = ['created_at' , 'updated_at'];

    // public function GradeItems()
    // {
    //     return $this->hasMany('App\GradeItems');
    // }
    // public function UserGrade()
    // {
    //     return $this->hasMany('App\UserGrade');
    // }

    // public function getAllowAttribute(){
    //     if(GradeItems::where('scale_id' , $this->id)->first() != null)
    //         return false;
    //     return true;
    // }

    public function getChainAttribute($value)
    {   if($value != null){
            $content= json_decode($value);
            return $content;
        }
        return $value;
    }

    public function details()
    {
        return $this->hasMany('App\ScaleDetails', 'scale_id' , 'id');
    }


}
