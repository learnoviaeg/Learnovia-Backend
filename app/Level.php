<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
class Level extends Model
{
    protected $fillable = ['name'];
    public function years()
    {
        return $this->belongsToMany('App\AcademicYearType', 'year_levels', 'level_id', 'academic_year_type_id');
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
}
