<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\SoftDeletes;

class Level extends Model
{
    use SoftDeletes;

    protected $fillable = ['name'];
    public function years()
    {
        return $this->belongsToMany('App\AcademicYearType', 'year_levels', 'level_id', 'academic_year_type_id');
    }

    public function yearlevel()
    {
        return $this->hasMany('App\YearLevel', 'level_id', 'id');
    }

    public static function Validate($data){
        $validator = Validator::make($data, [
            'name' => 'required|string|max:50',
        ]);
        if ($validator->fails()) {
            return $validator->errors();
        }
        return true ;
    }

    public static function GetAllLevelsInYear($id){
        $tmp = YearLevel::whereAcademic_year_type_id($id)->get(['level_id']);
        $ids = [];
        foreach ($tmp as $id){
            $ids[] = $id['level_id'];
        }
        return $ids;
    }
    protected $hidden = [
        'created_at','updated_at','deleted_at'
    ];

    public function timeline()
    {
        return $this->hasMany('App\Timeline','level_id','id');
    }
}
